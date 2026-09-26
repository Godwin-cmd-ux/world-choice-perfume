<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\OrderWorkflowService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private SupabaseService $supabase;

    private OrderWorkflowService $workflow;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->workflow = new OrderWorkflowService($this->supabase);
    }

    /**
     * The three tabs.
     *
     * Branch Admin used to get a single branch-wide list of every order. It is
     * now isolated like every other staff role: the pending queue is shared,
     * but picked and served orders only appear for the admin who picked them.
     * Super Admin is the role that keeps the whole-branch view.
     */
    public function index(Request $request)
    {
        $branchId = (int) auth()->user()->branch_id;
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $tab = $this->workflow->resolveTab($request->query('tab'));
        $orders = $this->workflow->tabRows($branchId, $tab, $userId, true, $request->query('q'));

        return view('branch-admin.orders.index', [
            'orders' => $orders,
            'counts' => $this->workflow->counts($branchId, $userId),
            'pickers' => $this->workflow->pickerNames($orders),
            'tab' => $tab,
            'tabRoute' => 'branch-admin.orders.index',
            'nameRoute' => 'branch-admin.orders.personal-name',
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
        ]);
    }

    public function show($orderId)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $order = $this->workflow->findForShow((int) $orderId, (int) auth()->user()->branch_id, $userId);

        if (!$order) {
            abort(404);
        }

        return view('branch-admin.orders.show', [
            'order' => $order,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'nameRoute' => 'branch-admin.orders.personal-name',
            'canName' => $this->workflow->isOwnedBy((array) $order, $userId),
        ]);
    }

    /**
     * Move an order forward. Only picked and served are accepted: an order
     * never goes back to pending, so a crafted request cannot un-claim work
     * that has already been picked.
     */
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate($this->workflow->statusChangeRules());

        $next = $request->status;

        if (!in_array($next, ['picked', 'served'], true)) {
            return back()->with('error', 'An order can only move to picked or served.');
        }

        $userId = auth()->user()->supabase_id ?? auth()->id();
        $branchId = (int) auth()->user()->branch_id;

        $result = $next === 'picked'
            ? $this->workflow->pick((int) $orderId, $branchId, $userId, $request->note)
            : $this->workflow->serve((int) $orderId, $branchId, $userId, $request->note);

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Save, edit or clear this admin's personal name for the order.
     */
    public function personalName(Request $request, $orderId)
    {
        $request->validate([
            'personal_order_name' => 'nullable|string|max:' . OrderWorkflowService::LABEL_MAX,
        ]);

        $result = $this->workflow->savePersonalName(
            (int) $orderId,
            (int) auth()->user()->branch_id,
            auth()->user()->supabase_id ?? auth()->id(),
            $request->personal_order_name
        );

        if (!$result['ok']) {
            return back()->with('error', $result['message'])->withInput();
        }

        return back()->with('success', $result['message']);
    }
}
