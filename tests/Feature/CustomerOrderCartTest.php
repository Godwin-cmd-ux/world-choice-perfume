<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The website's "choose more products" loop.
 *
 * The order page used to list the whole catalogue and hide the order in a
 * single submit. Now it is about the one perfume the customer tapped, and a
 * second button parks that selection in a cart and returns to the shop, so an
 * order can be built from several products without ever leaving the shop flow.
 *
 * Pinned here:
 *
 *  1. "Choose more products" stores the line (with its size and the hour's
 *     price) in the session cart and redirects to the shop, where the cart is
 *     visible with a checkout link;
 *  2. the order page shows the picked perfume's quantity card and the cart —
 *     never the catalogue — with both actions available;
 *  3. placing the order sends every line and empties the cart.
 *
 * PostgREST is faked at the HTTP layer, applying the `eq.` filters a real
 * PostgREST would, exactly as CustomerSizeOrderTest does.
 */
class CustomerOrderCartTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private const VARIETY_ROWS = [
        ['id' => 1, 'branch_id' => 3, 'product_id' => 7, 'volume' => 50, 'variant' => 'box_logo_yellow', 'quantity' => 2, 'selling_price' => 45000],
        ['id' => 2, 'branch_id' => 3, 'product_id' => 7, 'volume' => 50, 'variant' => 'no_box', 'quantity' => 3, 'selling_price' => 45000],
        ['id' => 3, 'branch_id' => 3, 'product_id' => 7, 'volume' => 30, 'variant' => 'plain', 'quantity' => 4, 'selling_price' => 30000],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService caches query results in a static, which would leak
        // from one test into the next.
        SupabaseService::clearCache();
    }

    /**
     * One branch with two perfumes: Reef 33 bottled in 30ml and 50ml, Oud
     * Royale sold as a plain bottle, plus a third perfume nobody asked for.
     */
    private function fakeSupabase(?array &$writes = null): void
    {
        $reef = ['id' => 7, 'name' => 'Reef 33', 'description' => 'A woody floral', 'brand' => 'Reef', 'category' => 'Oil Fragrance', 'sex_category' => 'unisex', 'fundamental_ingredient' => 'Wood', 'is_active' => true, 'images' => []];
        $oud = ['id' => 8, 'name' => 'Oud Royale', 'description' => 'Deep oud', 'brand' => 'Lattafa', 'category' => 'Brand Perfume', 'sex_category' => 'male', 'fundamental_ingredient' => 'Oud', 'is_active' => true, 'images' => []];
        $unasked = ['id' => 9, 'name' => 'Never Listed', 'description' => '', 'brand' => 'Nope', 'category' => 'Brand Perfume', 'sex_category' => 'female', 'fundamental_ingredient' => 'Floral', 'is_active' => true, 'images' => []];

        $tables = [
            'products' => [$reef, $oud, $unasked],
            'branches' => [
                ['id' => 3, 'name' => 'Kariakoo', 'address' => 'Kariakoo Market', 'latitude' => -6.8, 'longitude' => 39.2],
            ],
            'branch_stock' => [
                ['id' => 11, 'branch_id' => 3, 'product_id' => 7, 'quantity' => 5, 'selling_price' => 50000, 'product' => $reef],
                ['id' => 12, 'branch_id' => 3, 'product_id' => 8, 'quantity' => 4, 'selling_price' => 65000, 'product' => $oud],
                ['id' => 13, 'branch_id' => 3, 'product_id' => 9, 'quantity' => 4, 'selling_price' => 70000, 'product' => $unasked],
            ],
            'branch_stock_varieties' => self::VARIETY_ROWS,
            'customers' => [],
        ];

        Http::fake(function ($request) use ($tables, &$writes) {
            $url = $request->url();
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            $table = substr($path, strrpos($path, '/rest/v1/') + strlen('/rest/v1/'));
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

            if ($request->method() === 'POST') {
                $body = json_decode($request->body(), true);
                $body = is_array($body) ? $body : [];
                $records = ($body !== [] && array_is_list($body)) ? $body : [$body];

                $created = [];
                foreach ($records as $record) {
                    if ($writes !== null) {
                        $writes[$table][] = $record;
                    }
                    $created[] = array_merge($record, ['id' => 99]);
                }

                return Http::response($created, 201);
            }

            $rows = $tables[$table] ?? [];
            $rows = array_values(array_filter($rows, function ($row) use ($query) {
                foreach ($query as $key => $value) {
                    if (! is_string($value) || ! preg_match('/^eq\.(.*)$/', $value, $matches)) {
                        continue;
                    }
                    if (! array_key_exists($key, $row)) {
                        continue;
                    }
                    if ((string) $row[$key] !== $matches[1]) {
                        return false;
                    }
                }

                return true;
            }));

            if (isset($query['limit'])) {
                $rows = array_slice($rows, 0, (int) $query['limit']);
            }

            return Http::response($rows, 200);
        });
    }

    public function test_choose_more_products_parks_the_line_and_returns_to_the_shop(): void
    {
        $this->fakeSupabase();

        $response = $this->post('/orders/cart', [
            'branch_id' => 3,
            'current_index' => 0,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [
                ['product_id' => 7, 'quantity' => 2, 'volume' => 50],
            ],
        ]);

        $response->assertRedirect(route('customer.products.index', ['branch_id' => 3]));
        $response->assertSessionHas('success');

        $cart = session('customer_order_cart');
        $this->assertCount(1, $cart, 'the picked perfume is parked in the cart');
        $this->assertSame(2, (int) $cart[0]['quantity']);
        $this->assertSame(50, (int) $cart[0]['volume']);
        // The size's own price, not the branch's fallback.
        $this->assertSame(45000.0, (float) $cart[0]['unit_price']);
        // The details typed before leaving are kept for the trip back.
        $this->assertSame('Amina', session('customer_order_contact.name'));

        // The shop now carries the order in progress and its checkout link.
        $shop = $this->get('/products?branch_id=3');

        $shop->assertOk();
        $shop->assertSee('in your order');
        $shop->assertSee('90,000');
        $shop->assertSee(route('customer.orders.create', ['branch_id' => 3]), false);
    }

    public function test_the_order_page_shows_only_the_picked_perfume_and_the_cart(): void
    {
        $this->fakeSupabase();

        // A first perfume is parked in the cart...
        $this->post('/orders/cart', [
            'branch_id' => 3,
            'current_index' => 0,
            'items' => [['product_id' => 8, 'quantity' => 1, 'volume' => 0]],
        ])->assertSessionHasNoErrors();

        // ...then the customer taps "Order Now" on a second one.
        $response = $this->get('/orders/create?branch_id=3&product_id=7&volume=50');

        $response->assertOk();
        $response->assertSee('Reef 33');
        $response->assertSee('50ml');
        $response->assertSee('45,000');
        // Cart line first, the picked perfume's card after it.
        $response->assertSee('items[0][product_id]', false);
        $response->assertSee('items[1][volume]', false);
        $response->assertSee('value="50"', false);
        // The catalogue is not repeated here.
        $response->assertDontSee('Never Listed');
        // Both ways out of the page.
        $response->assertSee('Place Order');
        $response->assertSee('Choose More Products');
        // Only sizes reach the customer, never packaging.
        $response->assertDontSee('With Box');
        $response->assertDontSee('box_logo_yellow');
        $response->assertDontSee('no_box');
    }

    public function test_placing_the_order_takes_every_cart_line_and_empties_the_cart(): void
    {
        $writes = [];
        $this->fakeSupabase($writes);

        $this->post('/orders/cart', [
            'branch_id' => 3,
            'current_index' => 0,
            'items' => [['product_id' => 8, 'quantity' => 1, 'volume' => 0]],
        ]);

        // What the page posts: the cart line, then the product card.
        $response = $this->post('/orders', [
            'branch_id' => 3,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [
                ['product_id' => 8, 'quantity' => 1, 'volume' => 0],
                ['product_id' => 7, 'quantity' => 2, 'volume' => 50],
            ],
        ]);

        $response->assertOk();
        $response->assertSee('Order Placed');

        // Both lines are on the order: 65,000 + (2 x 45,000).
        $this->assertCount(2, $writes['order_items']);
        $this->assertSame(155000.0, (float) $response->viewData('order')->total);

        // The order now holds everything, so nothing is left waiting.
        $this->assertArrayNotHasKey('customer_order_cart', session()->all());
    }
}
