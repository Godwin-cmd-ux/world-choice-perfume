<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A stock manager's sale must price and deduct the bottling they picked.
 *
 * Secreto 04 is stocked as two varieties: 50ml at TZS 50,000 and 100ml at
 * TZS 75,000, while the product-level price is 75,000. Picking the 50ml line
 * must charge 50,000, record the pick on the sale item, decrement the 50ml
 * bucket — and leave both the 100ml bucket alone and the product price out of
 * the maths.
 *
 * The branch_stock fake honours the requested relation fields, exactly like
 * PostgREST: if the controller forgets to ask for the product's category, the
 * answer arrives without it and the variety branch quietly disappears.
 */
class StockManagerVarietySaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::clearCache();
    }

    private function actingAsStockManager(int $supabaseId = 51, int $branchId = 8): static
    {
        $user = new User;
        $user->forceFill([
            'id' => $supabaseId,
            'supabase_id' => $supabaseId,
            'name' => 'Kinondoni Stock Manager',
            'email' => 'kinondoni.sm@example.co.tz',
            'role' => 'stock_manager',
            'branch_id' => $branchId,
            'status' => 'active',
        ]);

        return $this->be($user);
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

    /**
     * Only the relation fields the caller asked for, the way PostgREST does.
     *
     * @return array<int, string>
     */
    private function requestedProductFields(string $select): array
    {
        if (preg_match('/product:products\(([^)]*)\)/', $select, $m)) {
            return array_map('trim', explode(',', $m[1]));
        }

        return [];
    }

    private function fakeSupabase(): void
    {
        Http::fake(function (Request $request) {
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

    public function test_selecting_the_50ml_variety_charges_and_deducts_that_bottling_only(): void
    {
        $this->fakeSupabase();

        $response = $this->actingAsStockManager()->post('/stock-manager/sales', [
            'sale_type' => 'retail',
            'payment_mode' => 'single',
            'payments' => [
                ['method' => 'cash', 'amount' => 50000],
            ],
            'items' => [
                ['product_id' => 360, 'quantity' => 1, 'volume' => 50, 'variant' => 'box_logo_yellow'],
            ],
        ]);

        $response->assertRedirect(route('stock-manager.sales.show', 900));

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

        $movements = $this->postedBodies('stock_movements');
        $this->assertCount(1, $movements);
        $this->assertSame(-1, $movements[0][0]['quantity']);
        $this->assertSame(900, $movements[0][0]['reference_id']);
    }
}
