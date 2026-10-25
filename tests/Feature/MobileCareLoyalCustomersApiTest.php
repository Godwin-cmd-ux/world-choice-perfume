<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The app's Loyal Customers endpoints — JSON twins of the website's Loyal
 * tab (customer-care/customers/loyal): GET /api/care/loyal-customers lists
 * the memberships joined to their client, POST assigns one or more from the
 * picker, DELETE revokes the flag.
 *
 * The picker can hold a stale row (someone was flagged from the website
 * meanwhile), so the POST must skip already-loyal and unknown ids instead of
 * failing the batch, and every insert is stamped with the session's staff id
 * — the audit trail the list's assigned_by_name carries.
 */
class MobileCareLoyalCustomersApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService's request cache outlives a single test — clear it
        // so an earlier test's row cannot answer this one's read.
        SupabaseService::clearCache();
    }

    /** The token shape StaffAuthController issues after a password check. */
    private function careSessionToken(): string
    {
        return Crypt::encryptString(json_encode([
            'scope' => 'staff_session',
            'uid' => 31,
            'role' => 'customer_care',
            'iat' => time(),
        ]));
    }

    public function test_the_endpoints_require_a_staff_session(): void
    {
        $this->getJson('/api/care/loyal-customers')->assertUnauthorized();
        $this->postJson('/api/care/loyal-customers', ['customer_ids' => [6]])->assertUnauthorized();
        $this->deleteJson('/api/care/loyal-customers/6')->assertUnauthorized();
    }

    public function test_a_session_of_another_role_is_refused(): void
    {
        $this->fakeSupabase(role: 'seller');

        $this->withHeaders(['X-Staff-Session' => $this->careSessionToken()])
            ->getJson('/api/care/loyal-customers')
            ->assertStatus(403)
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_the_list_returns_joined_clients_with_the_assigner(): void
    {
        $this->fakeSupabase();

        $response = $this->withHeaders(['X-Staff-Session' => $this->careSessionToken()])
            ->getJson('/api/care/loyal-customers');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('loyal.0.customer_id', 5);
        $response->assertJsonPath('loyal.0.customer.name', 'Amina Yusuf');
        $response->assertJsonPath('loyal.0.assigned_by', 31);
        $response->assertJsonPath('loyal.0.assigned_by_name', 'Neema Joseph');
        $response->assertJsonPath('scope.role', 'customer_care');
    }

    public function test_the_picker_adds_several_at_once_stamped_and_skip_aware(): void
    {
        $this->fakeSupabase();

        // 6 and 7 are fresh; 5 is already loyal; 999 does not exist.
        $response = $this->withHeaders(['X-Staff-Session' => $this->careSessionToken()])
            ->postJson('/api/care/loyal-customers', ['customer_ids' => [6, 7, 5, 999]]);

        $response->assertOk();
        $response->assertJsonPath('added', 2);
        $response->assertJsonPath('skipped', 2);
        $response->assertJsonPath('ids', [6, 7]);
        $this->assertStringContainsString('Added 2', (string) $response->json('message'));

        Http::assertSent(function (Request $request) {
            $body = $request->body();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/rest/v1/loyal_customers')
                && str_contains($body, '"customer_id":6')
                && str_contains($body, '"customer_id":7')
                // Stamped with the session's staff row — the audit trail.
                && str_contains($body, '"assigned_by":31')
                // The stale and unknown ids never reach the insert.
                && ! str_contains($body, '"customer_id":5')
                && ! str_contains($body, '999');
        });
    }

    public function test_an_empty_selection_is_a_validation_error(): void
    {
        $this->fakeSupabase();

        $this->withHeaders(['X-Staff-Session' => $this->careSessionToken()])
            ->postJson('/api/care/loyal-customers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_ids']);
    }

    public function test_removing_revokes_the_flag(): void
    {
        $this->fakeSupabase();

        $response = $this->withHeaders(['X-Staff-Session' => $this->careSessionToken()])
            ->deleteJson('/api/care/loyal-customers/5');

        $response->assertOk();
        $this->assertStringContainsString('Removed from loyal customers.', (string) $response->json('message'));

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/rest/v1/loyal_customers')
            && str_contains(urldecode($request->url()), 'customer_id=eq.5'));
    }

    public function test_removing_a_client_who_is_not_loyal_is_a_404(): void
    {
        $this->fakeSupabase();

        $this->withHeaders(['X-Staff-Session' => $this->careSessionToken()])
            ->deleteJson('/api/care/loyal-customers/6')
            ->assertNotFound();
    }

    /**
     * The PostgREST answers behind the endpoints: the session's user row, the
     * one membership, and the clients it joins to. Filters are honoured where
     * the assertions depend on them (in./eq. on both tables).
     */
    private function fakeSupabase(string $role = 'customer_care'): void
    {
        $customers = [
            ['id' => 5, 'name' => 'Amina Yusuf', 'phone' => '0754111222', 'whatsapp' => '0754111222', 'email' => 'amina@example.co.tz', 'created_at' => '2026-09-01T09:00:00+03:00'],
            ['id' => 6, 'name' => 'Hassan Juma', 'phone' => '0713555666', 'whatsapp' => '0713555666', 'email' => null, 'created_at' => '2026-09-10T09:00:00+03:00'],
            ['id' => 7, 'name' => 'Zawadi Ally', 'phone' => '0786999888', 'whatsapp' => null, 'email' => null, 'created_at' => '2026-09-15T09:00:00+03:00'],
        ];
        $loyal = [
            ['id' => 101, 'customer_id' => 5, 'assigned_by' => 31, 'assigned_at' => '2026-09-20T10:00:00+03:00', 'created_at' => '2026-09-20T10:00:00+03:00'],
        ];

        Http::fake(function (Request $request) use ($customers, $loyal, $role) {
            $url = urldecode($request->url());

            if (str_contains($url, '/rest/v1/users')) {
                return Http::response([[
                    'id' => 31,
                    'name' => 'Neema Joseph',
                    'email' => 'neema@example.co.tz',
                    'phone' => '0755000111',
                    'role' => $role,
                    'status' => 'active',
                    'branch_id' => 11,
                ]], 200);
            }

            if (str_contains($url, '/rest/v1/branches')) {
                return Http::response([['id' => 11, 'name' => 'Mikocheni', 'address' => 'Mikocheni B']], 200);
            }

            if (str_contains($url, '/rest/v1/loyal_customers')) {
                if (in_array($request->method(), ['POST', 'DELETE'], true)) {
                    return Http::response($loyal, 200);
                }
                if (preg_match('/customer_id=eq\.(\d+)/', $url, $m)) {
                    return Http::response(array_values(array_filter($loyal, fn ($r) => (int) $r['customer_id'] === (int) $m[1])), 200);
                }
                if (preg_match('/customer_id=in\.\(([^)]*)\)/', $url, $m)) {
                    $wanted = array_map('intval', explode(',', $m[1]));

                    return Http::response(array_values(array_filter($loyal, fn ($r) => in_array((int) $r['customer_id'], $wanted, true))), 200);
                }

                return Http::response($loyal, 200);
            }

            if (str_contains($url, '/rest/v1/customers')) {
                if (preg_match('/id=in\.\(([^)]*)\)/', $url, $m)) {
                    $wanted = array_map('intval', explode(',', $m[1]));

                    return Http::response(array_values(array_filter($customers, fn ($c) => in_array((int) $c['id'], $wanted, true))), 200);
                }

                return Http::response($customers, 200);
            }

            return Http::response([], 200);
        });
    }
}
