<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\DatabaseBackupService;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Super Admin's Backups page.
 *
 * What matters here is that the list tells the truth and that delete is
 * all-or-nothing: a backup is two files plus a row, so a delete that removes
 * only one file — or removes the row while a file survives — would leave the
 * page lying about what is still stored.
 */
class SuperAdminBackupsPageTest extends TestCase
{
    use RefreshDatabase;

    private const FOLDER = '4ftdshm54xvhks';

    /** A SupabaseService stand-in holding the backups table and recording writes. */
    private function supabaseStub(array $rows = []): SupabaseService
    {
        return new class($rows) extends SupabaseService
        {
            /** @var list<array{table: string, data: array}> */
            public array $inserted = [];

            /** @var list<array{table: string, data: array, conditions: array}> */
            public array $updated = [];

            /** @var list<array{table: string, conditions: array}> */
            public array $deleted = [];

            public function __construct(private array $rows)
            {
            }

            public function query(string $table, array $params = []): array
            {
                return $this->rows;
            }

            public function queryFresh(string $table, array $params = []): array
            {
                return $this->rows;
            }

            public function findOne(string $table, array $conditions, string $select = '*'): ?array
            {
                $date = (string) str_replace('eq.', '', (string) ($conditions['backup_date'] ?? ''));

                foreach ($this->rows as $row) {
                    if ((string) ($row['backup_date'] ?? '') === $date) {
                        return $row;
                    }
                }

                return null;
            }

            public function insert(string $table, array|object $data): ?array
            {
                $data = is_array($data) ? $data : (array) $data;
                $this->inserted[] = ['table' => $table, 'data' => $data];

                // the page lists what the table holds, so a write must be
                // visible to the next read in the same request
                if ($table === 'backups') {
                    $this->rows[] = $data;
                }

                return $data;
            }

            public function update(string $table, array|object $data, array $conditions): array
            {
                $this->updated[] = ['table' => $table, 'data' => (array) $data, 'conditions' => $conditions];

                return [(array) $data];
            }

            public function delete(string $table, array $conditions): bool
            {
                $this->deleted[] = ['table' => $table, 'conditions' => $conditions];

                return true;
            }
        };
    }

    private function admin(): User
    {
        $admin = new User;
        $admin->forceFill([
            'id' => 1,
            'supabase_id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@example.co.tz',
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        return $admin;
    }

    /** Cloudinary requests seen during the test (an array returned by value would lose them). */
    private array $requests = [];

    private function cloudinary(
        array $resources = [],
        array $destroy = ['result' => 'ok'],
        int $destroyStatus = 200,
        int $uploadStatus = 200
    ): void {
        Http::fake(function (Request $request) use ($resources, $destroy, $destroyStatus, $uploadStatus) {
            $this->requests[] = $request;

            if (str_contains($request->url(), '/resources/raw')) {
                return Http::response(['resources' => $resources], 200);
            }

            if (str_contains($request->url(), '/raw/destroy')) {
                return Http::response($destroy, $destroyStatus);
            }

            if (str_contains($request->url(), '/raw/upload')) {
                if ($uploadStatus !== 200) {
                    return Http::response(['error' => ['message' => 'Invalid Signature']], $uploadStatus);
                }

                $publicId = 'unknown';
                foreach ($request->data() as $part) {
                    if (($part['name'] ?? '') === 'public_id') {
                        $publicId = (string) $part['contents'];
                    }
                }

                return Http::response([
                    'public_id' => $publicId,
                    'secure_url' => 'https://res.cloudinary.com/test-cloud/raw/upload/v1/' . $publicId,
                    'bytes' => 1234,
                ], 200);
            }

            return Http::response('', 200);
        });

        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
        ]);
    }

    private function bind(SupabaseService $stub): SupabaseService
    {
        $this->app->instance(SupabaseService::class, $stub);

        return $stub;
    }

