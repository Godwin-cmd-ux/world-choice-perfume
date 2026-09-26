<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\OrderWorkflowService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Super Admin is the one role that is not isolated: these tabs list every
     * order for every staff member, not just the ones this admin picked.
     */
    private const TAB_LABELS = [
        'pending' => 'Pending Orders',
        'progress' => 'Orders On Progress',
        'completed' => 'Completed Orders',
    ];

    private SupabaseService $supabase;

    private OrderWorkflowService $workflow;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->workflow = new OrderWorkflowService($this->supabase);
    }

    public function index(Request $request)
    {
        // No branch selected means every branch, which is the point of the
        // monitor view.
        $branchId = $request->branch_id ? (int) $request->branch_id : null;
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $tab = $this->workflow->resolveTab($request->query('tab'));
        $orders = $this->workflow->tabRows($branchId, $tab, $userId, false, $request->query('q'));

        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        $pickers = $this->workflow->pickerNames($orders);

        // The monitor view has always shown how long ago each order arrived.
        $orders = $orders->map(function ($order) {
            $createdAt = \Carbon\Carbon::parse($order->created_at ?? now());
            $minutes = (int) $createdAt->diffInMinutes(now());
            $order->minutes_ago = $minutes;
            $order->duration_label = $minutes < 60
                ? $minutes . 'm'
                : ($minutes < 1440
                    ? floor($minutes / 60) . 'h ' . ($minutes % 60) . 'm'
                    : floor($minutes / 1440) . 'd');

            return $order;
        });

        return view('super-admin.orders.index', [
            'orders' => $orders,
            'counts' => $this->workflow->counts($branchId, $userId, false),
            'pickers' => $pickers,
            'branches' => $branches,
            'selectedBranchId' => $branchId,
            'tab' => $tab,
            'tabLabels' => self::TAB_LABELS,
            'tabRoute' => 'super-admin.orders.index',
            'nameRoute' => 'super-admin.orders.personal-name',
        ]);
    }

    public function show($orderId)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $order = $this->workflow->findForShow((int) $orderId, null, $userId, false);

        if (!$order) {
            abort(404);
        }

        $pickers = $this->workflow->pickerNames([$order]);

        return view('super-admin.orders.show', [
            'order' => $order,
            'pickers' => $pickers,
            'nameRoute' => 'super-admin.orders.personal-name',
        ]);
    }

    /**
     * Super Admin may correct a label on any order, for any branch. The service
     * still refuses to touch anything else on the row.
     */
    public function personalName(Request $request, $orderId)
    {
        $request->validate([
            'personal_order_name' => 'nullable|string|max:' . OrderWorkflowService::LABEL_MAX,
        ]);

        $result = $this->workflow->savePersonalName(
            (int) $orderId,
            null,
            auth()->user()->supabase_id ?? auth()->id(),
            $request->personal_order_name,
            true
        );

        if (!$result['ok']) {
            return back()->with('error', $result['message'])->withInput();
        }

        return back()->with('success', $result['message']);
    }
}
