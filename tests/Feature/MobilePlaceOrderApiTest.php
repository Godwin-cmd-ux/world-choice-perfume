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
        $response->assertJsonValidationErrors(['branch_id', 'customer_name', 'customer_phone', 'items']);
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
     * The app parses the order number and total out of the response, so a
     * placed order has to come back as JSON (the website gets a view) with
     * those fields from the row the server actually created.
     */
    public function test_a_placed_order_answers_json_with_the_created_order(): void
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

        // One product on the shelf at the branch, priced at 50,000.
        $supabase->shouldReceive('query')->andReturnUsing(function (string $table): array {
            if ($table === 'branch_stock') {
                return [[
                    'product_id' => 7,
                    'quantity' => 10,
                    'selling_price' => 50000,
                    'product' => ['name' => 'Reef 33'],
                ]];
            }
            if ($table === 'products') {
                return [['id' => 7, 'category' => 'Brand Perfume']];
            }

            return [];
        });
        $supabase->shouldReceive('queryFresh')->andReturn([]);
        $supabase->shouldReceive('tableHasColumn')->andReturn(false);

        $capturedOrder = null;
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

        $response = $this->postJson('/api/orders', [
            'branch_id' => 3,
            'customer_name' => 'Amina',
            'customer_phone' => '0712345678',
            'items' => [['product_id' => 7, 'quantity' => 2]],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('order.id', 99);
        $response->assertJsonPath('order.status', 'pending');
        $response->assertJsonPath('order.total', 100000);
        $this->assertStringStartsWith('ORD-', $response->json('order.order_number'));
        $this->assertSame(3, (int) $capturedOrder['branch_id']);
        $this->assertSame(55, (int) $capturedOrder['customer_id']);
    }
}
