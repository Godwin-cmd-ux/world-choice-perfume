<?php

namespace Tests\Unit;

use App\Services\OrderWorkflowService;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * The customer-care gate on placed orders.
 *
 * A newly placed order is written as awaiting_care, which no branch list or
 * detail page returns, so a branch's staff never see it. Only customer care of
 * that branch can open it, and its decision is what turns the order into
 * pending (visible to the branch) or failed (never visible again).
 */
class CareOrderGateTest extends TestCase
{
    private function order(int $id, string $status, int $branchId = 3): array
    {
        return [
            'id' => $id,
            'order_number' => 'ORD-' . $id,
            'branch_id' => $branchId,
            'customer_id' => 1,
            'status' => $status,
            'total' => 45000,
            'assigned_to' => null,
            'cashier_id' => null,
            'created_at' => '2026-11-07T08:00:00+00:00',
        ];
    }

    private function fakeSupabase(array $orders): SupabaseService
    {
        return new class($orders) extends SupabaseService
        {
            public function __construct(private array $orders) {}

            public function query(string $table, array $params = []): array
            {
                if ($table !== 'orders') {
                    return [];
                }

                return array_values(array_filter($this->orders, function (array $order) use ($params) {
                    foreach (['status', 'branch_id'] as $column) {
                        $value = $params[$column] ?? null;

                        if (
                            is_string($value)
                            && preg_match('/^eq\.(.*)$/', $value, $matches)
                            && (string) $order[$column] !== $matches[1]
                        ) {
                            return false;
                        }
                    }

                    return true;
                }));
            }

            public function find(string $table, $id, ?string $select = null): ?array
            {
                foreach ($this->orders as $order) {
                    if ($table === 'orders' && (int) $order['id'] === (int) $id) {
                        return $order;
                    }
                }

                return null;
            }
        };
    }

    public function test_a_held_order_is_hidden_from_everyone_but_customer_care(): void
    {
        $workflow = new OrderWorkflowService($this->fakeSupabase([$this->order(1, 'awaiting_care')]));

        $this->assertNull(
            $workflow->findForShow(1, 3, 9),
            'Branch staff must not open an order customer care has not released, even by id.'
        );

        $this->assertNull(
            $workflow->findForShow(1, 4, 9),
            'Customer care at another branch must not reach it either.'
        );

        $held = $workflow->findForShow(1, 3, 9, true, true);

        $this->assertNotNull($held, 'Customer care must be able to open the order it has to review.');
        $this->assertSame('awaiting_care', $held->status);
        $this->assertSame('awaiting_care', $workflow->statusFor('review'));
        $this->assertSame('failed', $workflow->statusFor('trash'));
    }

    public function test_the_branch_tabs_only_ever_ask_for_branch_statuses(): void
    {
        $workflow = new OrderWorkflowService($this->fakeSupabase([]));

        // A held order cannot appear on a branch tab because no branch tab maps
        // to its status, and a trashed one cannot either.
        $this->assertSame('pending', $workflow->statusFor('pending'));
        $this->assertSame('picked', $workflow->statusFor('progress'));
        $this->assertSame('served', $workflow->statusFor('completed'));

        // Care lands on the review queue by default, and an unknown slug cannot
        // jump to another role's tab.
        $this->assertSame('review', $workflow->resolveCareTab(null));
        $this->assertSame('review', $workflow->resolveCareTab('not-a-tab'));
    }

    public function test_the_review_and_trash_queues_are_counted_and_listed(): void
    {
        $workflow = new OrderWorkflowService($this->fakeSupabase([
            $this->order(1, 'awaiting_care'),
            $this->order(2, 'awaiting_care'),
            $this->order(3, 'failed'),
            $this->order(4, 'pending'),
        ]));

        $counts = $workflow->counts(3, 9);

        $this->assertSame(2, $counts['review']);
        $this->assertSame(1, $counts['trash']);
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(0, $counts['progress'], 'A held order has not been picked by anyone.');

        $this->assertCount(2, $workflow->tabRows(3, 'review', 9), 'Both held orders belong on the review queue.');
        $this->assertCount(1, $workflow->tabRows(3, 'trash', 9));
    }

    public function test_a_decision_is_only_accepted_on_a_held_order(): void
    {
        $workflow = new OrderWorkflowService($this->fakeSupabase([$this->order(1, 'pending')]));

        $this->assertFalse($workflow->decide(1, 3, 9, 'pending', 'Confirmed')['ok'], 'An already-released order cannot be released twice.');
        $this->assertFalse($workflow->decide(1, 3, 9, 'served', 'Oops')['ok'], 'Serving is the branch\'s move, not care\'s.');

        $missing = new OrderWorkflowService($this->fakeSupabase([]));
        $this->assertFalse($missing->decide(99, 3, 9, 'pending', 'Confirmed')['ok']);

        $otherBranch = new OrderWorkflowService($this->fakeSupabase([$this->order(1, 'awaiting_care')]));
        $this->assertFalse($otherBranch->decide(1, 4, 9, 'pending', 'Confirmed')['ok']);
    }
}