    private function row(string $date, array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'backup_date' => $date,
            'folder' => self::FOLDER . '/backup_' . $date,
            'schema_url' => 'https://res.cloudinary.com/test-cloud/raw/upload/' . self::FOLDER . '/backup_' . $date . '/schema_' . $date . '.sql',
            'schema_bytes' => 29741,
            'seed_url' => 'https://res.cloudinary.com/test-cloud/raw/upload/' . self::FOLDER . '/backup_' . $date . '/seed_' . $date . '.sql',
            'seed_bytes' => 789606,
            'tables_count' => 45,
            'rows_count' => 2635,
            'seconds' => 12.5,
            'status' => 'complete',
            'created_at' => $date . 'T00:00:09+00:00',
        ], $overrides);
    }

    public function test_the_page_lists_backups_newest_first_with_download_and_delete_actions(): void
    {
        $this->cloudinary();
        $this->bind($this->supabaseStub([
            $this->row('2026-10-29'),
            $this->row('2026-10-28', ['id' => 2]),
        ]));

        $response = $this->be($this->admin())->get('/super-admin/backups');

        $response->assertOk();
        $response->assertSee('29 Oct 2026');
        $response->assertSee('28 Oct 2026');

        // newest first: the date order the query asks for is what the page shows
        $this->assertLessThan(
            strpos($response->getContent(), '28 Oct 2026'),
            strpos($response->getContent(), '29 Oct 2026')
        );

        // both halves downloadable, delete posts a real DELETE
        $response->assertSee(route('super-admin.backups.download', ['2026-10-29', 'schema']), false);
        $response->assertSee(route('super-admin.backups.download', ['2026-10-29', 'seed']), false);
        $response->assertSee(route('super-admin.backups.destroy', '2026-10-29'), false);
        $this->assertSame(
            2,
            substr_count($response->getContent(), 'name="_method" value="DELETE"'),
            'every listed backup should offer a delete'
        );

        // and the warning says what delete really does
        $response->assertSee('Delete the backup for 2026-10-29 permanently?', false);
    }

    public function test_a_backup_cloudinary_has_but_the_table_does_not_is_imported_and_listed(): void
    {
        $this->cloudinary([
            [
                'public_id' => self::FOLDER . '/backup_2026-10-26/schema_2026-10-26.sql',
                'bytes' => 29741,
                'secure_url' => 'https://res.cloudinary.com/test-cloud/raw/upload/' . self::FOLDER . '/backup_2026-10-26/schema_2026-10-26.sql',
                'created_at' => '2026-10-26T21:00:05Z',
            ],
            [
                'public_id' => self::FOLDER . '/backup_2026-10-26/seed_2026-10-26.sql',
                'bytes' => 700000,
                'secure_url' => 'https://res.cloudinary.com/test-cloud/raw/upload/' . self::FOLDER . '/backup_2026-10-26/seed_2026-10-26.sql',
                'created_at' => '2026-10-26T21:00:09Z',
            ],
        ]);

        $stub = $this->bind($this->supabaseStub([]));

        $response = $this->be($this->admin())->get('/super-admin/backups');

        $response->assertOk();

        $this->assertCount(1, $stub->inserted, 'the folder Cloudinary has should have been recorded');
        $this->assertSame('backups', $stub->inserted[0]['table']);
        $this->assertSame('2026-10-26', $stub->inserted[0]['data']['backup_date']);
        $this->assertSame(700000, $stub->inserted[0]['data']['seed_bytes']);
        $this->assertSame(self::FOLDER . '/backup_2026-10-26', $stub->inserted[0]['data']['folder']);

        $response->assertSee('26 Oct 2026');
        $response->assertSee('found in Cloudinary');

        // the admin API needs the resource type in the path, not only in the
        // query: /resources/raw alone answers "Missing required parameter - type"
        $listing = collect($this->requests)->first(fn (Request $r) => str_contains($r->url(), '/resources/'));
        $this->assertNotNull($listing, 'Cloudinary should have been asked what it holds');
        $this->assertStringContainsString('/resources/raw/upload', $listing->url());
        $this->assertStringContainsString('prefix=4ftdshm54xvhks', $listing->url());
    }

    public function test_a_file_cloudinary_does_not_have_is_not_imported_from_a_stray_name(): void
    {
        $this->cloudinary([
            ['public_id' => self::FOLDER . '/notes.txt', 'bytes' => 10, 'secure_url' => '', 'created_at' => null],
            // file name and folder disagree: not a backup this app wrote
            ['public_id' => self::FOLDER . '/backup_2026-10-26/seed_2026-10-27.sql', 'bytes' => 10, 'secure_url' => '', 'created_at' => null],
        ]);

        $stub = $this->bind($this->supabaseStub([]));

        $this->be($this->admin())->get('/super-admin/backups')->assertOk();

        $this->assertSame([], $stub->inserted);
    }

    public function test_downloading_a_file_hands_it_over_as_an_attachment(): void
    {
        $url = 'https://res.cloudinary.com/test-cloud/raw/upload/' . self::FOLDER . '/backup_2026-10-29/seed_2026-10-29.sql';

        Http::fake([
            $url => Http::response("BEGIN;\nINSERT INTO public.branches_etc;\nCOMMIT;\n", 200),
        ]);

        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
        ]);

        $this->bind($this->supabaseStub([$this->row('2026-10-29')]));

        $response = $this->be($this->admin())->get('/super-admin/backups/2026-10-29/download/seed');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=seed_2026-10-29.sql');
        $this->assertStringContainsString('INSERT INTO public.branches_etc;', $response->streamedContent());
    }

    public function test_a_download_for_a_date_the_page_does_not_know_is_refused(): void
    {
        $this->cloudinary();
        $this->bind($this->supabaseStub([]));

        $this->be($this->admin())
            ->get('/super-admin/backups/2026-01-01/download/seed')
            ->assertRedirect(route('super-admin.backups.index'))
            ->assertSessionHas('error', 'There is no backup recorded for 2026-01-01.');
    }

    public function test_deleting_removes_both_files_and_only_then_the_row(): void
    {
        $this->cloudinary();
        $stub = $this->bind($this->supabaseStub([$this->row('2026-10-29')]));

        $response = $this->be($this->admin())->delete('/super-admin/backups/2026-10-29');

        $response->assertRedirect(route('super-admin.backups.index'));
        $response->assertSessionHas('success', 'The backup for 2026-10-29 has been deleted from Cloudinary and the database.');

        $destroyed = array_values(array_filter($this->requests, fn (Request $r) => str_contains($r->url(), '/raw/destroy')));

        $this->assertCount(2, $destroyed, 'both halves of the backup must be deleted');

        $ids = array_map(fn (Request $r) => (string) $r['public_id'], $destroyed);
        sort($ids);

        $this->assertSame([
            self::FOLDER . '/backup_2026-10-29/schema_2026-10-29.sql',
            self::FOLDER . '/backup_2026-10-29/seed_2026-10-29.sql',
        ], $ids);

        $this->assertSame([['table' => 'backups', 'conditions' => ['backup_date' => 'eq.2026-10-29']]], $stub->deleted);
    }

    public function test_a_refused_cloudinary_delete_keeps_the_row_and_reports_the_reason(): void
    {
        $this->cloudinary([], ['error' => ['message' => 'Invalid Signature']], 400);
        $stub = $this->bind($this->supabaseStub([$this->row('2026-10-29')]));

        $response = $this->be($this->admin())->delete('/super-admin/backups/2026-10-29');

        $response->assertRedirect(route('super-admin.backups.index'));
        $this->assertStringContainsString('Could not delete the backup for 2026-10-29', (string) session('error'));
        $this->assertStringContainsString('Invalid Signature', (string) session('error'));
        $this->assertSame(null, session('success'));

        // the row survives, so the page still shows a backup that is still there
        $this->assertSame([], $stub->deleted);
    }

    public function test_the_induce_button_takes_a_backup_immediately(): void
    {
        $this->cloudinary();
        $stub = $this->bind($this->supabaseStub([]));

        // the button is on the page, and it posts rather than links
        $page = $this->be($this->admin())->get('/super-admin/backups');
        $page->assertSee('Induce Backup');
        $page->assertSee(route('super-admin.backups.induce'), false);

        $today = now()->setTimezone('Africa/Dar_es_Salaam')->format('Y-m-d');

        $response = $this->be($this->admin())->post('/super-admin/backups/induce');

        $response->assertRedirect(route('super-admin.backups.index'));
        $this->assertStringContainsString("Backup for {$today} created", (string) session('success'));

        // today's row, pointing at today's folder — not tomorrow's
        $this->assertCount(1, $stub->inserted);
        $row = $stub->inserted[0]['data'];
        $this->assertSame('backups', $stub->inserted[0]['table']);
        $this->assertSame($today, $row['backup_date']);
        $this->assertSame('4ftdshm54xvhks/backup_' . $today, $row['folder']);

        // and both halves really were uploaded
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/raw/upload')
            && str_contains($r->body(), 'schema_' . $today . '.sql'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/raw/upload')
            && str_contains($r->body(), 'seed_' . $today . '.sql'));
    }

    public function test_an_induced_backup_cloudinary_rejects_is_reported_not_claimed(): void
    {
        $this->cloudinary([], ['result' => 'ok'], 200, 400);
        $this->bind($this->supabaseStub([]));

        $response = $this->be($this->admin())->post('/super-admin/backups/induce');

        $response->assertRedirect(route('super-admin.backups.index'));
        $this->assertStringContainsString('The backup failed', (string) session('error'));
        $this->assertStringContainsString('Invalid Signature', (string) session('error'));
        $this->assertSame(null, session('success'));
    }

    public function test_only_a_super_admin_can_open_the_page(): void
    {
        $this->cloudinary();
        $this->bind($this->supabaseStub([]));

        $cashier = new User;
        $cashier->forceFill([
            'id' => 9,
            'supabase_id' => 9,
            'name' => 'Amina',
            'email' => 'amina@example.co.tz',
            'role' => 'cashier',
            'status' => 'active',
        ]);

        $this->be($cashier)->get('/super-admin/backups')->assertForbidden();
        $this->be($cashier)->post('/super-admin/backups/induce')->assertForbidden();
    }
}
