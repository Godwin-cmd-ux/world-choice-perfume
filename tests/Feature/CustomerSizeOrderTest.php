<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * What a CUSTOMER is offered when a product is bottled in several sizes.
 *
 * Three rules are pinned here, end to end through the same endpoints the
 * website and the app use:
 *
 *  1. Only volumes are choices. The box/logo/colour of a bottling is the
 *     branch's to pack and never changes the price, so a customer must never
 *     see it — and an order that names only a size still has to be priced and
 *     packed correctly.
 *  2. Availability is company-wide. A product on any shelf is Available, but
 *     neither the source branch nor its stock count reaches the customer.
 *  3. Every customer order is recorded against Mikocheni HQ (branch 10),
 *     even when the chosen size currently exists only at another branch.
 *
 * PostgREST is faked at the HTTP layer instead of mocking SupabaseService,
 * because Customer\ProductController builds its own service instance. The fake
 * applies the `eq.` and `in.` filters a real PostgREST would, so the assertions
 * below see the rows the queries would really return.
 */
class CustomerSizeOrderTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private const VARIETY_ROWS = [
        ['id' => 1, 'branch_id' => 10, 'product_id' => 7, 'volume' => 50, 'variant' => 'box_logo_yellow', 'quantity' => 2, 'selling_price' => 45000],
        ['id' => 2, 'branch_id' => 10, 'product_id' => 7, 'volume' => 50, 'variant' => 'no_box', 'quantity' => 3, 'selling_price' => 45000],
        ['id' => 3, 'branch_id' => 10, 'product_id' => 7, 'volume' => 30, 'variant' => 'plain', 'quantity' => 4, 'selling_price' => 30000],
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
     * @param  int  $branchStockQuantity  the HQ branch's aggregate shelf count
     */
    private function fakeSupabase(int $branchStockQuantity = 5, ?array &$writes = null): void
    {
        // PostgREST embeds the joined product inside each stock row.
        $product = ['id' => 7, 'name' => 'Reef 33', 'description' => 'A woody floral', 'brand' => 'Reef', 'category' => 'Oil Fragrance', 'sex_category' => 'unisex', 'images' => []];

        $tables = [
            'products' => [$product],
            'branches' => [
                ['id' => 10, 'name' => 'Mikocheni HQ', 'address' => 'Mikocheni', 'latitude' => -6.8, 'longitude' => 39.2],
                ['id' => 9, 'name' => 'Dodoma', 'address' => 'Dodoma', 'latitude' => null, 'longitude' => null],
            ],
            'branch_stock' => [
                ['id' => 11, 'branch_id' => 10, 'product_id' => 7, 'quantity' => $branchStockQuantity, 'selling_price' => 50000, 'branch' => ['id' => 10, 'name' => 'Mikocheni HQ'], 'product' => $product],
                ['id' => 12, 'branch_id' => 9, 'product_id' => 7, 'quantity' => 4, 'selling_price' => 47000, 'branch' => ['id' => 9, 'name' => 'Dodoma'], 'product' => $product],
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
                    if (! is_string($value)) {
                        continue;
                    }
                    if (! array_key_exists($key, $row)) {
                        continue;
                    }
                    if (preg_match('/^eq\.(.*)$/', $value, $matches)
                        && (string) $row[$key] !== $matches[1]) {
                        return false;
                    }
                    if (preg_match('/^in\.\((.*)\)$/', $value, $matches)) {
                        $allowed = array_map('trim', explode(',', $matches[1]));
                        if (! in_array((string) $row[$key], $allowed, true)) {
                            return false;
                        }
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
     * controller) answers one option per SIZE from every shelf, with one
     * availability state and an HQ order destination — no branch list, source
     * stock or branch-keyed sizes.
     */
    public function test_the_product_api_offers_company_wide_sizes_without_branch_details(): void
    {
        $this->fakeSupabase();

        $response = $this->getJson('/api/products/7');

        $response->assertOk();
        $response->assertJsonPath('is_available', true);
        $response->assertJsonPath('order_branch_id', 10);
        $response->assertJsonPath('price', 50000);

        $this->assertCount(3, $response->json('varieties'));
        $this->assertSame(30, $response->json('varieties.0.volume'));
        $this->assertSame('30ml', $response->json('varieties.0.label'));
        $this->assertSame(30000.0, (float) $response->json('varieties.0.price'));

        // The two 50ml packagings are one 50ml choice, with their stock summed.
        $this->assertSame(50, $response->json('varieties.1.volume'));
        $this->assertSame(5, $response->json('varieties.1.available'));
        $this->assertSame(45000.0, (float) $response->json('varieties.1.price'));

        // A size stocked only at Dodoma is still part of the one company-wide
        // list, but its branch and quantity are not named.
        $this->assertSame(100, $response->json('varieties.2.volume'));
        $this->assertSame(60000.0, (float) $response->json('varieties.2.price'));

        $data = $response->json();
        foreach (['branches', 'branch_stocks', 'selected_branch', 'in_stock_branch_count'] as $removed) {
            $this->assertArrayNotHasKey($removed, $data);
        }

        $payload = json_encode($response->json('varieties'));
        $this->assertStringNotContainsString('box', $payload);
        $this->assertStringNotContainsString('variant', $payload);
        $this->assertStringNotContainsString('no_box', $payload);
        $this->assertStringNotContainsString('branch', $payload);
    }

    /**
     * The website's order form is the other customer surface: it still asks
     * for a size, pre-selects the one that was clicked, and never mentions the
     * packaging behind it.
     */
    public function test_the_website_order_form_asks_for_a_size_only(): void
    {
        $this->fakeSupabase();

        $response = $this->get('/orders/create?branch_id=9&product_id=7&volume=50');

        $response->assertOk();
        $response->assertSee('Reef 33');
        $response->assertSee('50ml');
        $response->assertSee('30ml');
        // A stale branch_id in the link cannot move the order away from HQ.
        $response->assertSee('name="branch_id" value="10"', false);
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
     * The website's product page gives one company-wide availability answer
     * and sends every order to HQ, without naming a source branch.
     */
    public function test_the_website_product_page_shows_one_availability_and_hq_order_links(): void
    {
        $this->fakeSupabase();

        $response = $this->get('/products/7');

        $response->assertOk();
        $response->assertSee('Available');
        $response->assertDontSee('Out of Stock');
        $response->assertDontSee('Choose a branch');
        $response->assertDontSee('Mikocheni HQ');
        $response->assertDontSee('Dodoma');
        $response->assertDontSee('from TZS');
        // One order destination, with a size option for each company-wide size.
        $response->assertSee('branch_id=10');
        $response->assertSee('volume=30');
        $response->assertSee('volume=50');
        $response->assertSee('volume=100');
        $response->assertDontSee('5 in stock');
        $response->assertDontSee('4 in stock');
        $response->assertDontSee('With Box');
        $response->assertDontSee('box_logo_black');
    }

    // --------------------------------------------------------------- orders

    /**
     * A size-only order is priced by that size and packed from a bucket a
     * branch really holds, without the customer ever naming packaging.
     */
    public function test_a_size_only_order_is_priced_by_size_and_packed_from_a_real_bucket(): void
    {
        $writes = [];
        $this->fakeSupabase(branchStockQuantity: 5, writes: $writes);

        $response = $this->postJson('/api/orders', [
            'branch_id' => 10,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 2, 'volume' => 50]],
        ]);

        $response->assertStatus(201);
        $this->assertSame(90000.0, (float) $response->json('order.total'));
        $this->assertSame(10, (int) ($writes['orders'][0]['branch_id'] ?? 0));

        $line = $writes['order_items'][0] ?? null;
        $this->assertNotNull($line, 'the order line was written');
        $this->assertSame(50, (int) $line['volume']);
        $this->assertSame(45000.0, (float) $line['unit_price']);
        $this->assertSame(90000.0, (float) $line['total']);
        // HQ is told which packaging to reach for: the bucket holding the most
        // of that size (3 without box beats 2 with box + logo).
        $this->assertSame('no_box', $line['variant']);
    }

    /** A product sold in sizes cannot be ordered without saying which one. */
    public function test_an_order_without_a_size_is_refused(): void
    {
        $this->fakeSupabase();

        $response = $this->postJson('/api/orders', [
            'branch_id' => 10,
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
            'branch_id' => 10,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 6, 'volume' => 50]],
        ]);

        $tooMany->assertStatus(422);
        $tooMany->assertJsonPath('errors.items.0', 'Only 5 left of 50ml for Reef 33.');

        $writes = [];
        $this->fakeSupabase(branchStockQuantity: 10, writes: $writes);

        $fits = $this->postJson('/api/orders', [
            'branch_id' => 10,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 5, 'volume' => 50]],
        ]);

        $fits->assertStatus(201);
        $this->assertSame(225000.0, (float) $fits->json('order.total'));
        $this->assertSame(10, (int) ($writes['orders'][0]['branch_id'] ?? 0));
    }

    /**
     * A browser or older app can still send a non-HQ branch id: it is ignored,
     * and a size held only by that branch is accepted as an HQ order.
     */
    public function test_a_non_hq_branch_id_is_ignored_and_the_order_is_placed_for_hq(): void
    {
        $writes = [];
        $this->fakeSupabase(writes: $writes);

        $response = $this->postJson('/api/orders', [
            'branch_id' => 9,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 1, 'volume' => 100]],
        ]);

        $response->assertStatus(201);
        $this->assertSame(60000.0, (float) $response->json('order.total'));
        $this->assertSame(10, (int) ($writes['orders'][0]['branch_id'] ?? 0));
        $this->assertSame('box_logo_black', $writes['order_items'][0]['variant'] ?? null);
    }
}
