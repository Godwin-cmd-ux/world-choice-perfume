<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\OrderWorkflowService;
use App\Services\StockManagerScope;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private SupabaseService $supabase;

    private StockManagerScope $scope;

    private OrderWorkflowService $workflow;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->scope = new StockManagerScope($this->supabase);
        $this->workflow = new OrderWorkflowService($this->supabase);
    }

    /**
     * The three tabs. Pending is the shared queue anyone can claim; picked and
     * served orders are only shown to the stock manager who picked them.
     */
    public function index(Request $request)
    {
        $branchId = $this->scope->activeBranchId();
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $tab = $this->workflow->resolveTab($request->query('tab'));
        $orders = $this->workflow->tabRows($branchId, $tab, $userId, true, $request->query('q'));

        return view('stock-manager.orders.index', [
            'orders' => $orders,
            'counts' => $this->workflow->counts($branchId, $userId),
            'pickers' => $this->workflow->pickerNames($orders),
            'tab' => $tab,
            'tabRoute' => 'stock-manager.orders.index',
            'nameRoute' => 'stock-manager.orders.personal-name',
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
            'activeBranchName' => $this->scope->activeBranchName(),
        ]);
    }

    public function show($orderId)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $order = $this->workflow->findForShow((int) $orderId, $this->scope->activeBranchId(), $userId);

        if (!$order) {
            abort(404);
        }

        return view('stock-manager.orders.show', [
            'order' => $order,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'nameRoute' => 'stock-manager.orders.personal-name',
            'canName' => $this->workflow->isOwnedBy((array) $order, $userId),
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
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
        $branchId = $this->scope->activeBranchId();

        $result = $next === 'picked'
            ? $this->workflow->pick((int) $orderId, $branchId, $userId, $request->note)
            : $this->workflow->serve((int) $orderId, $branchId, $userId, $request->note);

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Save, edit or clear this stock manager's personal name for the order.
     */
    public function personalName(Request $request, $orderId)
    {
        $request->validate([
            'personal_order_name' => 'nullable|string|max:' . OrderWorkflowService::LABEL_MAX,
        ]);

        $result = $this->workflow->savePersonalName(
            (int) $orderId,
            $this->scope->activeBranchId(),
            auth()->user()->supabase_id ?? auth()->id(),
            $request->personal_order_name
        );

        if (!$result['ok']) {
            return back()->with('error', $result['message'])->withInput();
        }

        return back()->with('success', $result['message']);
    }
}
