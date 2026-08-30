<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
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

        $orders = $this->supabase->query('orders', $params);

        // Cast for views
        $orders = collect($orders)->map(function ($o) {
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

        return view('branch-admin.orders.index', compact('orders'));
    }

    public function show($orderId)
    {
        $order = $this->supabase->find('orders', $orderId, '*, cashier:users(id,name), customer:customers(*), items:order_items(*, product:products(id,name,brand)), branch:branches(id,name,address)');
        if (!$order) abort(404);

        if (isset($order['cashier']) && is_array($order['cashier'])) $order['cashier'] = (object) $order['cashier'];
        if (isset($order['customer']) && is_array($order['customer'])) $order['customer'] = (object) $order['customer'];
        if (isset($order['branch']) && is_array($order['branch'])) $order['branch'] = (object) $order['branch'];
        if (isset($order['items'])) {
            $order['items'] = collect($order['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }

        return view('branch-admin.orders.show', ['order' => (object) $order]);
    }

    public function cancel($orderId)
    {
        $order = $this->supabase->find('orders', $orderId);
        if (!$order) abort(404);

        if (in_array($order['status'] ?? '', ['completed', 'served'])) {
            return back()->with('error', 'Cannot cancel a completed or served order.');
        }

        $this->supabase->update('orders', [
            'status' => 'cancelled',
            'cancelled_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $orderId]);

        return back()->with('success', 'Order cancelled.');
    }
}
