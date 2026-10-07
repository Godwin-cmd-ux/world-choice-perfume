<?php

namespace App\Http\Controllers\Api\CustomerCare;

use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;

/**
 * JSON twin of the customer-care Orders screens. The three tabs, ownership
 * rules and allowed transitions all come from OrderWorkflowService — the
 * same service the website's customer care controller uses — so the app can
 * never move an order the website would refuse.
 */
class CcOrderController extends CcBaseController
{
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
        $orders = $workflow->tabRows($branchId, $tab, $userId, true, $request->query('q'));

        return response()->json([
            'orders' => collect($orders)->map(fn ($o) => (array) $o)->values()->all(),
            'counts' => $workflow->counts($branchId, $userId),
            'pickers' => $workflow->pickerNames($orders),
            'tab' => $tab,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function show(Request $request, int $orderId)
    {
        $workflow = $this->workflow();
        $userId = $this->performingUserId($request);
        $order = $workflow->findForShow($orderId, $this->ownBranchId($request), $userId, true);

        if (! $order) {
            abort(404, 'Order not found.');
        }

        return response()->json([
            'order' => $order,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'canName' => $workflow->isOwnedBy((array) $order, $userId),
        ]);
    }

    /**
     * Move an order forward. Only picked and served are accepted: an order
     * never goes back to pending.
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

    /** Save, edit or clear this member's personal name for the order. */
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
