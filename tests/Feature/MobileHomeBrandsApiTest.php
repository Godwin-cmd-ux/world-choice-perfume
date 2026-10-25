<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\HomeController;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * GET /api/brands — the JSON twin of the website's GET /brands page, which
 * the app's home "View All Brands" button opens (routes/web.php, the
 * `customer.brands` route).
 *
 * The contract worth pinning is that it runs the page's own query (active
 * brands, name order, logo included) and answers an empty list rather than a
 * 500 when Supabase cannot be read — same graceful fallback as /api/home.
 */
class MobileHomeBrandsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService caches query results in a static that outlives a
        // single test; without clearing it a previous test's row could answer
        // this one's read and the fake would never be consulted.
        SupabaseService::clearCache();
    }

    public function test_the_route_dispatches_to_the_home_controller(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'api/brands' && in_array('GET', $route->methods())
        );

        $this->assertNotNull($route);
        $this->assertSame(HomeController::class.'@brands', $route->getActionName());
    }

    public function test_it_asks_for_the_same_active_brands_as_the_page_and_returns_them(): void
    {
        Http::fake([
            '*/rest/v1/brands*' => Http::response([
                ['id' => 4, 'name' => 'Reef', 'logo_url' => 'https://cdn.test/reef.png'],
                ['id' => 9, 'name' => 'Dior', 'logo_url' => null],
            ], 200),
            '*' => Http::response([], 200),
        ]);

        $response = $this->getJson('/api/brands');

        $response->assertOk();
        $response->assertJsonCount(2, 'brands');
        $response->assertJsonPath('brands.0.id', 4);
        $response->assertJsonPath('brands.0.name', 'Reef');
        $response->assertJsonPath('brands.0.logo_url', 'https://cdn.test/reef.png');
        $response->assertJsonPath('brands.1.logo_url', null);

        // The same filters the website's /brands route sends.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/rest/v1/brands')
            && str_contains($request->url(), 'is_active=eq.true')
            && str_contains($request->url(), 'order=name.asc'));
    }

    /** A Supabase outage yields an empty list, not a 500. */
    public function test_a_supabase_outage_answers_an_empty_list(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $this->getJson('/api/brands')
            ->assertOk()
            ->assertJson(['brands' => []]);
    }
}
