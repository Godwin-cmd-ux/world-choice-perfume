<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Editing an oil-fragrance product's quantity by hand is a bottling operation.
 *
 * A downward edit must put the freed bottles back into the exact bucket the
 * units came from; an upward edit must refuse to write anything if that bucket
 * cannot supply the extra bottles.
 */
class ProductStockEditBottleReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SupabaseService::clearCache();
    }

    public function test_reducing_quantity_restores_bottles_to_the_same_variant(): void
    {
        $captured = $this->fakeSupabase(20);
        $this->actingAsStockManager();

        $response = $this->patch('/stock-manager/product-stock/10', [
            'quantity' => 8,
            'unit_cost' => 1000,
            'selling_price' => 2000,
        ]);

        $response->assertRedirect(route('stock-manager.product-stock'));

        $bottlePatch = $this->patchFor($captured->updates, 'bottle_stock');
        $this->assertSame(22, $bottlePatch['quantity'], 'Two freed units must return two bottles.');

        $varietyPatch = $this->patchFor($captured->updates, 'branch_stock_varieties');
        $this->assertSame(8, $varietyPatch['quantity']);

        $movement = $this->firstInsert($captured->inserts, 'bottle_stock_movements');
        $this->assertSame('stock_in', $movement['type']);
        $this->assertSame(2, $movement['quantity']);
        $this->assertSame('50ml', $movement['volume']);
    }

    public function test_reducing_a_variety_quantity_restores_its_bottles(): void
    {
        $captured = $this->fakeSupabase(20);
        $this->actingAsStockManager();

        $response = $this->patch('/stock-manager/product-stock/varieties/30', [
            'quantity' => 7,
            'unit_cost' => 1000,
            'selling_price' => 2000,
        ]);

        $response->assertRedirect(route('stock-manager.product-stock'));

        $bottlePatch = $this->patchFor($captured->updates, 'bottle_stock');
        $this->assertSame(23, $bottlePatch['quantity'], 'Three freed variety units must return three bottles.');

        $movement = $this->firstInsert($captured->inserts, 'bottle_stock_movements');
        $this->assertSame('stock_in', $movement['type']);
        $this->assertSame(3, $movement['quantity']);
    }

    public function test_increasing_quantity_without_enough_bottles_is_rejected_before_any_write(): void
    {
        $captured = $this->fakeSupabase(1);
        $this->actingAsStockManager();

        $response = $this->patch('/stock-manager/product-stock/10', [
            'quantity' => 12,
            'unit_cost' => 1000,
            'selling_price' => 2000,
        ]);

        $response->assertSessionHasErrors(['quantity']);
        $this->assertSame([], $captured->updates, 'A failed pre-check must not write stock.');
    }

    private function fakeSupabase(int $bottleQuantity): object
    {
        $bottleStock = [['id' => 20, 'branch_id' => 8, 'volume' => '50ml', 'variant' => 'box_logo_yellow', 'quantity' => $bottleQuantity]];
        $varietyStock = [['id' => 30, 'branch_id' => 8, 'product_id' => 1, 'volume' => 50, 'variant' => 'box_logo_yellow', 'quantity' => 10]];
        // An object keeps the captured writes shared with the closure; a
        // returned array would be a copy that later mutations never reach.
        $captured = (object) ['updates' => [], 'inserts' => []];

        Http::fake(function ($request) use (&$bottleStock, $varietyStock, $captured) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();

            if ($method === 'GET' && str_contains($path, '/rest/v1/branch_stock_varieties')) {
                return Http::response($varietyStock, 200);
            }
            if ($method === 'GET' && str_contains($path, '/rest/v1/branch_stock')) {
                return Http::response([[
                    'id' => 10, 'branch_id' => 8, 'product_id' => 1,
                    'quantity' => 10, 'buying_cost' => 1000, 'selling_price' => 2000,
                ]], 200);
            }
            if ($method === 'GET' && str_contains($path, '/rest/v1/products')) {
                return Http::response([
                    ['id' => 1, 'name' => 'Test Perfume', 'brand' => 'Test', 'category' => 'Oil Fragrance'],
                ], 200);
            }
            if ($method === 'GET' && str_contains($path, '/rest/v1/bottle_stock')) {
                return Http::response($bottleStock, 200);
            }
            if ($method === 'GET' && str_contains($path, '/rest/v1/branches')) {
                return Http::response([
                    ['id' => 8, 'name' => 'Dodoma branch', 'category' => 'autonomous'],
                ], 200);
            }

            if ($method === 'PATCH') {
                $data = (array) $request->data();
                $table = str_contains($path, '/rest/v1/bottle_stock') ? 'bottle_stock'
                    : (str_contains($path, '/rest/v1/branch_stock_varieties') ? 'branch_stock_varieties'
                    : (str_contains($path, '/rest/v1/branch_stock') ? 'branch_stock' : 'other'));
                $captured->updates[] = ['table' => $table, 'data' => $data];

                if ($table === 'bottle_stock') {
                    $bottleStock[0]['quantity'] = (int) $data['quantity'];
                }

                return Http::response([['id' => 1]], 200);
            }

            if ($method === 'POST') {
                $data = (array) $request->data();
                $table = str_contains($path, '/rest/v1/bottle_stock_movements') ? 'bottle_stock_movements'
                    : (str_contains($path, '/rest/v1/stock_movements') ? 'stock_movements' : 'other');
                $captured->inserts[] = ['table' => $table, 'data' => $data];

                // PostgREST INSERT answers with a list of inserted rows.
                return Http::response([$data], 200);
            }

            return Http::response([], 200);
        });

        return $captured;
    }

    private function patchFor(array $updates, string $table): array
    {
        foreach ($updates as $update) {
            if ($update['table'] === $table) {
                return $update['data'];
            }
        }

        $this->fail("No PATCH captured for {$table}. Captured: " . json_encode($updates));
    }

    private function firstInsert(array $inserts, string $table): array
    {
        foreach ($inserts as $insert) {
            if ($insert['table'] === $table) {
                return $insert['data'];
            }
        }

        $this->fail("No INSERT captured for {$table}.");
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
