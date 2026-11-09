<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Creating a client and opening the new record must both work.
 *
 * The client record screen builds its branch switcher from the customer's sales.
 * A brand-new client has none, so the switcher is empty — reading the first
 * branch unconditionally crashed the page with a 500. This pins the whole
 * create → redirect → view path for a client with no transactions.
 */
class CustomerCareCreateAndViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService caches query results in a static that outlives a
        // single test; clear it so the fake below answers every read.
        SupabaseService::clearCache();
    }

    public function test_a_new_client_record_opens_even_without_sales(): void
    {
        $customers = [
            ['id' => 5, 'name' => 'Amina Yusuf', 'phone' => '0754111222', 'whatsapp' => '0754111222', 'email' => null, 'created_at' => '2026-09-01T09:00:00+03:00'],
        ];

        Http::fake(function (Request $request) use (&$customers) {
            $url = urldecode($request->url());

            if ($request->method() === 'HEAD') {
                $total = str_contains($url, '/customers') ? count($customers) : 0;

                return Http::response('', 200, ['Content-Range' => '0-' . max(0, $total - 1) . "/{$total}"]);
            }

            if (str_contains($url, '/rest/v1/branches')) {
                return Http::response([
                    ['id' => 10, 'name' => 'HQ', 'address' => 'Mikocheni', 'is_active' => true],
                    ['id' => 11, 'name' => 'Mikocheni', 'address' => 'Mikocheni B', 'is_active' => true],
                ], 200);
            }

            if (str_contains($url, '/rest/v1/customers')) {
                if ($request->method() === 'POST') {
                    $row = (array) json_decode($request->body(), true);
                    $row['id'] = 6;
                    $customers[] = $row;

                    return Http::response([$row], 201);
                }

                if (preg_match('/id=eq\.(\d+)/', $url, $m)) {
                    return Http::response(array_values(array_filter($customers, fn ($c) => (int) $c['id'] === (int) $m[1])), 200);
                }

                return Http::response($customers, 200);
            }

            // A new client has no sales, no loyal flag, and no line items.
            return Http::response([], 200);
        });

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

        $this->get('/customer-care/customers/create')->assertOk();

        $response = $this->post('/customer-care/customers', [
            'name' => 'New Person',
            'phone' => '0712345678',
            'whatsapp' => '',
            'email' => '',
        ]);

        $response->assertRedirect('/customer-care/customers/6');

        $view = $this->get('/customer-care/customers/6');
        $view->assertOk();
        $view->assertSee('New Person');
    }
}
