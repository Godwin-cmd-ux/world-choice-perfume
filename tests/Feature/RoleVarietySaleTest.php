<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every role sells the bottling that was picked, at that bottling's price.
 *
 * The stock manager's own sale is pinned separately
 * (StockManagerVarietySaleTest). This file proves the same rule across the
 * rest of the roles: the four other web sale forms and the mobile checkout
 * shared by all five. Secreto 04 is 75,000 at product level, but the picked
 * 50ml bottling is 50,000 — a 25,000 + 25,000 split payment must therefore
 * settle, and only the 50ml bucket may move.
 */
class RoleVarietySaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::clearCache();
    }

    private function varietyRow(): array
    {
        return [
            'id' => 41,
            'branch_id' => 8,
            'product_id' => 360,
            'volume' => 50,
            'variant' => 'box_logo_yellow',
            'quantity' => 4,
            'selling_price' => 50000,
            'buying_cost' => 20000,
        ];
    }

    private function userRow(string $role, int $id): array
    {
        return [
            'id' => $id,
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $role.'@example.co.tz',
            'role' => $role,
            'status' => 'active',
            'branch_id' => 8,
        ];
    }

    /** Only the relation fields the caller asked for, the way PostgREST does. */
    private function requestedProductFields(string $select): array
    {
        if (preg_match('/product:products\(([^)]*)\)/', $select, $m)) {
            return array_map('trim', explode(',', $m[1]));
        }

        return [];
    }

    private function fakeSupabase(string $role, int $userId): void
    {
        Http::fake(function (Request $request) use ($role, $userId) {
            $url = $request->url();
            $method = $request->method();
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
            $select = (string) ($q['select'] ?? '');

            if ($method === 'POST') {
                $body = json_decode($request->body(), true) ?: [];

                if (str_contains($url, '/rest/v1/sales')) {
                    return Http::response([array_merge((array) $body, ['id' => 900])], 201);
                }

                return Http::response([], 201);
            }

            if ($method === 'PATCH' || $method === 'DELETE') {
                return Http::response([], 200);
            }

            if (str_contains($url, '/rest/v1/users')) {
                return Http::response([$this->userRow($role, $userId)], 200);
            }

            if (str_contains($url, '/rest/v1/products')) {
                return Http::response([['id' => 360, 'category' => 'Oil Fragrance']], 200);
            }

            if (str_contains($url, '/rest/v1/branch_stock_varieties')) {
                return Http::response([$this->varietyRow()], 200);
            }

            if (str_contains($url, '/rest/v1/branch_stock')) {
                $product = ['id' => 360, 'name' => 'Secreto 04', 'category' => 'Oil Fragrance'];
                $fields = $this->requestedProductFields($select);
                if ($fields !== []) {
                    $product = array_intersect_key($product, array_flip($fields));
                }

                return Http::response([[
                    'id' => 207,
                    'product_id' => 360,
                    'quantity' => 10,
                    'selling_price' => 75000,
                    'buying_cost' => 0,
                    'product' => $product,
                ]], 200);
            }

            return Http::response([], 200);
        });
    }

    private function actingAsRole(string $role, int $id = 51, int $branchId = 8): static
    {
        $user = new User;
        $user->forceFill([
            'id' => $id,
            'supabase_id' => $id,
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $role.'@example.co.tz',
            'role' => $role,
            'branch_id' => $branchId,
            'status' => 'active',
        ]);

        return $this->be($user);
    }

    /** The sale the user actually reported: 25,000 cash + 25,000 bank. */
    private function payload(): array
    {
        return [
            'sale_type' => 'retail',
            'payment_mode' => 'multi',
            'payments' => [
                ['method' => 'cash', 'amount' => 25000],
                ['method' => 'bank_transfer', 'amount' => 25000],
            ],
            'items' => [
                ['product_id' => 360, 'quantity' => 1, 'volume' => 50, 'variant' => 'box_logo_yellow'],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function postedBodies(string $table): array
    {
        $bodies = [];

        foreach (Http::recorded() as [$request]) {
            if ($request->method() !== 'POST' || ! str_ends_with($request->url(), "/rest/v1/{$table}")) {
                continue;
            }

            $decoded = json_decode($request->body(), true);
            if (is_array($decoded)) {
                $bodies[] = $decoded;
            }
        }

        return $bodies;
    }

    /** @return array<int, array<string, mixed>> */
    private function patchedBodies(string $table): array
    {
        $bodies = [];

        foreach (Http::recorded() as [$request]) {
            if ($request->method() !== 'PATCH') {
                continue;
            }

            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            if (! str_ends_with($path, "/rest/v1/{$table}")) {
                continue;
            }

            $decoded = json_decode($request->body(), true);
            if (is_array($decoded)) {
                $bodies[] = $decoded;
            }
        }

        return $bodies;
    }

    private function assertThePickWasChargedAndDeducted(): void
    {
        $sales = $this->postedBodies('sales');
        $this->assertCount(1, $sales);
        $this->assertEquals(50000, $sales[0]['total']);
        $this->assertEquals(50000, $sales[0]['subtotal']);

        $items = $this->postedBodies('sale_items');
        $this->assertCount(1, $items);
        $this->assertEquals(50000, $items[0][0]['unit_price']);
        $this->assertEquals(20000, $items[0][0]['unit_cost']);
        $this->assertSame(50, $items[0][0]['volume']);
        $this->assertSame('box_logo_yellow', $items[0][0]['variant']);

        $stockPatches = $this->patchedBodies('branch_stock');
        $this->assertCount(1, $stockPatches);
        $this->assertSame(9, $stockPatches[0]['quantity']);

        // The picked bucket drops by the sold unit; nothing else is written.
        $varietyPatches = $this->patchedBodies('branch_stock_varieties');
        $this->assertCount(1, $varietyPatches);
        $this->assertSame(3, $varietyPatches[0]['quantity']);
    }

    public function test_the_cashier_sale_charges_and_deducts_the_picked_bottling(): void
    {
        $this->fakeSupabase('cashier', 51);

        $response = $this->actingAsRole('cashier')->post('/cashier/sales', $this->payload());

        $response->assertRedirect(route('cashier.sales.show', 900));
        $this->assertThePickWasChargedAndDeducted();
    }

    public function test_the_seller_sale_charges_and_deducts_the_picked_bottling(): void
    {
        $this->fakeSupabase('seller', 52);

        $response = $this->actingAsRole('seller', 52)->post('/seller/sales', $this->payload());

        $response->assertRedirect(route('seller.sales.show', 900));
        $this->assertThePickWasChargedAndDeducted();
    }

    public function test_the_branch_admin_sale_charges_and_deducts_the_picked_bottling(): void
    {
        $this->fakeSupabase('branch_admin', 53);

        $response = $this->actingAsRole('branch_admin', 53)->post('/branch-admin/sales', $this->payload());

        $response->assertRedirect(route('branch-admin.sales.show', 900));
        $this->assertThePickWasChargedAndDeducted();
    }

    public function test_the_customer_care_sale_charges_and_deducts_the_picked_bottling(): void
    {
        $this->fakeSupabase('customer_care', 54);

        $response = $this->actingAsRole('customer_care', 54)->post('/customer-care/sales', $this->payload());

        $response->assertRedirect(route('customer-care.sales.show', 900));
        $this->assertThePickWasChargedAndDeducted();
    }

    public function test_the_mobile_checkout_charges_and_deducts_the_picked_bottling(): void
    {
        $this->fakeSupabase('cashier', 51);

        // The app's staff token: a pointer only — the middleware re-reads the
        // user row from the database, which the fake answers with the cashier.
        $token = Crypt::encryptString(json_encode([
            'scope' => 'staff_session',
            'uid' => 51,
            'iat' => time(),
        ]));

        $response = $this->withHeaders(['X-Staff-Session' => $token])
            ->postJson('/api/cashier/sales', $this->payload());

        $response->assertOk();
        $this->assertStringContainsString('completed successfully', (string) $response->json('message'));
        $this->assertThePickWasChargedAndDeducted();
    }
}
