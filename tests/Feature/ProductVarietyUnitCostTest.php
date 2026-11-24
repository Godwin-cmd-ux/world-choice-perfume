<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every bottling volume of a product keeps its own unit cost.
 *
 * The product-level (branch_stock) cost is a leftover of the old single-cost
 * model and must never stand in for a volume's cost, or entering 50ml's cost
 * would make 100ml report the same money.
 */
class ProductVarietyUnitCostTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SupabaseService::clearCache();
    }

    public function test_the_stock_page_shows_each_volume_with_its_own_unit_cost(): void
    {
        $this->fakeSupabase([
            ['id' => 30, 'product_id' => 1, 'volume' => 50, 'variant' => 'box_logo_yellow', 'quantity' => 10, 'selling_price' => 22000, 'buying_cost' => 5000],
            ['id' => 31, 'product_id' => 1, 'volume' => 100, 'variant' => 'box_logo_yellow', 'quantity' => 5, 'selling_price' => 55000, 'buying_cost' => 8000],
        ]);
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/product-stock');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('TZS 5,000', $html, 'The 50ml volume shows its own cost.');
        $this->assertStringContainsString('TZS 8,000', $html, 'The 100ml volume shows its own cost.');
        $this->assertStringNotContainsString('TZS 15,500', $html, 'The product-level cost must not leak onto a volume.');
    }

    public function test_a_volume_without_a_cost_does_not_borrow_the_product_level_cost(): void
    {
        $this->fakeSupabase([
            ['id' => 30, 'product_id' => 1, 'volume' => 50, 'variant' => 'box_logo_yellow', 'quantity' => 10, 'selling_price' => 22000, 'buying_cost' => 5000],
            ['id' => 31, 'product_id' => 1, 'volume' => 100, 'variant' => 'box_logo_yellow', 'quantity' => 5, 'selling_price' => 55000, 'buying_cost' => 0],
        ]);
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/product-stock');

        $response->assertOk();
        $this->assertStringNotContainsString('TZS 15,500', $response->getContent());
    }

    public function test_saving_one_volume_unit_cost_never_touches_another_volume(): void
    {
        $captured = $this->fakeSupabase([
            ['id' => 30, 'product_id' => 1, 'volume' => 50, 'variant' => 'box_logo_yellow', 'quantity' => 10, 'selling_price' => 22000, 'buying_cost' => 5000],
            ['id' => 31, 'product_id' => 1, 'volume' => 100, 'variant' => 'box_logo_yellow', 'quantity' => 5, 'selling_price' => 55000, 'buying_cost' => 8000],
        ]);
        $this->actingAsStockManager();

        $response = $this->patch('/stock-manager/product-stock/varieties/30', [
            'quantity' => 10,
            'unit_cost' => 6000,
            'selling_price' => 22000,
        ]);

        $response->assertRedirect(route('stock-manager.product-stock'));

        $varietyPatches = array_values(array_filter(
            $captured->patches,
            fn ($p) => str_contains($p['path'], '/rest/v1/branch_stock_varieties')
        ));
        $this->assertCount(1, $varietyPatches, 'Only the edited volume is written.');
        $this->assertStringContainsString('id=eq.30', $varietyPatches[0]['url']);
        $this->assertSame(6000, $varietyPatches[0]['body']['buying_cost'] ?? null);
    }

    private function fakeSupabase(array $varietyRows): object
    {
        $captured = (object) ['patches' => []];

        Http::fake(function ($request) use ($varietyRows, $captured) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();

            if ($method === 'PATCH' && str_contains($path, '/rest/v1/branch_stock_varieties')) {
                $captured->patches[] = [
                    'path' => $path,
                    'url' => $request->url(),
                    'body' => json_decode($request->body(), true) ?: [],
                ];

                return Http::response([], 200);
            }

            if ($method === 'GET' && str_contains($path, '/rest/v1/branch_stock_varieties')) {
                return Http::response($varietyRows, 200);
            }

            if ($method === 'GET' && str_contains($path, '/rest/v1/branch_stock')) {
                return Http::response([[
                    'id' => 10,
                    'branch_id' => 8,
                    'product_id' => 1,
                    'quantity' => 15,
                    'buying_cost' => 15500,
                    'selling_price' => 22000,
                    'category' => 'Oil Fragrance',
                    'date_received' => '2026-11-20',
                    'created_at' => '2026-11-20 09:00:00+00',
                    'product' => [
                        'id' => 1,
                        'name' => 'Test Perfume',
                        'brand' => 'Test',
                        'category' => 'Oil Fragrance',
                        'images' => [],
                    ],
                ]], 200);
            }

            if ($method === 'GET' && str_contains($path, '/rest/v1/products')) {
                return Http::response([
                    ['id' => 1, 'name' => 'Test Perfume', 'brand' => 'Test', 'category' => 'Oil Fragrance'],
                ], 200);
            }

            if ($method === 'GET' && str_contains($path, '/rest/v1/branches')) {
                return Http::response([
                    ['id' => 8, 'name' => 'Dodoma branch', 'category' => 'autonomous'],
                ], 200);
            }

            return Http::response([], 200);
        });

        return $captured;
    }

    private function actingAsStockManager(): static
    {
        $user = new User;
        $user->forceFill([
            'id' => 700,
            'supabase_id' => 700,
            'name' => 'Stock Manager',
            'email' => 'stock.manager@example.co.tz',
            'role' => 'stock_manager',
            'branch_id' => 8,
            'status' => 'active',
        ]);

        return $this->be($user);
    }
}
