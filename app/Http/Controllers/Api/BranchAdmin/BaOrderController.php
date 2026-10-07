<?php

namespace App\Http\Controllers\Api\BranchAdmin;

use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;

/**
 * JSON twin of the branch-admin Orders screens. Branch Admin supervises the
 * branch, so the tabs are NOT isolated the way the other staff roles are:
 * every order placed at this branch is listed, whichever staff member
 * picked it, with waiting times, the pending watch and team activity. The
 * extra visibility is read-only though — picking and serving still belong
 * to whoever claimed the order, and OrderWorkflowService refuses both on
 * someone else's order, so a supervisor watching the queue can never take a
 * colleague's work over.
 */
class BaOrderController extends BaBaseController
{
    private const TAB_LABELS = [
        'pending' => 'Pending Orders',
        'progress' => 'Orders On Progress',
        'completed' => 'Completed Orders',
    ];

    private function workflow(): OrderWorkflowService
    {
        return new OrderWorkflowService($this->supabase);
    }

    public function index(Request $request)
    {
        $workflow = $this->workflow();
        $branchId = $this->ownBranchId($request);
        $userId = $this->performingUserId($request);

        $tab = $workflow->resolveTab($request->query('tab'));
        $orders = $workflow->tabRows($branchId, $tab, $userId, false, $request->query('q'));

        // The queue is worked oldest first, so that is the order it is read in.
        if ($tab === 'pending') {
            $orders = $orders->sortBy('created_at')->values();
        }

        $decorated = $workflow->decorateWaitingTimes($orders);

        return response()->json([
            'orders' => collect($decorated)->map(fn ($o) => (array) $o)->values()->all(),
            'counts' => $workflow->counts($branchId, $userId, false),
            'pendingWatch' => $workflow->pendingWatch($branchId),
            'team' => $workflow->teamActivity($branchId),
            'pickers' => $workflow->pickerNames($orders),
            'tab' => $tab,
            'tabLabels' => self::TAB_LABELS,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function show(Request $request, int $orderId)
    {
        $workflow = $this->workflow();
        $userId = $this->performingUserId($request);
        $order = $workflow->findForShow($orderId, $this->ownBranchId($request), $userId, false);

        if (! $order) {
            abort(404, 'Order not found.');
        }

        $order = $workflow->decorateWaitingTimes(collect([$order]))->first();

        return response()->json([
            'order' => (array) $order,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'canName' => $workflow->isOwnedBy((array) $order, $userId),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * Move an order forward. Only picked and served are accepted: an order
     * never goes back to pending, so a crafted request cannot un-claim work
     * that has already been picked.
     */
    public function updateStatus(Request $request, int $orderId)
    {
        $workflow = $this->workflow();
        $request->validate($workflow->statusChangeRules());

        $next = $request->input('status');

        if (! in_array($next, ['picked', 'served'], true)) {
            $this->fail(['status' => 'An order can only move to picked or served.']);
        }

        $userId = $this->performingUserId($request);
        $branchId = $this->ownBranchId($request);

        $result = $next === 'picked'
            ? $workflow->pick($orderId, $branchId, $userId, (string) $request->input('note', ''))
            : $workflow->serve($orderId, $branchId, $userId, (string) $request->input('note', ''));

        if (! $result['ok']) {
            $this->fail(['error' => $result['message']]);
        }

        return response()->json(['message' => $result['message']]);
    }

    /** Save, edit or clear this admin's personal name for the order. */
    public function personalName(Request $request, int $orderId)
    {
        $workflow = $this->workflow();
        $request->validate([
            'personal_order_name' => 'nullable|string|max:'.OrderWorkflowService::LABEL_MAX,
        ]);

        $result = $workflow->savePersonalName(
            $orderId,
            $this->ownBranchId($request),
            $this->performingUserId($request),
            $request->input('personal_order_name')
        );

        if (! $result['ok']) {
            $this->fail(['error' => $result['message']]);
        }

        return response()->json(['message' => $result['message']]);
    }
}
