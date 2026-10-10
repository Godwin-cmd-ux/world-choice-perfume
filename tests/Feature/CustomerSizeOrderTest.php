<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * What a CUSTOMER is offered when a product is bottled in several sizes.
 *
 * Two rules are pinned here, end to end through the same endpoints the website
 * and the app use:
 *
 *  1. Only volumes are choices. The box/logo/colour of a bottling is the
 *     branch's to pack and never changes the price (every 50ml of a product
 *     costs the same), so a customer must never see it — and an order that
 *     names only a size still has to be priced and packed correctly.
 *  2. A perfume stocked at more than one branch is ordered from exactly one of
 *     them, so every stocked branch's price and stock reaches the customer
 *     before they choose.
 *
 * PostgREST is faked at the HTTP layer instead of mocking SupabaseService,
 * because Customer\ProductController builds its own service instance. The fake
 * applies the `eq.` filters a real PostgREST would, so the assertions below see
 * the rows a branch-scoped query would really return.
 */
class CustomerSizeOrderTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private const VARIETY_ROWS = [
        ['id' => 1, 'branch_id' => 3, 'product_id' => 7, 'volume' => 50, 'variant' => 'box_logo_yellow', 'quantity' => 2, 'selling_price' => 45000],
        ['id' => 2, 'branch_id' => 3, 'product_id' => 7, 'volume' => 50, 'variant' => 'no_box', 'quantity' => 3, 'selling_price' => 45000],
        ['id' => 3, 'branch_id' => 3, 'product_id' => 7, 'volume' => 30, 'variant' => 'plain', 'quantity' => 4, 'selling_price' => 30000],
        ['id' => 4, 'branch_id' => 9, 'product_id' => 7, 'volume' => 100, 'variant' => 'box_logo_black', 'quantity' => 1, 'selling_price' => 60000],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService caches query results in a static, which would leak
        // from one test into the next.
        SupabaseService::clearCache();
    }

    /**
     * One product, stocked at two branches, bottled in two sizes at the first
     * (with the 50ml split over two packagings) and one size at the second.
     *
     * @param  int  $branchStockQuantity  the branch's aggregate shelf count
     */
    private function fakeSupabase(int $branchStockQuantity = 5, ?array &$writes = null): void
    {
        // PostgREST embeds the joined product inside each stock row.
        $product = ['id' => 7, 'name' => 'Reef 33', 'description' => 'A woody floral', 'brand' => 'Reef', 'category' => 'Oil Fragrance', 'sex_category' => 'unisex', 'images' => []];

        $tables = [
            'products' => [$product],
            'branches' => [
                ['id' => 3, 'name' => 'Kariakoo', 'address' => 'Kariakoo Market', 'latitude' => -6.8, 'longitude' => 39.2],
                ['id' => 9, 'name' => 'Mikocheni', 'address' => 'Mikocheni B', 'latitude' => null, 'longitude' => null],
            ],
            'branch_stock' => [
                ['id' => 11, 'branch_id' => 3, 'product_id' => 7, 'quantity' => $branchStockQuantity, 'selling_price' => 50000, 'branch' => ['id' => 3, 'name' => 'Kariakoo'], 'product' => $product],
                ['id' => 12, 'branch_id' => 9, 'product_id' => 7, 'quantity' => 4, 'selling_price' => 47000, 'branch' => ['id' => 9, 'name' => 'Mikocheni'], 'product' => $product],
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
                // PostgREST takes either one record or an array of them.
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
                        continue; // select / order / limit / in.(...) are not equality filters
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

    // ---------------------------------------------------------------- sizes

    /**
     * The app's product detail (and the website's, which shares the
     * controller) must answer one option per SIZE — the two 50ml packagings
     * are a single 50ml choice — while still listing every stocked branch.
     */
    public function test_the_product_api_offers_one_option_per_size_and_every_stocked_branch(): void
    {
        $this->fakeSupabase();

        $response = $this->getJson('/api/products/7');

        $response->assertOk();
        $response->assertJsonPath('in_stock_branch_count', 2);

        // Branch 3 bottles it in 30ml and 50ml.
        $this->assertCount(2, $response->json('varieties.3'));
        $this->assertSame(30, $response->json('varieties.3.0.volume'));
        $this->assertSame('30ml', $response->json('varieties.3.0.label'));
        $this->assertSame(4, $response->json('varieties.3.0.available'));
        $this->assertSame(30000.0, (float) $response->json('varieties.3.0.price'));

        $this->assertSame(50, $response->json('varieties.3.1.volume'));
        // The whole size is available: 2 "with box + logo" plus 3 "without box".
        $this->assertSame(5, $response->json('varieties.3.1.available'));
        $this->assertSame(45000.0, (float) $response->json('varieties.3.1.price'));

        // The second branch's own price and size come through too.
        $this->assertSame(100, $response->json('varieties.9.0.volume'));
        $this->assertSame(1, $response->json('varieties.9.0.available'));
        $this->assertSame(60000.0, (float) $response->json('varieties.9.0.price'));

        // Nothing about the packaging reaches the customer's payload.
        $payload = json_encode($response->json('varieties'));
        $this->assertStringNotContainsString('box', $payload);
        $this->assertStringNotContainsString('variant', $payload);
        $this->assertStringNotContainsString('no_box', $payload);

        // The branches the customer picks between carry their own numbers.
        $this->assertSame(50000, (int) $response->json('branch_stocks.0.selling_price'));
        $this->assertSame(4, (int) $response->json('branch_stocks.1.quantity'));
    }

    /**
     * The website's order form is the other customer surface: it has to offer
     * the sizes the branch stocks, pre-select the one that was clicked, and
     * never mention the packaging behind it.
     */
    public function test_the_website_order_form_asks_for_a_size_only(): void
    {
        $this->fakeSupabase();

        $response = $this->get('/orders/create?branch_id=3&product_id=7&volume=50');

        $response->assertOk();
        $response->assertSee('Reef 33');
        $response->assertSee('50ml');
        $response->assertSee('30ml');
        // The clicked size's own price and its whole-size availability.
        $response->assertSee('45,000');
        $response->assertSee('5 in stock');
        // The size is what gets posted.
        $response->assertSee('items[0][volume]', false);
        $response->assertSee('value="50"', false);

        $response->assertDontSee('With Box');
        $response->assertDontSee('box_logo_yellow');
        $response->assertDontSee('no_box');
        $response->assertDontSee('[variant]', false);
    }

    /**
     * The website's product page has to show what each branch offers before the
     * customer commits to one — including when they arrived from a branch that
     * does not stock it.
     */
    public function test_the_website_product_page_lists_every_stocked_branch_with_its_price_and_stock(): void
    {
        $this->fakeSupabase();

        $response = $this->get('/products/7');

        $response->assertOk();
        $response->assertSee('Choose a branch');
        $response->assertSee('Kariakoo');
        $response->assertSee('Mikocheni');
        // Each option carries that branch's own price and shelf count.
        $response->assertSee('from TZS 30,000');
        $response->assertSee('from TZS 60,000');
        $response->assertSee('5 in stock');
        $response->assertSee('4 in stock');
        $response->assertDontSee('With Box');
        $response->assertDontSee('box_logo_black');
    }

    // --------------------------------------------------------------- orders

    /**
     * A size-only order is priced by that size and packed from a bucket the
     * branch really holds, without the customer ever naming packaging.
     */
    public function test_a_size_only_order_is_priced_by_size_and_packed_from_a_real_bucket(): void
    {
        $writes = [];
        $this->fakeSupabase(branchStockQuantity: 5, writes: $writes);

        $response = $this->postJson('/api/orders', [
            'branch_id' => 3,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 2, 'volume' => 50]],
        ]);

        $response->assertStatus(201);
        $this->assertSame(90000.0, (float) $response->json('order.total'));

        $line = $writes['order_items'][0] ?? null;
        $this->assertNotNull($line, 'the order line was written');
        $this->assertSame(50, (int) $line['volume']);
        $this->assertSame(45000.0, (float) $line['unit_price']);
        $this->assertSame(90000.0, (float) $line['total']);
        // The branch is told which packaging to reach for: the bucket holding
        // the most of that size (3 without box beats 2 with box + logo).
        $this->assertSame('no_box', $line['variant']);
    }

    /** A product sold in sizes cannot be ordered without saying which one. */
    public function test_an_order_without_a_size_is_refused(): void
    {
        $this->fakeSupabase();

        $response = $this->postJson('/api/orders', [
            'branch_id' => 3,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.items.0', 'Please choose the size for Reef 33.');
    }

    /**
     * Availability is the whole SIZE, not one packaging bucket: 5 of the 50ml
     * exist (3 + 2), so ordering 6 fails on the size while ordering 5 works.
     */
    public function test_availability_is_counted_across_the_whole_size(): void
    {
        $this->fakeSupabase(branchStockQuantity: 10);

        $tooMany = $this->postJson('/api/orders', [
            'branch_id' => 3,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 6, 'volume' => 50]],
        ]);

        $tooMany->assertStatus(422);
        $tooMany->assertJsonPath('errors.items.0', 'Only 5 left of 50ml for Reef 33.');

        $writes = [];
        $this->fakeSupabase(branchStockQuantity: 10, writes: $writes);

        $fits = $this->postJson('/api/orders', [
            'branch_id' => 3,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 5, 'volume' => 50]],
        ]);

        $fits->assertStatus(201);
        $this->assertSame(225000.0, (float) $fits->json('order.total'));
    }
}
