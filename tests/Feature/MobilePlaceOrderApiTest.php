<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

/**
 * POST /api/orders — the mobile app's "Place Order" endpoint.
 *
 * It dispatches to the very controller the website checkout posts to
 * (Customer\OrderController@store), which validates before touching
 * Supabase, so the field-level validation contract can be pinned here
 * without a live project.
 *
 * A branch_id may still arrive from an older app build, but it is no longer a
 * destination: HQ is the only branch a customer order can belong to.
 */
class MobilePlaceOrderApiTest extends TestCase
{
    public function test_the_route_places_orders_through_the_website_controller(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'api/orders' && in_array('POST', $route->methods())
        );

        $this->assertNotNull($route);
        $this->assertSame('App\Http\Controllers\Customer\OrderController@store', $route->getActionName());
    }

    public function test_a_missing_body_answers_json_validation_errors(): void
    {
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(422);
        // branch_id is accepted for older clients but is never required: the
        // server always records the order against Mikocheni HQ.
        $response->assertJsonValidationErrors(['customer_name', 'customer_phone', 'items']);
    }

    public function test_each_order_line_is_validated(): void
    {
        $response = $this->postJson('/api/orders', [
            'branch_id' => 1,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['quantity' => 0]],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.product_id', 'items.0.quantity']);
    }

    /**
     * A plain (non-bottled) product on the shelf, with the order insert
     * captured so the destination can be checked without reading Supabase.
     */
    private function fakeSupabase(?array &$capturedOrder = null): void
    {
        $supabase = Mockery::mock(SupabaseService::class);

        // No existing customer, so one is created.
        $supabase->shouldReceive('findOne')
            ->with('customers', Mockery::type('array'))
            ->once()
            ->andReturn(null);
        $supabase->shouldReceive('insert')
            ->with('customers', Mockery::type('array'))
            ->once()
            ->andReturn(['id' => 55]);

        // One product on the shelf, priced at 50,000.
        $supabase->shouldReceive('query')->andReturnUsing(function (string $table): array {
            if ($table === 'branch_stock') {
                return [[
                    'branch_id' => 10,
                    'product_id' => 7,
                    'quantity' => 10,
                    'selling_price' => 50000,
                    'product' => ['id' => 7, 'name' => 'Reef 33'],
                ]];
            }
            if ($table === 'products') {
                return [['id' => 7, 'category' => 'Brand Perfume']];
            }

            return [];
        });
        $supabase->shouldReceive('queryFresh')->andReturn([]);
        $supabase->shouldReceive('tableHasColumn')->andReturn(false);

        $supabase->shouldReceive('insert')
            ->with('orders', Mockery::type('array'))
            ->once()
            ->andReturnUsing(function (string $table, array $data) use (&$capturedOrder) {
                $capturedOrder = $data;

                return array_merge($data, ['id' => 99]);
            });
        $supabase->shouldReceive('insertMany')
            ->with('order_items', Mockery::type('array'))
            ->once()
            ->andReturn([]);

        $this->app->instance(SupabaseService::class, $supabase);
    }

    /**
     * The app parses the order number and total out of the response, so a
     * placed order has to come back as JSON (the website gets a view) with
     * those fields from the row the server actually created. A stale non-HQ
     * branch id from an older build must not move that order away from HQ.
     */
    public function test_a_non_hq_branch_id_is_ignored_and_a_placed_order_answers_json_with_the_created_order(): void
    {
        $capturedOrder = null;
        $this->fakeSupabase($capturedOrder);

        $response = $this->postJson('/api/orders', [
            'branch_id' => 3,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 2]],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('order.id', 99);
        // A placed order is held for customer care, not shown to the branch yet.
        $response->assertJsonPath('order.status', 'awaiting_care');
        $response->assertJsonPath('order.total', 100000);
        $this->assertStringStartsWith('ORD-', $response->json('order.order_number'));
        $this->assertSame(10, (int) $capturedOrder['branch_id']);
        $this->assertSame(55, (int) $capturedOrder['customer_id']);
    }

    /** A newer client that omits branch_id entirely still orders from HQ. */
    public function test_an_omitted_branch_id_is_also_placed_for_hq(): void
    {
        $capturedOrder = null;
        $this->fakeSupabase($capturedOrder);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 1]],
        ]);

        $response->assertStatus(201);
        $this->assertSame(10, (int) $capturedOrder['branch_id']);
    }
}
