<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NavigationCountsService;
use App\Services\SupabaseService;
use Tests\TestCase;

class NavigationCountsOrdersTest extends TestCase
{
    public function test_super_admin_order_badge_counts_only_pending_orders(): void
    {
        $supabase = new class extends SupabaseService
        {
            public array $orderConditions = [];

            public function __construct() {}

            public function count(string $table, array $conditions = []): int
            {
                if ($table === 'orders') {
                    $this->orderConditions = $conditions;

                    return 3;
                }

                return 0;
            }

            public function query(string $table, array $params = []): array
            {
                return [];
            }

            public function tableHasColumn(string $table, string $column): bool
            {
                return false;
            }
        };

        $counts = (new NavigationCountsService($supabase))
            ->forUser(new User(['role' => 'super_admin']));

        // awaiting_care belongs to customer care, and picking an order moves it
        // to "picked", so only pending rows may keep this badge up.
        $this->assertSame(['status' => 'eq.pending'], $supabase->orderConditions);
        $this->assertSame(3, $counts['orders']);
    }
}
