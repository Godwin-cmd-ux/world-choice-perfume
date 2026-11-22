<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Adding a product from /stock-manager/products must create exactly one row.
 *
 * The old store method wrote once through SupabaseService and then mirrored a
 * second row with Eloquent. On the live database both writes land in the same
 * products table, so the catalogue grew by two for every single submit. The
 * form also guards against a second click while the first POST is in flight.
 */
class StockManagerProductCreateOnceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::clearCache();
        config([
            'services.supabase.url' => 'https://example.supabase.co',
            'services.supabase.anon_key' => 'anon-key',
            'services.supabase.service_role_key' => 'service-role-key',
        ]);
    }

    public function test_storing_a_product_writes_one_row_and_no_eloquent_mirror(): void
    {
        $this->fakeSupabase();
        $this->actingAsStockManager();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->post(route('stock-manager.products.store'), [
            'name' => 'Ocean Blue',
            'category' => 'Brand Perfume',
        ]);

        $response->assertRedirect(route('stock-manager.products.index'));

        $productWrites = collect(Http::recorded())->filter(function ($recorded) {
            $request = $recorded[0] ?? $recorded;

            return $request->method() === 'POST'
                && str_contains($request->url(), '/rest/v1/products');
        });
        $this->assertCount(1, $productWrites, 'One submit must send one product insert.');

        $eloquentProductInserts = collect(DB::getQueryLog())->filter(function ($query) {
            return str_starts_with(strtolower($query['query']), 'insert into "products"');
        });
        $this->assertCount(0, $eloquentProductInserts, 'The Eloquent mirror duplicates the row.');
    }

    public function test_the_create_form_disables_its_submit_button(): void
    {
        $this->fakeSupabase();
        $this->actingAsStockManager();

        $this->get(route('stock-manager.products.create'))
            ->assertOk()
            ->assertSee('id="product-create-form"', false)
            ->assertSee('id="create-product-button"', false)
            ->assertSee('button.disabled = true', false);
    }

    private function fakeSupabase(): void
    {
        Http::fake(function ($request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_contains($path, '/rest/v1/products') && $request->method() === 'POST') {
                return Http::response([[
                    'id' => 99,
                    'name' => 'Ocean Blue',
                    'category' => 'Brand Perfume',
                ]], 201);
            }

            return Http::response([], 200);
        });
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
