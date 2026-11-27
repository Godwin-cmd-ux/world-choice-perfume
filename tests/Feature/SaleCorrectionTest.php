<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use App\Support\BusinessDay;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Same-day correction of completed sales.
 *
 * A paid sale may be put right only on its own business day. Every accepted
 * change reaches both admin notification feeds, and deleting a sale returns
 * the stock it consumed.
 */
class SaleCorrectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::clearCache();
    }

    public function test_a_completed_sale_can_be_edited_on_the_same_day(): void
    {
        $calls = [];
        $this->fakeCorrectionApi($calls, $this->sale($this->todayStamp()));

        $response = $this->put('/cashier/sales/5', [
            'customer_id' => '',
            'payment_method' => 'mobile_payment',
            'payment_summary' => '',
            'subtotal' => 90000,
            'discount' => 0,
            'notes' => 'Corrected the till entry',
        ]);

        $response->assertRedirect(route('cashier.sales.index'));
        $this->assertStringContainsString('updated', (string) session('success'));

        $patch = $this->findCall($calls, 'PATCH', '/rest/v1/sales?');
        $this->assertNotNull($patch, 'the sale update reached the database');
        $this->assertSame(90000.0, (float) ($patch['data']['total'] ?? 0));
        $this->assertSame('mobile_payment', $patch['data']['payment_method'] ?? null);

        $this->assertNotEmpty($this->findCall($calls, 'POST', '/rest/v1/audit_logs'));
        $adminAlert = $this->findCall($calls, 'POST', '/rest/v1/admin_notifications');
        $this->assertNotNull($adminAlert);
        $this->assertSame('sale_edited', $adminAlert['data']['type'] ?? null);

        $branchAlert = $this->findCall($calls, 'POST', '/rest/v1/notifications');
        $this->assertNotNull($branchAlert);
        $this->assertSame(77, $branchAlert['data']['user_id'] ?? null);
        $this->assertStringContainsString('sale_edited', (string) ($branchAlert['data']['type'] ?? ''));
    }

    public function test_a_sale_from_an_earlier_day_cannot_be_edited(): void
    {
        $calls = [];
        $yesterday = BusinessDay::stamp(BusinessDay::start()->subDay()->addHours(12));
        $this->fakeCorrectionApi($calls, $this->sale($yesterday));

        $response = $this->put('/cashier/sales/5', [
            'payment_method' => 'cash',
            'subtotal' => 1000,
            'discount' => 0,
        ]);

        $response->assertForbidden();
        $this->assertNull($this->findCall($calls, 'PATCH', '/rest/v1/sales?'));
        $this->assertNull($this->findCall($calls, 'POST', '/rest/v1/admin_notifications'));
    }

    public function test_an_unpaid_sale_cannot_be_deleted(): void
    {
        $calls = [];
        $sale = $this->sale($this->todayStamp());
        $sale['payment_status'] = 'pending';
        $this->fakeCorrectionApi($calls, $sale);

        $response = $this->delete('/cashier/sales/5');

        $response->assertForbidden();
        $this->assertNull($this->findCall($calls, 'DELETE', '/rest/v1/sales?'));
    }

    public function test_deleting_a_today_sale_restores_stock_and_notifies_both_admins(): void
    {
        $calls = [];
        $sale = $this->sale($this->todayStamp());
        $sale['items'] = [[
            'id' => 9,
            'sale_id' => 5,
            'product_id' => 8,
            'quantity' => 3,
            'unit_price' => 1000,
            'unit_cost' => 600,
            'total' => 3000,
        ]];
        $this->fakeCorrectionApi($calls, $sale);

        $response = $this->delete('/cashier/sales/5');

        $response->assertRedirect(route('cashier.sales.index'));
        $this->assertStringContainsString('deleted', (string) session('success'));

        $stockUpdate = $this->findCall($calls, 'PATCH', '/rest/v1/branch_stock?');
        $this->assertNotNull($stockUpdate, 'the sold quantity went back to branch stock');
        $this->assertSame(7, (int) ($stockUpdate['data']['quantity'] ?? 0));

        $this->assertNotNull($this->findCall($calls, 'DELETE', '/rest/v1/sale_items?'));
        $this->assertNotNull($this->findCall($calls, 'DELETE', '/rest/v1/sales?'));
        $this->assertSame('sale_deleted', ($this->findCall($calls, 'POST', '/rest/v1/admin_notifications')['data']['type'] ?? null));
        $this->assertNotNull($this->findCall($calls, 'POST', '/rest/v1/notifications'));
    }

    public function test_a_sale_of_another_cashier_is_not_correctable(): void
    {
        $calls = [];
        $sale = $this->sale($this->todayStamp());
        $sale['cashier_id'] = 99;
        $this->fakeCorrectionApi($calls, $sale);

        $this->put('/cashier/sales/5', [
            'payment_method' => 'cash',
            'subtotal' => 1000,
            'discount' => 0,
        ])->assertForbidden();

        $this->assertNull($this->findCall($calls, 'PATCH', '/rest/v1/sales?'));
    }

    public function test_cross_branch_monitoring_cannot_correct_a_sale(): void
    {
        $calls = [];
        $this->actingRole('cashier', 10); // Head Quarters cashier
        session(['cashier_cross_branch_id' => 11]);
        $this->fakeCorrectionApi($calls, $this->sale($this->todayStamp()), false);

        $payload = [
            'payment_method' => 'cash',
            'subtotal' => 1000,
            'discount' => 0,
        ];

        $this->put('/cashier/sales/5', $payload)->assertForbidden();
        $this->delete('/cashier/sales/5')->assertForbidden();

        $this->assertNull($this->findCall($calls, 'PATCH', '/rest/v1/sales?'));
        $this->assertNull($this->findCall($calls, 'DELETE', '/rest/v1/sales?'));
    }

    public function test_the_sales_list_only_offers_actions_for_todays_completed_sales(): void
    {
        $calls = [];
        $this->actingCashier();
        Http::fake(function (Request $request) use (&$calls) {
            $this->record($calls, $request);
            $url = urldecode($request->url());

            if (str_contains($url, '/rest/v1/sales?') && $request->method() === 'GET') {
                return Http::response([
                    $this->sale($this->todayStamp()),
                    array_merge($this->sale(BusinessDay::stamp(BusinessDay::start()->subDay()->addHours(12))), ['id' => 6, 'sale_number' => 'SAL-6']),
                ]);
            }

            return Http::response([], 200);
        });

        $html = (string) $this->get('/cashier/sales')->assertOk()->getContent();

        $this->assertStringContainsString('/cashier/sales/5/edit', $html);
        $this->assertStringNotContainsString('/cashier/sales/6/edit', $html);
    }

    public function test_every_role_sales_page_exposes_and_renders_the_correction_form(): void
    {
        $pages = [
            ['cashier', '/cashier/sales', '/cashier/sales/5/edit'],
            ['seller', '/seller/sales', '/seller/sales/5/edit'],
            ['branch_admin', '/branch-admin/sales', '/branch-admin/sales/5/edit'],
            ['customer_care', '/customer-care/sales', '/customer-care/sales/5/edit'],
            ['stock_manager', '/stock-manager/sales', '/stock-manager/sales/5/edit'],
        ];

        foreach ($pages as [$role, $indexPath, $editPath]) {
            SupabaseService::clearCache();
            $calls = [];
            $this->actingRole($role);
            $this->fakeSalesApi($calls, $this->sale($this->todayStamp()));

            $this->get($indexPath)->assertOk()->assertSee($editPath, false);

            // The same request cache must not answer the edit read with the
            // list's shape; clear it before opening the form.
            SupabaseService::clearCache();
            $this->get($editPath)->assertOk()->assertSee('SAL-5');
        }
    }

    private function todayStamp(): string
    {
        return BusinessDay::stamp(BusinessDay::now());
    }

    private function sale(string $createdAt): array
    {
        return [
            'id' => 5,
            'sale_number' => 'SAL-5',
            'branch_id' => 11,
            'cashier_id' => 31,
            'customer_id' => null,
            'subtotal' => 100000,
            'discount' => 0,
            'total' => 100000,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'payment_summary' => 'Cash 100,000',
            'notes' => null,
            'created_at' => $createdAt,
            'customer' => ['id' => 3, 'name' => 'Test Customer', 'phone' => '0712345678'],
            'cashier' => ['id' => 31, 'name' => 'Test Cashier'],
            'branch' => ['id' => 11, 'name' => 'Test Branch'],
            'items' => [[
                'id' => 9,
                'sale_id' => 5,
                'product_id' => 8,
                'quantity' => 1,
                'unit_price' => 100000,
                'unit_cost' => 60000,
                'total' => 100000,
                'product' => ['id' => 8, 'name' => 'Test Perfume'],
            ]],
        ];
    }

    private function actingCashier(): void
    {
        $this->actingRole('cashier');
    }

    private function actingRole(string $role, int $branchId = 11): void
    {
        // The suite runs against an in-memory SQLite database with no migrated
        // tables, so authenticate the same unsaved model the other sales tests
        // use instead of persisting a factory user.
        $user = new User;
        $user->forceFill([
            'id' => 14,
            'supabase_id' => 31,
            'name' => 'Test ' . ucfirst(str_replace('_', ' ', $role)),
            'email' => $role . '@example.co.tz',
            'role' => $role,
            'branch_id' => $branchId,
            'status' => 'active',
        ]);
        $this->be($user);
    }

    private function fakeSalesApi(array &$calls, array $sale): void
    {
        Http::fake(function (Request $request) use (&$calls, $sale) {
            $this->record($calls, $request);
            $url = urldecode($request->url());

            if ($request->method() === 'HEAD') {
                return Http::response('', 200, ['Content-Range' => '0-0/1']);
            }
            if (str_contains($url, '/rest/v1/sales?') && $request->method() === 'GET') {
                return Http::response([$sale]);
            }
            if (str_contains($url, '/rest/v1/customers?')) {
                return Http::response([['id' => 3, 'name' => 'Test Customer', 'phone' => '0712345678']]);
            }

            return Http::response([], 200);
        });
    }

    private function fakeCorrectionApi(array &$calls, array $sale, bool $authenticate = true): void
    {
        if ($authenticate) {
            $this->actingCashier();
        }

        Http::fake(function (Request $request) use (&$calls, $sale) {
            $this->record($calls, $request);
            $url = urldecode($request->url());

            if (str_contains($url, '/rest/v1/sales?') && $request->method() === 'GET') {
                return Http::response([$sale]);
            }
            if (str_contains($url, '/rest/v1/customers?')) {
                return Http::response([]);
            }
            if (str_contains($url, '/rest/v1/users?')) {
                return Http::response([['id' => 77, 'name' => 'Branch Admin']]);
            }
            if (str_contains($url, '/rest/v1/branch_stock?')) {
                return Http::response([['id' => 4, 'quantity' => 4]]);
            }
            if (str_contains($url, '/rest/v1/bottle_stock_movements?')) {
                return Http::response([]);
            }

            return Http::response([], 200);
        });
    }

    private function record(array &$calls, Request $request): void
    {
        $calls[] = [
            'method' => $request->method(),
            'url' => urldecode($request->url()),
            'data' => $request->data(),
        ];
    }

    private function findCall(array $calls, string $method, string $urlPart): ?array
    {
        foreach ($calls as $call) {
            if ($call['method'] === $method && str_contains($call['url'], $urlPart)) {
                return $call;
            }
        }

        return null;
    }
}
