<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The stock manager's own numbers.
 *
 * Three things are pinned here, all of them about whose stock and whose sales
 * a screen is describing:
 *
 *  - the Total Product Units card counts UNITS, not product types, with the
 *    type count alongside so the two cannot be confused;
 *  - the low-stock count opens the list of the products it counted, each with
 *    a Stock In way in;
 *  - a sales figure on this account means the sales THIS manager committed,
 *    never the branch's takings.
 *
 * The controller builds its own Supabase client over HTTP, so the project is
 * faked per table.
 */
class StockManagerDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService keeps its query cache in a static, which outlives a
        // single test. Clear it so one test's rows cannot answer the next one.
        $cache = new \ReflectionProperty(SupabaseService::class, 'queryCache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);
    }

    /** Two products: Reef 33 with 3 units (low) and Dune with 40 (fine). */
    private function stockRows(): array
    {
        return [
            [
                'id' => 1, 'product_id' => 1, 'quantity' => 3, 'selling_price' => 1500,
                'category' => 'Brand Perfume',
                'product' => ['id' => 1, 'name' => 'Reef 33', 'brand' => 'Reef', 'category' => 'Brand Perfume'],
            ],
            [
                'id' => 2, 'product_id' => 2, 'quantity' => 40, 'selling_price' => 2000,
                'category' => 'Brand Perfume',
                'product' => ['id' => 2, 'name' => 'Dune', 'brand' => 'Dior', 'category' => 'Brand Perfume'],
            ],
        ];
    }

    /**
     * @param array $stock     rows the branch_stock queries answer with
     * @param array $sales     rows the sales queries answer with
     * @param array $products  rows the products query answers with
     */
    private function fakeSupabase(array $stock, array $sales = [], array $products = []): void
    {
        Http::fake(function ($request) use ($stock, $sales, $products) {
            $url = $request->url();

            if (str_contains($url, '/rest/v1/branch_stock')) {
                // The low-stock page filters at the source; honour it so the
                // test proves the filter is actually sent.
                $rows = $stock;
                if (str_contains($url, 'quantity=lte.5')) {
                    $rows = array_values(array_filter($rows, fn ($r) => ($r['quantity'] ?? 0) <= 5));
                }

                return Http::response($rows, 200);
            }

            if (str_contains($url, '/rest/v1/sales')) {
                return Http::response($sales, 200);
            }

            if (str_contains($url, '/rest/v1/products')) {
                return Http::response($products, 200);
            }

            if (str_contains($url, '/rest/v1/branches')) {
                return Http::response([
                    ['id' => 8, 'name' => 'Dodoma branch', 'category' => 'autonomous'],
                ], 200);
            }

            return Http::response([], 200);
        });
    }

    private function actingAsStockManager(int $supabaseId = 700, int $branchId = 8): static
    {
        $user = new User;
        $user->forceFill([
            'id' => $supabaseId,
            'supabase_id' => $supabaseId,
            'name' => 'Stock Manager',
            'email' => 'stock.manager@example.co.tz',
            'role' => 'stock_manager',
            'branch_id' => $branchId,
            'status' => 'active',
        ]);

        return $this->be($user);
    }

    public function test_the_total_products_card_counts_units_and_shows_the_type_count(): void
    {
        $this->fakeSupabase($this->stockRows());
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/dashboard');

        $response->assertOk();
        $response->assertSee('Total Product Units');

        // 3 + 40 units, across 2 products — not "2".
        $response->assertSee('43');
        $response->assertSee('Across 2 product(s)');
    }

    public function test_the_low_stock_count_links_to_the_low_stock_list(): void
    {
        $this->fakeSupabase($this->stockRows());
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/dashboard');

        $response->assertSee('1 product(s) low stock');
        $response->assertSee(route('stock-manager.product-stock.low-stock'), false);
    }

    public function test_the_low_stock_page_lists_only_the_products_at_or_below_the_threshold(): void
    {
        $this->fakeSupabase($this->stockRows());
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/product-stock/low-stock');

        $response->assertOk();
        $response->assertSee('Reef 33');
        $response->assertDontSee('Dune');
        $response->assertSee('needs restocking');

        // The filter is sent to the database rather than applied in PHP, so a
        // large catalogue never travels the wire whole.
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/rest/v1/branch_stock')
            && str_contains($request->url(), 'quantity=lte.5'));
    }

    public function test_every_low_stock_row_offers_a_stock_in_that_carries_the_product(): void
    {
        $this->fakeSupabase($this->stockRows());
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/product-stock/low-stock');

        $response->assertSee('Stock In');
        $response->assertSee('product_id=1');
    }

    public function test_the_stock_in_form_arrives_with_the_product_already_chosen(): void
    {
        $this->fakeSupabase([], [], [
            ['id' => 1, 'name' => 'Reef 33', 'brand' => 'Reef', 'category' => 'Brand Perfume'],
        ]);
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/product-stock/entry?product_id=1');

        $response->assertOk();

        // The category has to be preselected as well, or the product list is
        // filtered down to nothing and the choice is lost.
        $response->assertSee('<option value="Brand Perfume" selected>', false);
        $response->assertSee('<option value="1" data-category="Brand Perfume" selected>', false);
    }

    public function test_the_dashboard_sales_card_counts_only_this_managers_own_sales(): void
    {
        // The manager's own two sales today.
        $this->fakeSupabase($this->stockRows(), [
            ['total' => 5000, 'cashier_id' => 700],
            ['total' => 2500, 'cashier_id' => 700],
        ]);
        $this->actingAsStockManager(700);

        $response = $this->get('/stock-manager/dashboard');

        $response->assertSee('My Sales Today');
        $response->assertSee('TZS 7,500');

        // And the query asks for this user specifically, so another person's
        // takings can never be counted in.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/rest/v1/sales')
            && str_contains($request->url(), 'cashier_id=eq.700'));
    }

    public function test_the_sales_page_is_narrowed_to_the_signed_in_manager(): void
    {
        $this->fakeSupabase($this->stockRows());
        $this->actingAsStockManager(700);

        $response = $this->get('/stock-manager/sales');

        $response->assertOk();

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/rest/v1/sales')
            && str_contains($request->url(), 'cashier_id=eq.700'));
    }

    public function test_a_super_admin_browsing_the_sales_page_is_not_narrowed_to_one_person(): void
    {
        $this->fakeSupabase($this->stockRows());

        $user = new User;
        $user->forceFill([
            'id' => 1,
            'supabase_id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@example.co.tz',
            'role' => 'super_admin',
            'branch_id' => 8,
            'status' => 'active',
        ]);
        $this->be($user);

        $this->get('/stock-manager/sales')->assertOk();

        // Overseeing, not selling: the branch-wide listing is left intact.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/v1/sales')
            && str_contains($request->url(), 'cashier_id=eq.'));
    }
}
