<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderWorkflowService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * JSON twin of SuperAdmin\OrderController — the all-branches order monitor.
 * Tabs, counts, search and the personal-name correction all go through the
 * same OrderWorkflowService the website uses, with isolation switched off
 * exactly as the website does for the Super Admin role.
 */
class AdminOrderController extends Controller
{
    private SupabaseService $supabase;

    private OrderWorkflowService $workflow;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->workflow = new OrderWorkflowService($this->supabase);
    }

    public function index(Request $request)
    {
        $branchId = $request->branch_id ? (int) $request->branch_id : null;
        $sessionUser = $request->attributes->get('staff_user');
        $userId = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;

        $tab = $this->workflow->resolveTab($request->query('tab'));
        $orders = $this->workflow->tabRows($branchId, $tab, $userId, false, $request->query('q'));

        $branches = $this->supabase->query('branches', [
            'select' => 'id,name',
            'order' => 'name.asc',
        ]);

        $pickers = $this->workflow->pickerNames($orders);

        $orders = $orders->map(function ($order) {
            $createdAt = \Carbon\Carbon::parse($order->created_at ?? now());
            $minutes = (int) $createdAt->diffInMinutes(now());
            $order->minutes_ago = $minutes;
            $order->duration_label = $minutes < 60
                ? $minutes.'m'
                : ($minutes < 1440
                    ? floor($minutes / 60).'h '.($minutes % 60).'m'
                    : floor($minutes / 1440).'d');

            return $order;
        });

        return response()->json([
            'orders' => $orders->values(),
            'counts' => $this->workflow->counts($branchId, $userId, false),
            'pickers' => $pickers,
            'branches' => $branches,
            'tab' => $tab,
            'tab_labels' => [
                'pending' => 'Pending Orders',
                'progress' => 'Orders On Progress',
                'completed' => 'Completed Orders',
            ],
        ]);
    }

    public function show(Request $request, $orderId)
    {
        $sessionUser = $request->attributes->get('staff_user');
        $userId = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;

        $order = $this->workflow->findForShow((int) $orderId, null, $userId, false);
        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json([
            'order' => $order,
            'pickers' => $this->workflow->pickerNames([$order]),
        ]);
    }

    public function personalName(Request $request, $orderId)
    {
        $request->validate([
            'personal_order_name' => 'nullable|string|max:'.OrderWorkflowService::LABEL_MAX,
        ]);

        $sessionUser = $request->attributes->get('staff_user');
        $userId = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;

        $result = $this->workflow->savePersonalName(
            (int) $orderId,
            null,
            $userId,
            $request->personal_order_name,
            true
        );

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['message'],
                'errors' => ['personal_order_name' => [$result['message']]],
            ], 422);
        }

        return response()->json(['message' => $result['message']]);
    }
}
