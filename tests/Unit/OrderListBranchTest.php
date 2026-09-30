<?php

namespace Tests\Unit;

use App\Services\OrderWorkflowService;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * The origin branch on the tab lists.
 *
 * Super Admin is the one role whose order list spans every branch, so the
 * Branch column is the only thing telling two rows apart. That column reads
 * $order->branch?->name, which silently renders as an em dash when the list
 * query leaves the branches relation out of its select — no error, no warning,
 * just a blank column. Pinning the embed here is the whole defence.
 */
class OrderListBranchTest extends TestCase
{
    public function test_the_list_query_embeds_the_branch_relation(): void
    {
        $supabase = $this->fakeSupabase();

        $this->service($supabase)->tabRows(null, 'pending', 7, false);

        $this->assertStringContainsString(
            'branch:branches(',
            $supabase->orderSelect,
            'The order list select must embed branches, or the Branch column is always blank.'
        );
    }

    /** cast() has to hand the view an object, because the cell uses ?->name. */
    public function test_the_embedded_branch_reaches_the_view_as_an_object(): void
    {
        $orders = $this->service($this->fakeSupabase())->tabRows(null, 'pending', 7, false);

        $this->assertCount(1, $orders);
        $this->assertIsObject($orders[0]->branch, 'The embedded branch must be cast to an object for ?->name to work.');
        $this->assertSame('Head Quarters-Mikocheni', $orders[0]->branch->name);
    }

    /**
     * PostgREST returns embedded relations as nested arrays, and the row this
     * fake hands back mirrors that shape.
     */
    private function fakeSupabase(): SupabaseService
    {
        return new class extends SupabaseService
        {
            public function __construct() {}

            /** The select the service built for the orders read. */
            public string $orderSelect = '';

            public function query(string $table, array $params = []): array
            {
                return match ($table) {
                    'orders' => $this->orders($params),
                    // pickerNames() looks the pickers up; none of our row is assigned.
                    'users' => [],
                    default => [],
                };
            }

            private function orders(array $params): array
            {
                $this->orderSelect = (string) ($params['select'] ?? '');

                return [[
                    'id' => 1,
                    'order_number' => 'ORD-0001',
                    'personal_order_name' => null,
                    'total' => 45000,
                    'status' => 'pending',
                    'branch_id' => 10,
                    'customer_id' => null,
                    'assigned_to' => null,
                    'created_at' => '2026-09-30T08:00:00+00:00',
                    'customer' => ['id' => 3, 'name' => 'Amina Yusuf', 'phone' => '0754'],
                    'branch' => ['id' => 10, 'name' => 'Head Quarters-Mikocheni'],
                    'items' => [],
                ]];
            }
        };
    }

    private function service(SupabaseService $supabase): OrderWorkflowService
    {
        return new OrderWorkflowService($supabase);
    }
}
