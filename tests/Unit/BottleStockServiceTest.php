<?php

namespace Tests\Unit;

use App\Services\BottleStockService;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * Manual product-stock edits reuse the same bottle buckets as stock entries.
 *
 * An upward edit consumes bottles; correcting it downwards must give exactly
 * those bottles back to the same volume/variant bucket, with a matching
 * movement, so the two operations cancel out.
 */
class BottleStockServiceTest extends TestCase
{
    public function test_restock_returns_bottles_to_the_exact_variant_bucket(): void
    {
        $supabase = $this->fakeSupabase();
        $service = new BottleStockService($supabase);

        $newQuantity = $service->restock(8, 50, 4, 'Manual stock reduction for Reef 33', 700, 'box_logo_yellow');

        $this->assertSame(14, $newQuantity);
        $this->assertSame(1, $supabase->updates[0]['conditions']['id']);
        $this->assertSame(14, $supabase->updates[0]['data']['quantity']);
        $this->assertCount(1, $supabase->movements);
        $this->assertSame('stock_in', $supabase->movements[0]['type']);
        $this->assertSame(4, $supabase->movements[0]['quantity']);
        $this->assertSame('50ml', $supabase->movements[0]['volume']);
        $this->assertSame('box_logo_yellow', $supabase->movements[0]['variant']);
        $this->assertSame('Manual stock reduction for Reef 33', $supabase->movements[0]['reason']);
    }

    public function test_available_variant_quantity_reads_only_the_matching_bucket(): void
    {
        $service = new BottleStockService($this->fakeSupabase());

        $this->assertSame(10, $service->availableVariantQuantity(8, 50, 'box_logo_yellow'));
        $this->assertSame(3, $service->availableVariantQuantity(8, 50, 'plain'));
        $this->assertSame(0, $service->availableVariantQuantity(8, 50, 'no_box'));
    }

    public function test_a_reduction_without_a_matching_bucket_does_not_create_a_new_one(): void
    {
        $supabase = $this->fakeSupabase();
        $service = new BottleStockService($supabase);

        $this->assertNull($service->restock(8, 50, 4, 'Manual stock reduction', 700, 'no_box'));
        $this->assertSame([], $supabase->updates, 'No bucket means there is nowhere to put the bottles back.');
        $this->assertSame([], $supabase->movements);
    }

    public function test_deduct_then_restock_leaves_the_bucket_as_it_started(): void
    {
        $supabase = $this->fakeSupabase();
        $service = new BottleStockService($supabase);

        $service->deduct(8, 50, 3, 'Manual stock increase', 700, 'box_logo_yellow');
        $service->restock(8, 50, 3, 'Manual stock reduction', 700, 'box_logo_yellow');

        $this->assertSame(10, $supabase->stock[0]['quantity']);
        $this->assertCount(2, $supabase->movements);
        $this->assertSame('stock_out', $supabase->movements[0]['type']);
        $this->assertSame('stock_in', $supabase->movements[1]['type']);
    }

    private function fakeSupabase(): SupabaseService
    {
        return new class extends SupabaseService
        {
            /** @var array<int, array> */
            public array $stock = [
                ['id' => 1, 'branch_id' => 8, 'volume' => '50ml', 'variant' => 'box_logo_yellow', 'quantity' => 10],
                ['id' => 2, 'branch_id' => 8, 'volume' => '50ml', 'variant' => 'plain', 'quantity' => 3],
            ];

            /** @var array<int, array> */
            public array $updates = [];

            /** @var array<int, array> */
            public array $movements = [];

            public function __construct() {}

            public function query(string $table, array $params = []): array
            {
                return $this->queryFresh($table, $params);
            }

            public function queryFresh(string $table, array $params = []): array
            {
                if ($table !== 'bottle_stock') {
                    return [];
                }

                $rows = $this->stock;
                foreach (['branch_id', 'volume', 'variant'] as $field) {
                    if (!isset($params[$field])) {
                        continue;
                    }
                    $value = substr((string) $params[$field], strlen('eq.'));
                    $rows = array_values(array_filter($rows, fn ($row) => (string) ($row[$field] ?? '') === $value));
                }

                return $rows;
            }

            public function update(string $table, array|object $data, array $conditions): array
            {
                $this->updates[] = ['table' => $table, 'data' => (array) $data, 'conditions' => $conditions];

                foreach ($this->stock as &$row) {
                    if ((string) $row['id'] === (string) ($conditions['id'] ?? '')) {
                        $row['quantity'] = (int) $data['quantity'];
                        break;
                    }
                }
                unset($row);

                return [];
            }

            public function insert(string $table, array|object $data): ?array
            {
                $this->movements[] = (array) $data;

                return (array) $data;
            }

            public function tableHasColumn(string $table, string $column): bool
            {
                return true;
            }
        };
    }
}
