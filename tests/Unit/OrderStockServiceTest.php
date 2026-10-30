<?php

namespace Tests\Unit;

use App\Services\OrderStockService;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * Serving an order is the point where its items leave the branch's shelf.
 *
 * These pin the three behaviours the serve path relies on: the deduction is
 * recorded against the order, serving twice can never take stock twice, and a
 * branch that is short is never pushed below zero.
 */
class OrderStockServiceTest extends TestCase
{
    public function test_a_served_order_deducts_its_items_and_records_the_order_reference(): void
    {
        $supabase = $this->fakeSupabase(quantity: 10);

        $result = (new OrderStockService($supabase))->deductForOrder($this->order(), 7);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['already']);
        $this->assertSame(3, $result['deducted']);
        $this->assertSame([], $result['skipped']);
        $this->assertSame(7, $supabase->quantity);
        $this->assertCount(1, $supabase->movements);
        $this->assertSame('order', $supabase->movements[0]['reference_type']);
        $this->assertSame(42, $supabase->movements[0]['reference_id']);
        $this->assertSame(-3, $supabase->movements[0]['quantity']);
    }

    public function test_an_order_that_already_moved_stock_is_left_alone(): void
    {
        $supabase = $this->fakeSupabase(quantity: 10);
        $service = new OrderStockService($supabase);

        $service->deductForOrder($this->order(), 7);
        $second = $service->deductForOrder($this->order(), 7);

        $this->assertTrue($second['ok']);
        $this->assertTrue($second['already']);
        $this->assertSame(7, $supabase->quantity, 'A retried serve must not take the stock a second time.');
        $this->assertCount(1, $supabase->movements);
    }

    public function test_a_short_branch_is_deducted_only_to_zero(): void
    {
        $supabase = $this->fakeSupabase(quantity: 2);

        $result = (new OrderStockService($supabase))->deductForOrder($this->order(), 7);

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['deducted']);
        $this->assertSame(0, $supabase->quantity, 'Stock must never go negative.');
        $this->assertNotEmpty($result['skipped']);
        $this->assertSame(2, $result['skipped'][0]['deducted']);
    }

    private function order(): array
    {
        return ['id' => 42, 'branch_id' => 10, 'order_number' => 'ORD-0042'];
    }

    /**
     * A minimal in-memory branch: one product with $quantity in stock and a
     * single order line asking for three of them.
     */
    private function fakeSupabase(int $quantity): SupabaseService
    {
        return new class($quantity) extends SupabaseService
        {
            public int $quantity;

            /** @var array<int, array> */
            public array $movements = [];

            public function __construct(int $quantity)
            {
                $this->quantity = $quantity;
            }

            public function queryFresh(string $table, array $params = []): array
            {
                return match ($table) {
                    'stock_movements' => $this->movements,
                    'order_items' => [[
                        'product_id' => 5,
                        'quantity' => 3,
                        'unit_price' => 12000,
                        'volume' => 0,
                        'variant' => null,
                    ]],
                    'products' => [['id' => 5, 'category' => 'Perfume']],
                    'branch_stock' => $this->quantity > 0 ? [[
                        'id' => 99,
                        'product_id' => 5,
                        'quantity' => $this->quantity,
                        'selling_price' => 10000,
                    ]] : [],
                    default => [],
                };
            }

            public function update(string $table, array|object $data, array $conditions): array
            {
                if ($table !== 'branch_stock') {
                    return [];
                }

                $this->quantity = (int) ((array) $data)['quantity'];

                return [['id' => $conditions['id'] ?? 99, 'quantity' => $this->quantity]];
            }

            public function insertMany(string $table, array $records): ?array
            {
                if ($table === 'stock_movements') {
                    array_push($this->movements, ...$records);
                }

                return $records;
            }
        };
    }
}
