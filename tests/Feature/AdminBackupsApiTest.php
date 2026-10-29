<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The app's Backups screen endpoints — JSON twins of /super-admin/backups.
 *
 * The important behaviour is the same as on the website: induced backups are
 * separate from the nightly one, and a delete removes both files before the
 * row, refusing to pretend when Cloudinary says no.
 */
class AdminBackupsApiTest extends TestCase
{
    private const ROOT = '4ftdshm54xvhks';

    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService's request-level cache outlives a single test.
        SupabaseService::clearCache();
    }

    private function token(string $role = 'super_admin'): string
    {
        return Crypt::encryptString(json_encode([
            'scope' => 'staff_session',
            'uid' => 1,
            'role' => $role,
            'iat' => time(),
        ]));
    }

    /** @return list<array<string, mixed>> */
    private function rows(): array
    {
        $automatic = [
            'id' => 1,
            'backup_date' => '2026-10-29',
            'kind' => 'automatic',
            'stamp' => '2026-10-29',
            'folder' => self::ROOT . '/backup_2026-10-29',
            'schema_url' => 'https://res.cloudinary.com/zcmci5mi/raw/upload/v1/' . self::ROOT . '/backup_2026-10-29/schema_2026-10-29.sql',
            'schema_bytes' => 29656,
            'seed_url' => 'https://res.cloudinary.com/zcmci5mi/raw/upload/v1/' . self::ROOT . '/backup_2026-10-29/seed_2026-10-29.sql',
            'seed_bytes' => 831341,
            'tables_count' => 45,
            'rows_count' => 2925,
            'seconds' => 2.01,
            'status' => 'complete',
            'created_at' => '2026-10-29T00:00:09+00:00',
        ];

        $induced = $automatic;
        $induced['id'] = 2;
        $induced['kind'] = 'induced';
        $induced['stamp'] = '2026-10-29_143501';
        $induced['folder'] = self::ROOT . '/induced/backup_2026-10-29_143501';
        $induced['schema_url'] = 'https://res.cloudinary.com/zcmci5mi/raw/upload/v1/' . $induced['folder'] . '/schema_2026-10-29_143501.sql';
        $induced['seed_url'] = 'https://res.cloudinary.com/zcmci5mi/raw/upload/v1/' . $induced['folder'] . '/seed_2026-10-29_143501.sql';
        $induced['rows_count'] = 2930;

        return [$automatic, $induced];
    }

    /**
     * The PostgREST + Cloudinary answers behind the endpoints.
     */
    private function fake(bool $cloudinaryAccepts = true, array $resources = []): void
    {
        $rows = $this->rows();

        Http::fake(function (Request $request) use ($rows, $cloudinaryAccepts, $resources) {
            $url = urldecode($request->url());

            if (str_contains($url, 'api.cloudinary.com')) {
                if (str_contains($url, '/resources/raw')) {
                    return Http::response(['resources' => $resources], 200);
                }

                if (str_contains($url, '/raw/destroy')) {
                    return $cloudinaryAccepts
                        ? Http::response(['result' => 'ok'], 200)
                        : Http::response(['error' => ['message' => 'Invalid Signature']], 401);
                }

                if (str_contains($url, '/raw/upload')) {
                    $publicId = 'unknown';
                    foreach ($request->data() as $part) {
                        if (($part['name'] ?? '') === 'public_id') {
                            $publicId = (string) $part['contents'];
                        }
                    }

                    return Http::response([
                        'public_id' => $publicId,
                        'secure_url' => 'https://res.cloudinary.com/zcmci5mi/raw/upload/v1/' . $publicId,
                        'bytes' => 1000,
                    ], 200);
                }

                return Http::response([], 200);
            }

            if (str_contains($url, '/rest/v1/users')) {
                return Http::response([[
                    'id' => 1,
                    'name' => 'Super Admin',
                    'email' => 'admin@example.co.tz',
                    'role' => 'super_admin',
                    'status' => 'active',
                ]], 200);
            }

            if (str_contains($url, '/rest/v1/backups')) {
                if ($request->method() === 'POST') {
                    return Http::response([['id' => 3]], 201);
                }

                if ($request->method() === 'DELETE') {
                    return Http::response('', 204);
                }

                // findOne() on the folder, used by delete/download
                if (preg_match('/folder=eq\.([^&]+)/', $url, $m)) {
                    $folder = $m[1];

                    return Http::response(array_values(array_filter($rows, fn ($row) => $row['folder'] === $folder)), 200);
                }

                return Http::response($rows, 200);
            }

            return Http::response([], 200);
        });

        config([
            'services.cloudinary.cloud_name' => 'zcmci5mi',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
        ]);
    }

    public function test_the_endpoints_require_a_super_admin_session(): void
    {
        $this->fake();

        $this->getJson('/api/admin/backups')->assertUnauthorized();
        $this->postJson('/api/admin/backups/induce')->assertUnauthorized();
        $this->deleteJson('/api/admin/backups/2026-10-29')->assertUnauthorized();
    }

    public function test_a_session_of_another_role_is_refused(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([[
                'id' => 1, 'name' => 'Amina', 'email' => 'amina@example.co.tz',
                'role' => 'cashier', 'status' => 'active',
            ]], 200),
        ]);

        $this->withHeaders(['X-Staff-Session' => $this->token('cashier')])
            ->getJson('/api/admin/backups')
            ->assertStatus(403);
    }

    public function test_the_catalogue_lists_induced_and_nightly_backups_newest_first(): void
    {
        $this->fake();

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->getJson('/api/admin/backups');

        $response->assertOk();

        // newest first: the induced one on that date comes before the night's
        $response->assertJsonPath('backups.0.stamp', '2026-10-29_143501');
        $response->assertJsonPath('backups.0.kind', 'induced');
        $response->assertJsonPath('backups.0.time', '14:35:01');
        $response->assertJsonPath('backups.0.label', '2026-10-29 at 14:35:01');
        $response->assertJsonPath('backups.1.stamp', '2026-10-29');
        $response->assertJsonPath('backups.1.kind', 'automatic');
        $response->assertJsonPath('backups.1.time', null);

        $response->assertJsonPath('total_stored', 2);
        $response->assertJsonPath('cloudinary_warning', null);
        $response->assertJsonPath('system.folder', self::ROOT);
        $response->assertJsonPath('system.timezone', 'Africa/Dar_es_Salaam');

        // the file URLs are there for the app to open
        $this->assertStringContainsString('/induced/backup_2026-10-29_143501/seed_2026-10-29_143501.sql', (string) $response->json('backups.0.seed_url'));
    }

    public function test_the_date_filters_narrow_the_catalogue(): void
    {
        $this->fake();

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->getJson('/api/admin/backups?date_from=2026-10-01&date_to=2026-10-10');

        $response->assertOk();
        $response->assertJsonCount(0, 'backups');
        $response->assertJsonPath('total_stored', 2, 'filtering must not hide what is stored');
    }

    public function test_inducing_a_backup_files_it_separately_and_reports_what_it_did(): void
    {
        $this->fake();

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->postJson('/api/admin/backups/induce');

        $response->assertOk();
        $response->assertJsonPath('backup.kind', 'induced');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{6}$/', (string) $response->json('backup.stamp'));
        $this->assertStringContainsString('Induced backup for', (string) $response->json('message'));

        // the row was written with kind + stamp, and the files went under induced/
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_contains($request->url(), '/rest/v1/backups')
            && str_contains((string) $request->body(), '"kind":"induced"'));

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/raw/upload')
            && str_contains((string) ($this->publicId($request) ?? ''), '/induced/backup_'));
    }

    public function test_deleting_removes_both_files_then_the_row(): void
    {
        $this->fake();

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->deleteJson('/api/admin/backups/2026-10-29_143501');

        $response->assertOk();
        $this->assertStringContainsString(
            'The backup for 2026-10-29 at 14:35:01 has been deleted from Cloudinary and the database.',
            (string) $response->json('message')
        );

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/raw/destroy')
            && $request['public_id'] === self::ROOT . '/induced/backup_2026-10-29_143501/schema_2026-10-29_143501.sql');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/raw/destroy')
            && $request['public_id'] === self::ROOT . '/induced/backup_2026-10-29_143501/seed_2026-10-29_143501.sql');

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_contains(urldecode($request->url()), '/rest/v1/backups?')
            && str_contains(urldecode($request->url()), 'folder=eq.' . self::ROOT . '/induced/backup_2026-10-29_143501'));
    }

    public function test_deleting_a_backup_that_is_not_there_is_a_404(): void
    {
        $this->fake();

        $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->deleteJson('/api/admin/backups/2026-01-01')
            ->assertNotFound()
            ->assertJsonPath('message', 'There is no backup recorded for 2026-01-01.');
    }

    public function test_a_refused_cloudinary_delete_is_a_502_and_keeps_the_row(): void
    {
        $this->fake(cloudinaryAccepts: false);

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->deleteJson('/api/admin/backups/2026-10-29');

        $response->assertStatus(502);
        $this->assertStringContainsString('It is still listed.', (string) $response->json('message'));

        Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/rest/v1/backups'));
    }

    /** The public_id of a multipart raw upload, or null for any other request. */
    private function publicId(Request $request): ?string
    {
        foreach ($request->data() as $part) {
            if (($part['name'] ?? '') === 'public_id') {
                return (string) $part['contents'];
            }
        }

        return null;
    }
}
