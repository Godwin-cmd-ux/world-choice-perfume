<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use App\Support\BusinessDay;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Calendar filtering on the cashier's sales list.
 *
 * The date inputs and quick ranges must narrow the database query itself, not
 * just hide rows after the capped list has been fetched.
 */
class CashierSalesCalendarFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::clearCache();
    }

    public function test_the_selected_date_range_reaches_the_sales_query(): void
    {
        $urls = [];
        $this->fakeSales($urls);

        $response = $this->get('/cashier/sales?date_from=2026-11-01&date_to=2026-11-15');

        $response->assertOk();
        $response->assertSee('TZS 250,000');
        $this->assertSalesWindow($urls, BusinessDay::localRangeFilter('2026-11-01', '2026-11-15'));
    }

    public function test_picking_a_from_date_shows_every_sale_of_that_day(): void
    {
        $urls = [];
        $this->fakeSales($urls);

        $response = $this->get('/cashier/sales?date_from=2026-11-10');

        $response->assertOk();
        $response->assertSee('Nov 10, 2026');
        $response->assertSee('value="2026-11-10"', false);
        $this->assertSalesWindow($urls, BusinessDay::localRangeFilter('2026-11-10', '2026-11-10'));
        $this->assertListLimit($urls, 1000);
    }

    public function test_picking_a_to_date_alone_also_filters_that_day(): void
    {
        $urls = [];
        $this->fakeSales($urls);

        $response = $this->get('/cashier/sales?date_to=2026-11-10');

        $response->assertOk();
        $this->assertSalesWindow($urls, BusinessDay::localRangeFilter('2026-11-10', '2026-11-10'));
        $this->assertListLimit($urls, 1000);
    }

    public function test_without_dates_the_window_is_today(): void
    {
        $urls = [];
        $this->fakeSales($urls);

        $response = $this->get('/cashier/sales');

        $response->assertOk();
        $response->assertSee('Today (');
        $this->assertSalesWindow($urls, BusinessDay::dayFilter());
        $this->assertListLimit($urls, 50);
    }

    public function test_all_dates_removes_the_window_and_labels_the_list(): void
    {
        $urls = [];
        $this->fakeSales($urls);

        $response = $this->get('/cashier/sales?all=1');

        $response->assertOk();
        $response->assertSee('All dates');
        foreach ($this->salesUrls($urls) as $url) {
            $this->assertStringNotContainsString('created_at=', $url);
        }
    }

    /** @param  array<int, string>  $urls  Collected request URLs, populated by reference. */
    private function assertSalesWindow(array $urls, string $window): void
    {
        $salesUrls = $this->salesUrls($urls);
        $this->assertNotEmpty($salesUrls);

        foreach ($salesUrls as $url) {
            $this->assertStringContainsString('created_at=' . $window, $url);
        }
    }

    /** @param  array<int, string>  $urls */
    private function assertListLimit(array $urls, int $limit): void
    {
        foreach ($this->salesUrls($urls) as $url) {
            $this->assertStringContainsString('limit=' . $limit, $url);
        }
    }

    /** @param  array<int, string>  $urls */
    private function salesUrls(array $urls): array
    {
        return array_values(array_filter(
            $urls,
            fn (string $url) => str_contains($url, '/rest/v1/sales?')
                && str_contains($url, 'cashier_id=eq.31')
                && str_contains($url, 'sale_items')
        ));
    }

    /**
     * Fake PostgREST and collect every URL so the window can be inspected.
     *
     * @param  array<int, string>  $urls  Collected request URLs, populated by reference.
     */
    private function fakeSales(array &$urls): void
    {
        Http::fake(function (Request $request) use (&$urls) {
            $url = urldecode($request->url());
            $urls[] = $url;

            if ($request->method() === 'HEAD') {
                return Http::response('', 200, ['Content-Range' => '0-0/1']);
            }

            if (str_contains($url, '/rest/v1/sales')) {
                return Http::response([[
                    'id' => 5,
                    'sale_number' => 'SAL-5',
                    'customer' => null,
                    'payment_method' => 'cash',
                    'items' => [],
                    'total' => 250000,
                    'created_at' => '2026-11-10T09:00:00',
                ]], 200);
            }

            return Http::response([], 200);
        });

        $user = new User;
        $user->forceFill([
            'id' => 14,
            'supabase_id' => 31,
            'name' => 'Neema Joseph',
            'email' => 'neema@example.co.tz',
            'role' => 'cashier',
            'branch_id' => 11,
            'status' => 'active',
        ]);
        $this->be($user);
    }
}
