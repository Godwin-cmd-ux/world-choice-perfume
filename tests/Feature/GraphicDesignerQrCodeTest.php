<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Graphic Designer QR page offers both codes it exists to produce: one
 * general code for the shop, and one code per perfume that opens that
 * perfume's order page.
 *
 * The product code is the one with a way to be wrong: it has to encode the
 * product's own address, on the deployed site. A code that points at the shop
 * instead (or at the host the designer happened to be browsing from) is
 * printed, stuck on a shelf and only discovered to be wrong by a customer with
 * a phone.
 */
class GraphicDesignerQrCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $cache = new \ReflectionProperty(SupabaseService::class, 'queryCache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);
    }

    /**
     * The catalogue the picker reads. Faked per test rather than in setUp:
     * Http::fake() merges stubs instead of replacing them, so an earlier
     * catch-all would quietly answer the "Supabase is down" test too.
     */
    private function fakeCatalogue(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/rest/v1/products')) {
                return Http::response([
                    ['id' => 7, 'name' => 'Reef 33', 'brand' => 'Reef', 'category' => 'Oil Fragrance', 'sex_category' => 'male'],
                    ['id' => 12, 'name' => 'Dune', 'brand' => 'Dior', 'category' => 'Brand Perfume', 'sex_category' => 'unisex'],
                ], 200);
            }

            return Http::response([], 200);
        });
    }

    private function actingAsGraphicDesigner(): static
    {
        $user = new User;
        $user->forceFill([
            'id' => 9,
            'supabase_id' => 9,
            'name' => 'Graphic Designer',
            'email' => 'designer@example.co.tz',
            'role' => 'graphic_designer',
            'branch_id' => null,
            'status' => 'active',
        ]);

        return $this->be($user);
    }

    public function test_the_page_offers_the_general_and_the_product_code(): void
    {
        $this->fakeCatalogue();

        $response = $this->actingAsGraphicDesigner()->get('/graphic-designer/qr-code');

        $response->assertOk();

        $response->assertSee('Create General QR Code');
        $response->assertSee('Create Product QR Code');

        // the general tab is the one that is open on arrival
        $response->assertSee('role="tab"', false);
        $response->assertSee('aria-selected="true"', false);
    }

    public function test_the_product_tab_lists_the_catalogue_with_each_order_page(): void
    {
        $this->fakeCatalogue();

        $response = $this->actingAsGraphicDesigner()->get('/graphic-designer/qr-code');

        $response->assertOk();

        $response->assertSee('Reef 33');
        $response->assertSee('Dune');

        // each row carries the product's own public address, not the shop's
        $response->assertSee('https://world-choice-perfume.onrender.com/products/7', false);
        $response->assertSee('https://world-choice-perfume.onrender.com/products/12', false);
    }

    public function test_the_general_code_still_points_at_the_site_root(): void
    {
        $this->fakeCatalogue();

        $response = $this->actingAsGraphicDesigner()->get('/graphic-designer/qr-code');

        $response->assertOk();
        $response->assertSee('https://world-choice-perfume.onrender.com/', false);
    }

    /** A Supabase outage must not take the general code down with it. */
    public function test_the_general_code_survives_a_catalogue_it_cannot_read(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $response = $this->actingAsGraphicDesigner()->get('/graphic-designer/qr-code');

        $response->assertOk();
        $response->assertSee('Create General QR Code');
        $response->assertSee('No active products to link to yet');
    }
}
