<?php

namespace App\Http\Controllers\SuperAdmin;

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
        $params = [
            'select' => '*, branch:branches(id,name), customer:customers(id,name,phone), cashier:users!orders_cashier_id_fkey(id,name)',
            'order' => 'created_at.desc',
            'limit' => 100,
        ];

        if ($request->status) {
            $params['status'] = "eq.{$request->status}";
        }

        if ($request->branch_id) {
            $params['branch_id'] = "eq.{$request->branch_id}";
        }

        $orders = $this->supabase->query('orders', $params);

        $orders = collect($orders)->map(function ($o) {
            if (isset($o['branch']) && is_array($o['branch'])) $o['branch'] = (object) $o['branch'];
            if (isset($o['customer']) && is_array($o['customer'])) $o['customer'] = (object) $o['customer'];
            if (isset($o['cashier']) && is_array($o['cashier'])) $o['cashier'] = (object) $o['cashier'];
            $createdAt = \Carbon\Carbon::parse($o['created_at'] ?? now());
            $o['minutes_ago'] = (int) $createdAt->diffInMinutes(now());
            $o['duration_label'] = $o['minutes_ago'] < 60 ? $o['minutes_ago'] . 'm' : ($o['minutes_ago'] < 1440 ? floor($o['minutes_ago'] / 60) . 'h ' . ($o['minutes_ago'] % 60) . 'm' : floor($o['minutes_ago'] / 1440) . 'd');
            return (object) $o;
        });

        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        // Stats
        $pending = $orders->filter(fn($o) => ($o->status ?? '') === 'pending')->count();
        $assigned = $orders->filter(fn($o) => ($o->status ?? '') === 'assigned')->count();
        $ready = $orders->filter(fn($o) => ($o->status ?? '') === 'ready')->count();
        $completed = $orders->filter(fn($o) => in_array($o->status ?? '', ['completed', 'served']))->count();

        return view('super-admin.orders.index', compact('orders', 'branches', 'pending', 'assigned', 'ready', 'completed'));
    }

    public function show($orderId)
    {
        $order = $this->supabase->find('orders', $orderId, '*, customer:customers(*), items:order_items(*, product:products(id,name,brand)), cashier:users!orders_cashier_id_fkey(id,name), branch:branches(id,name,address)');
        if (!$order) abort(404);

        if (isset($order['customer']) && is_array($order['customer'])) $order['customer'] = (object) $order['customer'];
        if (isset($order['cashier']) && is_array($order['cashier'])) $order['cashier'] = (object) $order['cashier'];
        if (isset($order['branch']) && is_array($order['branch'])) $order['branch'] = (object) $order['branch'];
        if (isset($order['items'])) {
            $order['items'] = collect($order['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }

        return view('super-admin.orders.show', ['order' => (object) $order]);
    }
}
