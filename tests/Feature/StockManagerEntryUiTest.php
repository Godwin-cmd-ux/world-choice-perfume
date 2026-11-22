<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The stock-manager pages the operator actually touches.
 *
 * These pin the small UI rules that a green unit suite cannot see: the number
 * wheel guard is present, the add-line button follows the last line, each line
 * has its own accent, and product pickers are searchable by typed words
 * (including a phrase with a space).
 */
class StockManagerEntryUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SupabaseService::clearCache();
    }

    public function test_the_entry_page_has_the_picker_line_controls_and_wheel_guard(): void
    {
        $this->fakeSupabase();
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/product-stock/entry');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="product_suggestions"', $html);
        $this->assertStringContainsString('spaces are allowed', $html);
        $this->assertStringContainsString('.split(/\\s+/)', $html, 'The entry picker splits a typed phrase into terms.');
        $this->assertStringContainsString('terms.every', $html);
        $this->assertStringContainsString('.item-line.line-tone-0', $html);
        $this->assertStringContainsString('.item-line.line-tone-3', $html);
        $this->assertStringContainsString('input[type="number"]', $html, 'The wheel guard must be loaded on stock pages.');
        $this->assertStringContainsString('event.preventDefault()', $html);

        $lines = strpos($html, '<div id="item-lines"></div>');
        $add = strpos($html, 'Add Item Line');
        $this->assertNotFalse($lines);
        $this->assertNotFalse($add);
        $this->assertLessThan($add, $lines, 'Add Item Line must sit after the last item line.');
    }

    public function test_the_movements_filter_uses_the_searchable_product_picker(): void
    {
        $this->fakeSupabase();
        $this->actingAsStockManager();

        $response = $this->get('/stock-manager/product-stock/movements');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-searchable-select', $html);
        $this->assertStringContainsString('Type to search...', $html);
        $this->assertStringContainsString('All Products', $html);
        $this->assertStringContainsString('Reef 33 — Reef', $html);
    }

    private function fakeSupabase(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/rest/v1/products')) {
                return Http::response([
                    ['id' => 1, 'name' => 'Reef 33', 'brand' => 'Reef', 'category' => 'Brand Perfume'],
                ], 200);
            }

            if (str_contains($url, '/rest/v1/branches')) {
                return Http::response([
                    ['id' => 8, 'name' => 'Dodoma branch', 'category' => 'autonomous'],
                ], 200);
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
