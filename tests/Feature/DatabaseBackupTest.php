<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The nightly SQL backup.
 *
 * A backup has to be complete and restorable, so these pin the parts that can
 * silently go wrong: a table left out, a big table truncated at the first page,
 * a quote or a backslash in a name breaking the SQL, and an upload failure being
 * reported as success.
 */
class DatabaseBackupTest extends TestCase
{
    private const DATE = '2026-10-30';

    /** A SupabaseService stand-in that serves rows per table and pages them. */
    private function supabaseWith(array $rowsByTable): SupabaseService
    {
        return new class($rowsByTable) extends SupabaseService
        {
            /** @var list<array{table: string, params: array}> */
            public array $calls = [];

            /** @var list<array{table: string, data: array}> */
            public array $recorded = [];

            public function __construct(private array $rows)
            {
            }

            public function query(string $table, array $params = []): array
            {
                $this->calls[] = ['table' => $table, 'params' => $params];

                $rows = $this->rows[$table] ?? [];
                $offset = (int) ($params['offset'] ?? 0);
                $limit = (int) ($params['limit'] ?? 1000);

                return array_slice($rows, $offset, $limit);
            }

            // The backup records itself in `backups` once the uploads are done.
            // These are answered here so a dump test never reaches the real DB.
            public function findOne(string $table, array $conditions, string $select = '*'): ?array
            {
                return null;
            }

            public function insert(string $table, array|object $data): ?array
            {
                $this->recorded[] = ['table' => $table, 'data' => (array) $data];

                return (array) $data;
            }

            public function update(string $table, array|object $data, array $conditions): array
            {
                $this->recorded[] = ['table' => $table, 'data' => (array) $data];

                return [(array) $data];
            }
        };
    }

    private function backups(SupabaseService $supabase): DatabaseBackupService
    {
        return new DatabaseBackupService($supabase);
    }

    private function at(): Carbon
    {
        return Carbon::parse('2026-10-30 00:00:00', 'Africa/Dar_es_Salaam');
    }

