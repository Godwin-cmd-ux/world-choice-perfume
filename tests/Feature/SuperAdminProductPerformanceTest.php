<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperAdmin\ReportController;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The two percentage columns on the Product Performance report.
 *
 * "% Sold" is a sell-through rate: units sold divided by everything that was
 * available (units sold + units still in stock). "% of Total Sales" is the
 * product's share of the revenue taken across all products. Both are easy to
 * get subtly wrong — an off-by-one in the denominator, or a division by zero on
 * a product that never sold — so each one is pinned here.
 *
 * The controller builds its own Supabase client, so the fake is injected into
 * the private property and the action is driven directly, as the other report
 * tests in this suite do.
 */
class SuperAdminProductPerformanceTest extends TestCase
{
    private function controllerWith(SupabaseService $supabase): ReportController
    {
        $controller = (new \ReflectionClass(ReportController::class))->newInstanceWithoutConstructor();

        $property = new \ReflectionProperty(ReportController::class, 'supabase');
        $property->setAccessible(true);
        $property->setValue($controller, $supabase);

        return $controller;
    }

    /**
     * One sales row carrying two products, plus the stock rows that say how
     * many units of each are still on the shelf.
     *
     * Alpha: sold 30, 10 left  -> 40 available, 75% sold, 75% of revenue.
     * Beta:  sold 10, 90 left  -> 100 available, 10% sold, 25% of revenue.
     */
    private function supabaseWith(array $sales, array $stock = []): SupabaseService
    {
        return new class($sales, $stock) extends SupabaseService
        {
            /** @var array<int, array{table: string, params: array}> */
            public array $queries = [];

            public function __construct(private array $sales, private array $stock)
            {
            }

            public function query(string $table, array $params = []): array
            {
                $this->queries[] = ['table' => $table, 'params' => $params];

                if ($table === 'sales') {
                    return $this->sales;
                }

                if ($table !== 'branch_stock') {
                    return [];
                }

                // Honour the branch filter the controller adds when a branch
                // is selected.
                $filter = $params['branch_id'] ?? null;
                if (! is_string($filter) || ! str_starts_with($filter, 'eq.')) {
                    return $this->stock;
                }

                $wanted = (int) substr($filter, 3);

                return array_values(array_filter(
                    $this->stock,
                    fn ($row) => (int) ($row['branch_id'] ?? 0) === $wanted
                ));
            }
        };
    }

    private function twoProductSales(): array
    {
        return [[
            'id' => 1,
            'branch_id' => 1,
            'created_at' => '2026-10-03T10:00:00+00:00',
            'items' => [
                ['quantity' => 30, 'total' => 300000, 'product' => ['id' => 1, 'name' => 'Alpha']],
                ['quantity' => 10, 'total' => 100000, 'product' => ['id' => 2, 'name' => 'Beta']],
            ],
        ]];
    }

    private function request(array $overrides = []): Request
    {
        return Request::create('/super-admin/reports/product-performance', 'GET', array_merge([
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-31',
        ], $overrides));
    }

    public function test_sell_through_and_contribution_are_calculated_per_product(): void
    {
        $supabase = $this->supabaseWith($this->twoProductSales(), [
            ['branch_id' => 1, 'product_id' => 1, 'quantity' => 10],
            ['branch_id' => 1, 'product_id' => 2, 'quantity' => 90],
        ]);

        $data = $this->controllerWith($supabase)->productPerformance($this->request())->getData();
        $report = collect($data['report'])->keyBy('name');

        $alpha = $report['Alpha'];
        $this->assertSame(30, $alpha['total_sold']);
        $this->assertSame(10, $alpha['remaining']);
        $this->assertSame(40, $alpha['available'], 'available = sold + still in stock');
        $this->assertSame(75.0, $alpha['sell_through']);
        $this->assertSame(75.0, $alpha['contribution']);

        $beta = $report['Beta'];
        $this->assertSame(100, $beta['available']);
        $this->assertSame(10.0, $beta['sell_through']);
        $this->assertSame(25.0, $beta['contribution']);

        $this->assertSame(400000, $data['totalRevenue']);
    }

    public function test_contribution_shares_add_up_to_one_hundred_percent(): void
    {
        $supabase = $this->supabaseWith($this->twoProductSales(), [
            ['branch_id' => 1, 'product_id' => 1, 'quantity' => 10],
            ['branch_id' => 1, 'product_id' => 2, 'quantity' => 90],
        ]);

        $data = $this->controllerWith($supabase)->productPerformance($this->request())->getData();

        $this->assertSame(100.0, round(array_sum(array_column($data['report'], 'contribution')), 1));
    }

    public function test_a_product_with_no_stock_left_scores_full_sell_through(): void
    {
        $supabase = $this->supabaseWith($this->twoProductSales(), [
            ['branch_id' => 1, 'product_id' => 1, 'quantity' => 0],
            // Beta has no stock row at all — it is treated as zero remaining.
        ]);

        $report = collect(
            $this->controllerWith($supabase)->productPerformance($this->request())->getData()['report']
        )->keyBy('name');

        $this->assertSame(0, $report['Alpha']['remaining']);
        $this->assertSame(100.0, $report['Alpha']['sell_through']);
        $this->assertSame(100.0, $report['Beta']['sell_through']);
    }

    public function test_a_period_with_no_sales_reports_nothing_and_never_divides_by_zero(): void
    {
        $supabase = $this->supabaseWith([], [
            ['branch_id' => 1, 'product_id' => 1, 'quantity' => 25],
        ]);

        $data = $this->controllerWith($supabase)->productPerformance($this->request())->getData();

        $this->assertSame([], $data['report']);
        $this->assertSame(0, $data['totalRevenue']);
    }

    public function test_the_branch_filter_is_applied_to_both_sales_and_stock(): void
    {
        $supabase = $this->supabaseWith($this->twoProductSales(), [
            ['branch_id' => 1, 'product_id' => 1, 'quantity' => 10],
            ['branch_id' => 2, 'product_id' => 1, 'quantity' => 500],
        ]);

        $data = $this->controllerWith($supabase)->productPerformance($this->request(['branch_id' => 1]))->getData();

        $alpha = collect($data['report'])->firstWhere('name', 'Alpha');

        $this->assertSame(10, $alpha['remaining'], 'only branch 1 stock counts');
        $this->assertSame(75.0, $alpha['sell_through']);

        $stockQueries = array_values(array_filter(
            $supabase->queries,
            fn ($q) => $q['table'] === 'branch_stock'
        ));

        $this->assertNotEmpty($stockQueries);
        $this->assertSame('eq.1', $stockQueries[0]['params']['branch_id']);
    }
}
