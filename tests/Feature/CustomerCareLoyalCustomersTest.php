<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Loyal Customers tab of the customer care client records screen.
 *
 * The tab (customer-care/customers/loyal, linked from the shared branch strip
 * on customer-care/customers) lists everyone flagged as loyal, and its
 * Create Loyal picker (…/loyal?create=1) searches the customers who are not
 * loyal yet and assigns one or more in a single POST. Membership lives in
 * loyal_customers and records who assigned it, so three things are pinned
 * here: the picker never offers someone already loyal, the POST skips
 * stale ids instead of failing the batch, and the insert is stamped with the
 * signed-in member — the audit trail the list's "by …" line reads.
 */
class CustomerCareLoyalCustomersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService caches query results in a static that outlives a
        // single test; without clearing it a previous test's answer could
        // serve this one's read and the fake would never be consulted.
        SupabaseService::clearCache();
    }

    public function test_the_clients_page_carries_the_loyal_tab(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        $response = $this->get('/customer-care/customers');

        $response->assertOk();
        $response->assertSee('All Customers');
        $response->assertSee('Loyal Customers');
        $response->assertSee(route('customer-care.customers.loyal', [], false));
    }

    public function test_the_loyal_tab_lists_the_flagged_clients_with_the_assigner(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        $response = $this->get('/customer-care/customers/loyal');

        $response->assertOk();
        $response->assertSee('Loyal Customers');
        $response->assertSee('Loyal Since');
        // The flagged client, her row, and who flagged her.
        $response->assertSee('Amina Yusuf');
        $response->assertSee('Neema Joseph');
        $response->assertSee(route('customer-care.customers.show', 5, false));
    }

    public function test_the_picker_offers_clients_who_are_not_loyal_yet(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        $response = $this->get('/customer-care/customers/loyal?create=1');

        $response->assertOk();
        $response->assertSee('Add selected as Loyal');
        // The two customers who may be ticked…
        $response->assertSee('value="6"', false);
        $response->assertSee('value="7"', false);
        // …and never the one already loyal: her row on the list carries a
        // remove form, not a checkbox, so her id appears nowhere as a value.
        $response->assertDontSee('value="5"', false);
    }

    public function test_the_picker_searches_the_available_clients(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        $response = $this->get('/customer-care/customers/loyal?create=1&cq=Hassan');

        $response->assertOk();
        $response->assertSee('Hassan Juma');
        $response->assertDontSee('Zawadi Ally');

        // The three-column search PostgREST cannot OR across (name/phone/
        // whatsapp) was actually asked for.
        Http::assertSent(fn (Request $request) => str_contains(urldecode($request->url()), 'name=ilike'));
        Http::assertSent(fn (Request $request) => str_contains(urldecode($request->url()), 'phone=ilike'));
        Http::assertSent(fn (Request $request) => str_contains(urldecode($request->url()), 'whatsapp=ilike'));
    }

    public function test_posting_the_picker_assigns_the_selected_clients(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        $response = $this->post('/customer-care/customers/loyal', ['customer_ids' => [6, 7]]);

        $response->assertRedirect(route('customer-care.customers.loyal'));
        $this->assertStringContainsString('Added 2', (string) session('success'));

        Http::assertSent(function (Request $request) {
            $body = urldecode($request->body());

            return $request->method() === 'POST'
                && str_contains($request->url(), '/rest/v1/loyal_customers')
                && str_contains($body, '"customer_id":6')
                && str_contains($body, '"customer_id":7')
                // Stamped with the signed-in member's Supabase id — the audit
                // trail behind the list's "by …" line.
                && str_contains($body, '"assigned_by":31');
        });
    }

    public function test_already_loyal_and_unknown_clients_are_skipped_not_failed(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        // 5 is already loyal, 999 does not exist, 6 is a fresh assign.
        $response = $this->post('/customer-care/customers/loyal', ['customer_ids' => [5, 999, 6]]);

        $response->assertRedirect(route('customer-care.customers.loyal'));
        $message = (string) session('success');
        $this->assertStringContainsString('Added 1', $message);
        $this->assertStringContainsString('2 skipped', $message);

        // Only the fresh id reached the insert.
        Http::assertSent(function (Request $request) {
            $body = $request->body();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/rest/v1/loyal_customers')
                && str_contains($body, '"customer_id":6')
                && ! str_contains($body, '"customer_id":5')
                && ! str_contains($body, '999');
        });
    }

    public function test_selecting_nobody_is_refused_before_anything_is_written(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        $response = $this->post('/customer-care/customers/loyal', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors('customer_ids');
        Http::assertNothingSent();
    }

    public function test_removing_a_client_revokes_the_flag(): void
    {
        $this->fakeSupabase();
        $this->loginAsCustomerCare();

        $response = $this->post('/customer-care/customers/loyal/5/remove');

        $response->assertRedirect(route('customer-care.customers.loyal'));
        $this->assertStringContainsString('Removed from loyal customers.', (string) session('success'));

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/rest/v1/loyal_customers')
            && str_contains(urldecode($request->url()), 'customer_id=eq.5'));
    }

    public function test_the_screen_is_for_signed_in_customer_care_only(): void
    {
        $this->fakeSupabase();

        // No session: the role middleware stands between the page and anyone
        // who knows the URL.
        $this->get('/customer-care/customers/loyal')->assertRedirect();
    }

    /**
     * Everything the two screens read, answered at the PostgREST level so the
     * real query strings (filters included) are what gets exercised. HEAD
     * requests are the count() probes — they carry the total in Content-Range.
     */
    private function fakeSupabase(): void
    {
        $branches = [
            ['id' => 10, 'name' => 'Head Quarters-Mikocheni', 'address' => 'Mikocheni', 'is_active' => true],
            ['id' => 11, 'name' => 'Mikocheni', 'address' => 'Mikocheni B', 'is_active' => true],
        ];
        $customers = [
            ['id' => 5, 'name' => 'Amina Yusuf', 'phone' => '0754111222', 'whatsapp' => '0754111222', 'email' => 'amina@example.co.tz', 'created_at' => '2026-09-01T09:00:00+03:00'],
            ['id' => 6, 'name' => 'Hassan Juma', 'phone' => '0713555666', 'whatsapp' => '0713555666', 'email' => null, 'created_at' => '2026-09-10T09:00:00+03:00'],
            ['id' => 7, 'name' => 'Zawadi Ally', 'phone' => '0786999888', 'whatsapp' => null, 'email' => null, 'created_at' => '2026-09-15T09:00:00+03:00'],
        ];
        $loyal = [
            ['id' => 101, 'customer_id' => 5, 'assigned_by' => 31, 'assigned_at' => '2026-09-20T10:00:00+03:00', 'created_at' => '2026-09-20T10:00:00+03:00'],
        ];
        $users = [
            ['id' => 31, 'name' => 'Neema Joseph'],
        ];
        $sales = [
            ['id' => 1, 'branch_id' => 11, 'customer_id' => 5, 'total' => 150000, 'created_at' => '2026-09-21T11:00:00+03:00'],
            ['id' => 2, 'branch_id' => 10, 'customer_id' => 6, 'total' => 90000, 'created_at' => '2026-09-22T11:00:00+03:00'],
        ];

        Http::fake(function (Request $request) use ($branches, $customers, $loyal, $users, $sales) {
            $url = urldecode($request->url());

            // count() probes with HEAD and reads the total from Content-Range.
            if ($request->method() === 'HEAD') {
                $total = str_contains($url, '/loyal_customers') ? count($loyal)
                    : (str_contains($url, '/customers') ? count($customers) : 0);

                return Http::response('', 200, ['Content-Range' => '0-' . max(0, $total - 1) . "/{$total}"]);
            }

            if (str_contains($url, '/rest/v1/branches')) {
                return Http::response($branches, 200);
            }

            if (str_contains($url, '/rest/v1/loyal_customers')) {
                if (in_array($request->method(), ['POST', 'DELETE'], true)) {
                    return Http::response($loyal, 200);
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
                // The three-column search: a row matches when the searched
                // term appears in any of its searchable columns.
                if (preg_match_all('/(?:name|phone|whatsapp)=ilike\.([^&]*)/', $url, $matches)) {
                    $terms = array_map(fn ($term) => mb_strtolower(trim($term, '*')), $matches[1]);

                    return Http::response(array_values(array_filter($customers, function ($c) use ($terms) {
                        foreach ($terms as $term) {
                            if ($term === '') continue;
                            foreach (['name', 'phone', 'whatsapp'] as $column) {
                                $value = mb_strtolower((string) ($c[$column] ?? ''));
                                if ($value !== '' && str_contains($value, $term)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    })), 200);
                }

                return Http::response($customers, 200);
            }

            if (str_contains($url, '/rest/v1/users')) {
                return Http::response($users, 200);
            }

            if (str_contains($url, '/rest/v1/sales')) {
                return Http::response($sales, 200);
            }

            return Http::response([], 200);
        });
    }

    /** The signed-in customer care member — supabase_id 31 is the audit stamp. */
    private function loginAsCustomerCare(): void
    {
        $user = new User;
        $user->forceFill([
            'id' => 14,
            'supabase_id' => 31,
            'name' => 'Neema Joseph',
            'email' => 'neema@example.co.tz',
            'role' => 'customer_care',
            'branch_id' => 11,
            'status' => 'active',
        ]);

        $this->be($user);
    }
}
