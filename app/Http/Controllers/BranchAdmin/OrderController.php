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
     * Branch Admin supervises the branch, so these tabs are not isolated the
     * way the other staff roles are: every order placed at this branch is
     * listed, whichever staff member picked it, together with who that was and
     * how long the order has been waiting. The extra visibility is read-only
     * though. Picking and serving still belong to the staff member who claimed
     * the order, and the service refuses both on someone else's order, so a
     * supervisor watching the queue cannot take a colleague's work over.
     */
    private const TAB_LABELS = [
        'pending' => 'Pending Orders',
        'progress' => 'Orders On Progress',
        'completed' => 'Completed Orders',
    ];

    public function index(Request $request)
    {
        $branchId = (int) auth()->user()->branch_id;
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $tab = $this->workflow->resolveTab($request->query('tab'));
        $orders = $this->workflow->tabRows($branchId, $tab, $userId, false, $request->query('q'));

        // The queue is worked oldest first, so that is the order it is read in.
        if ($tab === 'pending') {
            $orders = $orders->sortBy('created_at')->values();
        }

        return view('branch-admin.orders.index', [
            'orders' => $this->workflow->decorateWaitingTimes($orders),
            'counts' => $this->workflow->counts($branchId, $userId, false),
            'pendingWatch' => $this->workflow->pendingWatch($branchId),
            'team' => $this->workflow->teamActivity($branchId),
            'pickers' => $this->workflow->pickerNames($orders),
            'tab' => $tab,
            'tabLabels' => self::TAB_LABELS,
            'tabRoute' => 'branch-admin.orders.index',
            'nameRoute' => 'branch-admin.orders.personal-name',
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
        ]);
    }

    public function show($orderId)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $order = $this->workflow->findForShow((int) $orderId, (int) auth()->user()->branch_id, $userId, false);

        if (!$order) {
            abort(404);
        }

        $order = $this->workflow->decorateWaitingTimes(collect([$order]))->first();

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
