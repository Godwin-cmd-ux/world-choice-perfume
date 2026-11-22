<?php

namespace Tests\Unit;

use App\Services\StockNotificationService;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * Low-stock alerts for the stock admin.
 *
 * Thresholds are deliberately different: a brand product alerts AT two, while
 * bottles and oil fragrances alert only BELOW 100 and 2. Products-only branches
 * and Head Quarters-Mikocheni do not use the bottle/oil modules, so rows from
 * those branches must never produce those alerts.
 */
class StockNotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSyncGuard();
    }

    public function test_it_alerts_at_the_thresholds_and_skips_bottle_exempt_branches(): void
    {
        $supabase = $this->fakeSupabase();

        (new StockNotificationService($supabase))->sync();

        $this->assertSame([
            'brand:8:1',
            'brand:9:2',
            'bottle:8:50:plain',
            'oil:8:5',
        ], $this->alertKeys($supabase->notifications));

        $this->assertContains('Bottle Stock Low', array_column($supabase->notifications, 'title'));
        $this->assertContains('Oil Fragrance Low', array_column($supabase->notifications, 'title'));
    }

    public function test_a_second_sync_in_the_same_request_does_not_duplicate_alerts(): void
    {
        $supabase = $this->fakeSupabase();
        $service = new StockNotificationService($supabase);

        $service->sync();
        $service->sync();

        $this->assertCount(4, $supabase->notifications);
    }

    public function test_a_read_alert_is_not_recreated_while_stock_stays_low(): void
    {
        $supabase = $this->fakeSupabase();

        (new StockNotificationService($supabase))->sync();
        foreach ($supabase->notifications as &$notification) {
            $notification['is_read'] = true;
        }
        unset($notification);

        // A later request still sees the same low-stock rows and the read rows.
        $this->resetSyncGuard();
        (new StockNotificationService($supabase))->sync();

        $this->assertCount(4, $supabase->notifications);
        $this->assertTrue(array_reduce($supabase->notifications, fn ($all, $n) => $all && $n['is_read'], true));
    }

    private function resetSyncGuard(): void
    {
        $property = new \ReflectionProperty(StockNotificationService::class, 'syncedThisRequest');
        $property->setValue(null, false);
    }

    private function alertKeys(array $notifications): array
    {
        return array_values(array_map(function ($notification) {
            $data = json_decode($notification['data'], true);

            return $data['alert_key'] ?? null;
        }, $notifications));
    }

    private function fakeSupabase(): SupabaseService
    {
        return new class extends SupabaseService
        {
            /** @var array<int, array> */
            public array $branches = [
                ['id' => 8, 'name' => 'Dodoma branch', 'category' => 'autonomous'],
                ['id' => 9, 'name' => 'Products Only branch', 'category' => 'products_based'],
                ['id' => 10, 'name' => 'Head Quarters-Mikocheni', 'category' => 'autonomous'],
            ];

            /** @var array<int, array> */
            public array $products = [
                ['id' => 1, 'name' => 'Reef 33', 'brand' => 'Reef', 'category' => 'Brand Perfume'],
                ['id' => 2, 'name' => 'Dune', 'brand' => 'Dior', 'category' => 'Brand Perfume'],
            ];

            /** @var array<int, array> */
            public array $branchStock = [
                ['id' => 1, 'branch_id' => 8, 'product_id' => 1, 'quantity' => 2, 'category' => 'Brand Perfume'],
                ['id' => 2, 'branch_id' => 8, 'product_id' => 99, 'quantity' => 1, 'category' => 'Oil Fragrance'],
                ['id' => 3, 'branch_id' => 9, 'product_id' => 2, 'quantity' => 1, 'category' => 'Brand Perfume'],
            ];

            /** @var array<int, array> */
            public array $bottleStock = [
                ['id' => 1, 'branch_id' => 8, 'volume' => '50ml', 'variant' => 'plain', 'quantity' => 99],
                ['id' => 2, 'branch_id' => 8, 'volume' => '30ml', 'variant' => 'no_box', 'quantity' => 100],
                ['id' => 3, 'branch_id' => 9, 'volume' => '50ml', 'variant' => 'plain', 'quantity' => 10],
                ['id' => 4, 'branch_id' => 10, 'volume' => '50ml', 'variant' => 'plain', 'quantity' => 10],
            ];

            /** @var array<int, array> */
            public array $oils = [
                ['id' => 5, 'branch_id' => 8, 'name' => 'Amber', 'volume' => 30, 'quantity' => 1],
                ['id' => 6, 'branch_id' => 8, 'name' => 'Musk', 'volume' => 30, 'quantity' => 2],
                ['id' => 7, 'branch_id' => 9, 'name' => 'Jasmine', 'volume' => 30, 'quantity' => 0],
                ['id' => 8, 'branch_id' => 10, 'name' => 'Oud', 'volume' => 30, 'quantity' => 0],
            ];

            /** @var array<int, array> */
            public array $notifications = [];

            public function __construct() {}

            public function query(string $table, array $params = []): array
            {
                return match ($table) {
                    'products' => $this->products,
                    'admin_notifications' => $this->notifications,
                    default => [],
                };
            }

            public function queryFresh(string $table, array $params = []): array
            {
                $rows = match ($table) {
                    'branches' => $this->branches,
                    'branch_stock' => $this->branchStock,
                    'bottle_stock' => $this->bottleStock,
                    'oil_fragrance_stock' => $this->oils,
                    default => [],
                };

                foreach ($params as $field => $filter) {
                    if (!is_string($filter) || !str_contains($filter, '.')) {
                        continue;
                    }
                    [$operator, $value] = explode('.', $filter, 2);
                    $rows = array_values(array_filter($rows, function ($row) use ($field, $operator, $value) {
                        $actual = $row[$field] ?? null;
                        if ($operator === 'lte') {
                            return (int) $actual <= (int) $value;
                        }
                        if ($operator === 'eq') {
                            return (string) $actual === $value;
                        }

                        return true;
                    }));
                }

                return $rows;
            }

            public function insert(string $table, array|object $data): ?array
            {
                $record = (array) $data;
                $record['id'] = count($this->notifications) + 1;
                $this->notifications[] = $record;

                return $record;
            }
        };
    }
}