    /**
     * Fake Cloudinary and hand back the requests it received.
     */
    private function fakeCloudinary(int $status = 200): array
    {
        $sent = [];

        Http::fake(function (Request $request) use (&$sent, $status) {
            $sent[] = $request;

            if ($status !== 200) {
                return Http::response(['error' => ['message' => 'Invalid Signature']], $status);
            }

            // multipart: data() is the list of parts, not a keyed array
            $publicId = 'unknown';
            foreach ($request->data() as $part) {
                if (($part['name'] ?? '') === 'public_id') {
                    $publicId = (string) $part['contents'];
                }
            }

            return Http::response([
                'public_id' => $publicId,
                'secure_url' => 'https://res.cloudinary.com/test/raw/upload/v1/' . $publicId,
                'bytes' => 1234,
            ], 200);
        });

        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
        ]);

        return $sent;
    }

    public function test_a_backup_files_the_seed_and_its_schema_in_one_folder_for_the_day(): void
    {
        $this->fakeCloudinary();

        $supabase = $this->supabaseWith([
            'branches' => [['id' => 8, 'name' => 'Kinondoni branch', 'is_active' => true]],
        ]);

        $result = $this->backups($supabase)->run($this->at());

        $this->assertSame($result['date'], self::DATE);
        $this->assertSame('4ftdshm54xvhks/backup_' . self::DATE, $result['folder']);

        // both files, in that folder, as raw uploads
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/raw/upload')
            && str_contains($request->body(), '4ftdshm54xvhks/backup_' . self::DATE . '/seed_' . self::DATE . '.sql'));

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/raw/upload')
            && str_contains($request->body(), '4ftdshm54xvhks/backup_' . self::DATE . '/schema_' . self::DATE . '.sql'));

        // the schema copy is the constant structure plus a note saying which
        // day it pairs with, so a restore never has to guess
        $schema = $this->backups($supabase)->schemaFile(self::DATE);
        $this->assertStringContainsString('schema_' . self::DATE . '.sql', $schema);
        $this->assertStringContainsString('CREATE TABLE public.branches', $schema);
        $this->assertStringContainsString('CREATE TABLE public.sales', $schema);
        $this->assertStringContainsString(trim((string) file_get_contents(base_path('SYSTEM_SCHEMA.sql'))), $schema);

        $this->assertSame(
            'https://res.cloudinary.com/test/raw/upload/v1/4ftdshm54xvhks/backup_' . self::DATE . '/seed_' . self::DATE . '.sql',
            $result['files']['seed_' . self::DATE . '.sql']['url']
        );
    }

    public function test_a_completed_backup_is_recorded_in_the_backups_table(): void
    {
        $this->fakeCloudinary();

        $supabase = $this->supabaseWith([
            'branches' => [['id' => 8, 'name' => 'Kinondoni branch']],
        ]);

        $this->backups($supabase)->run($this->at());

        $this->assertCount(1, $supabase->recorded, 'the run should be logged once');
        $this->assertSame('backups', $supabase->recorded[0]['table']);

        $row = $supabase->recorded[0]['data'];

        $this->assertSame(self::DATE, $row['backup_date']);
        $this->assertSame('4ftdshm54xvhks/backup_' . self::DATE, $row['folder']);
        $this->assertSame(1, $row['rows_count']);
        $this->assertSame(45, $row['tables_count']);
        $this->assertStringEndsWith('/seed_' . self::DATE . '.sql', (string) $row['seed_url']);
        $this->assertStringEndsWith('/schema_' . self::DATE . '.sql', (string) $row['schema_url']);
        $this->assertGreaterThan(0, $row['seed_bytes']);
    }

    public function test_the_seed_contains_a_restorable_insert_for_every_row(): void
    {
        $this->fakeCloudinary();

        $supabase = $this->supabaseWith([
            'branches' => [
                ['id' => 8, 'name' => "Kinondoni O'Brien", 'address' => null, 'is_active' => true],
            ],
            'audit_logs' => [
                ['id' => 3, 'action' => 'login', 'old_values' => ['role' => 'cashier'], 'new_values' => null],
            ],
        ]);

        $seed = $this->backups($supabase)->buildSeed(self::DATE, $this->at())['sql'];

        // a quote is doubled, never left to end the literal early
        $this->assertStringContainsString("E'Kinondoni O''Brien'", $seed);
        // nulls and booleans are SQL keywords, not strings
        $this->assertStringContainsString('NULL', $seed);
        $this->assertStringContainsString('TRUE', $seed);
        // jsonb columns come back as arrays and must be written as JSON text
        $this->assertStringContainsString('E\'{"role":"cashier"}\'', $seed);
        // and the whole file is committed as one transaction
        $this->assertStringStartsWith("-- =", $seed);
        $this->assertStringContainsString('BEGIN;', $seed);
        $this->assertStringEndsWith("COMMIT;\n", $seed);

        // column list comes from the schema, in the schema's order (parents first)
        $this->assertStringContainsString(
            'INSERT INTO public.branches (id, name, address, latitude, longitude, profile_picture, is_active, created_at, updated_at, category) VALUES',
            $seed
        );
    }

    public function test_a_large_table_is_paged_so_rows_are_not_silently_dropped(): void
    {
        $this->fakeCloudinary();

        $sales = [];
        for ($i = 1; $i <= 1500; $i++) {
            $sales[] = ['id' => $i, 'sale_number' => 'S-' . $i];
        }

        $supabase = $this->supabaseWith(['sales' => $sales]);
        $seed = $this->backups($supabase)->buildSeed(self::DATE, $this->at());

        $saleCalls = array_values(array_filter($supabase->calls, fn (array $call) => $call['table'] === 'sales'));

        $this->assertCount(2, $saleCalls, 'sales should be read in two pages');
        $this->assertSame(0, $saleCalls[0]['params']['offset']);
        $this->assertSame(1000, $saleCalls[0]['params']['limit']);
        $this->assertSame(1000, $saleCalls[1]['params']['offset']);
        $this->assertSame('id.asc', $saleCalls[0]['params']['order'], 'paging needs a stable order');

        $this->assertSame(1500, $seed['tables']['sales']);
        $this->assertSame(1500, substr_count($seed['sql'], "E'S-"), 'every row must appear in the dump');
    }

    public function test_every_table_in_the_schema_is_backed_up_parents_first(): void
    {
        $this->fakeCloudinary();

        $tables = $this->backups($this->supabaseWith([]))->tables();

        $this->assertCount(45, $tables, 'the table list must follow SYSTEM_SCHEMA.sql');
        $this->assertArrayHasKey('sales', $tables);
        $this->assertArrayHasKey('info_email_attachments', $tables);
        // the log of the backups is itself part of the database, so it is dumped
        $this->assertArrayHasKey('backups', $tables);

        $order = array_keys($tables);
        $this->assertLessThan(array_search('users', $order, true), array_search('branches', $order, true));
        $this->assertLessThan(array_search('sale_items', $order, true), array_search('sales', $order, true));
        $this->assertLessThan(array_search('info_email_replies', $order, true), array_search('info_emails', $order, true));

        // NUMERIC(12, 2) must not be split into two "columns"
        $this->assertNotContains('2)', $tables['products']['columns']);
        $this->assertSame('id', $tables['products']['primary']);
    }

    public function test_only_serial_ids_are_rewound_after_a_restore(): void
    {
        $this->fakeCloudinary();

        $supabase = $this->supabaseWith([
            'branches' => [['id' => 8, 'name' => 'Kinondoni branch']],
            'sessions' => [['id' => 'abc123', 'user_id' => 8, 'payload' => 'x']],
        ]);

        $seed = $this->backups($supabase)->buildSeed(self::DATE, $this->at())['sql'];

        // branches.id is BIGSERIAL: its sequence must move past the restored ids
        $this->assertStringContainsString("pg_get_serial_sequence('public.branches', 'id')", $seed);

        // sessions.id is VARCHAR — there is no sequence, and setval(null) would
        // abort the whole restore
        $this->assertStringNotContainsString("pg_get_serial_sequence('public.sessions', 'id')", $seed);
    }

    public function test_the_route_needs_a_super_admin_or_the_trigger_token(): void
    {
        $this->fakeCloudinary();
        config(['services.backup.token' => '']);

        $this->get('/backup/run')->assertForbidden();

        config(['services.backup.token' => 'nightly-secret']);

        $this->get('/backup/run?token=wrong')->assertForbidden();

        $response = $this->get('/backup/run?token=nightly-secret');
        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('folder', '4ftdshm54xvhks/backup_' . now()->setTimezone('Africa/Dar_es_Salaam')->format('Y-m-d'));

        // a signed-in super admin needs no token
        $admin = new User;
        $admin->forceFill(['id' => 1, 'name' => 'Super Admin', 'email' => 'admin@example.co.tz', 'role' => 'super_admin']);

        $this->be($admin)->get('/backup/run')->assertOk();
    }

    public function test_a_cloudinary_failure_is_reported_instead_of_claimed_as_success(): void
    {
        $this->fakeCloudinary(400);
        config(['services.backup.token' => 'nightly-secret']);

        $response = $this->get('/backup/run?token=nightly-secret');

        $response->assertStatus(500);
        $response->assertJsonPath('status', 'error');
        $this->assertStringContainsString('Invalid Signature', (string) $response->json('message'));
    }
}
