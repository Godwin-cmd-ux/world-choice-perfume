<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * GET /api/gd/qr-code — the app twin of the website's QR page, which offers
 * two codes: one general code for the shop and one per perfume that opens
 * that perfume's order page (graphic-designer/qr-code.blade.php).
 *
 * The product code is the one with a way to be wrong: it has to encode the
 * product's own address, on the deployed site, and the id from the client is
 * only ever matched against the catalogue the server read — so a printed
 * code can never point at an address the app invented.
 */
class MobileGdQrCodeApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService's request cache outlives a single test — clear it
        // so an earlier test's row cannot answer this one's read.
        SupabaseService::clearCache();
    }

    /** The catalogue the picker lists, plus the user row the session middleware re-reads. */
    private function fakeCatalogue(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/rest/v1/products')) {
                return Http::response([
                    ['id' => 7, 'name' => 'Reef 33', 'brand' => 'Reef', 'category' => 'Oil Fragrance', 'sex_category' => 'male'],
                    ['id' => 12, 'name' => 'Dune', 'brand' => 'Dior', 'category' => 'Brand Perfume', 'sex_category' => 'unisex'],
                ], 200);
            }

            if (str_contains($request->url(), '/rest/v1/users')) {
                return Http::response([[
                    'id' => 9,
                    'name' => 'Graphic Designer',
                    'email' => 'designer@example.co.tz',
                    'role' => 'graphic_designer',
                    'status' => 'active',
                    'branch_id' => null,
                ]], 200);
            }

            return Http::response([], 200);
        });
    }

    /** The token shape StaffAuthController issues after a password check. */
    private function gdSessionToken(): string
    {
        return Crypt::encryptString(json_encode([
            'scope' => 'staff_session',
            'uid' => 9,
            'role' => 'graphic_designer',
            'iat' => time(),
        ]));
    }

    public function test_the_endpoint_requires_a_staff_session(): void
    {
        $this->fakeCatalogue();

        $this->getJson('/api/gd/qr-code')->assertUnauthorized();
    }

    public function test_the_general_code_is_the_site_root_and_lists_the_catalogue(): void
    {
        $this->fakeCatalogue();

        $response = $this->withHeaders(['X-Staff-Session' => $this->gdSessionToken()])
            ->getJson('/api/gd/qr-code');

        $response->assertOk();
        $response->assertJsonPath('url', 'https://worldchoiceperfume.com/');
        $this->assertStringStartsWith('data:image/png;base64,', (string) $response->json('image'));

        $response->assertJsonCount(2, 'products');
        // Each row carries the product's own public address, built from the
        // named route — not the shop root.
        $response->assertJsonPath('products.0.url', 'https://worldchoiceperfume.com/products/7');
        $response->assertJsonPath('products.1.url', 'https://worldchoiceperfume.com/products/12');
        $response->assertJsonPath('products.0.name', 'Reef 33');
        $response->assertJsonPath('products.0.brand', 'Reef');
    }

    public function test_a_product_code_encodes_that_products_own_order_page(): void
    {
        $this->fakeCatalogue();

        $response = $this->withHeaders(['X-Staff-Session' => $this->gdSessionToken()])
            ->getJson('/api/gd/qr-code?product_id=12');

        $response->assertOk();
        $response->assertJsonPath('url', 'https://worldchoiceperfume.com/products/12');
        $this->assertStringStartsWith('data:image/png;base64,', (string) $response->json('image'));
    }

    public function test_an_unknown_product_is_refused_rather_than_encoded(): void
    {
        $this->fakeCatalogue();

        $this->withHeaders(['X-Staff-Session' => $this->gdSessionToken()])
            ->getJson('/api/gd/qr-code?product_id=999')
            ->assertNotFound();
    }

    /** A Supabase outage must not take the general code down with it. */
    public function test_the_general_code_survives_a_catalogue_it_cannot_read(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/rest/v1/users')) {
                return Http::response([[
                    'id' => 9,
                    'name' => 'Graphic Designer',
                    'email' => 'designer@example.co.tz',
                    'role' => 'graphic_designer',
                    'status' => 'active',
                    'branch_id' => null,
                ]], 200);
            }

            return Http::response('nope', 500);
        });

        $response = $this->withHeaders(['X-Staff-Session' => $this->gdSessionToken()])
            ->getJson('/api/gd/qr-code');

        $response->assertOk();
        $response->assertJsonPath('url', 'https://worldchoiceperfume.com/');
        $response->assertJsonCount(0, 'products');
        $this->assertStringStartsWith('data:image/png;base64,', (string) $response->json('image'));
    }
}
