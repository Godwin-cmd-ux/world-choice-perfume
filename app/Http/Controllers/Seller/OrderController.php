<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const TRANSITIONS = [
        'pending' => ['assigned', 'cancelled'],
        'assigned' => ['ready', 'cancelled'],
        'ready' => ['completed', 'cancelled'],
        'completed' => ['served'],
        'served' => [],
        'cancelled' => [],
    ];

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, cashier:users(id,name), customer:customers(id,name,phone), items:order_items(*, product:products(id,name,brand))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->status) {
            $params['status'] = "eq.{$request->status}";
        }

        $orders = collect($this->supabase->query('orders', $params))->map(function ($o) {
            if (isset($o['cashier']) && is_array($o['cashier'])) $o['cashier'] = (object) $o['cashier'];
            if (isset($o['customer']) && is_array($o['customer'])) $o['customer'] = (object) $o['customer'];
            if (isset($o['items'])) {
                $o['items'] = collect($o['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $o;
        });

        return view('seller.orders.index', ['orders' => $orders, 'transitions' => self::TRANSITIONS]);
    }

    public function show($orderId)
    {
        $order = $this->supabase->find('orders', $orderId, '*, cashier:users(id,name), customer:customers(*), items:order_items(*, product:products(id,name,brand)), branch:branches(id,name,address)');
        if (!$order || $order['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        if (isset($order['cashier']) && is_array($order['cashier'])) $order['cashier'] = (object) $order['cashier'];
        if (isset($order['customer']) && is_array($order['customer'])) $order['customer'] = (object) $order['customer'];
        if (isset($order['branch']) && is_array($order['branch'])) $order['branch'] = (object) $order['branch'];
        if (isset($order['items'])) {
            $order['items'] = collect($order['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }

        return view('seller.orders.show', ['order' => (object) $order, 'transitions' => self::TRANSITIONS]);
    }

    public function updateStatus(Request $request, $orderId)
    {
        $request->validate(['status' => 'required|in:pending,assigned,ready,completed,served,cancelled']);

        $order = $this->supabase->find('orders', $orderId);
        if (!$order || $order['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        $current = $order['status'] ?? 'pending';
        $next = $request->status;

        if (!in_array($next, self::TRANSITIONS[$current] ?? [], true)) {
            return back()->with('error', 'Invalid status transition.');
        }

        $this->supabase->update('orders', [
            'status' => $next,
            'cancelled_at' => $next === 'cancelled' ? now()->toIso8601String() : ($order['cancelled_at'] ?? null),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $orderId]);

        (new \App\Services\AuditService())->recordCriticalAction(
            'order_status_changed',
            'order_status_changed',
            'Order Status Changed',
            "Order {$order['order_number']} status changed to {$next}.",
            ['order_id' => $orderId, 'order_number' => $order['order_number'], 'total' => $order['total'] ?? null],
            'orders',
            (string) $orderId,
            ['status' => $current],
            ['status' => $next]
        );

        return back()->with('success', 'Order status updated.');
    }
}