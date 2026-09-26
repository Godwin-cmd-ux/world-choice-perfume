<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const TRANSITIONS = [
        'pending' => ['picked'],
        'picked' => ['served'],
        'served' => [],
    ];

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Whether this staff member is the one holding the order.
     *
     * assigned_to is the canonical picker column; cashier_id is the older one
     * and still the only value written on orders placed before it existed.
     */
    private function isOwnedBy($order, $userId): bool
    {
        foreach (['assigned_to', 'cashier_id'] as $column) {
            if (isset($order[$column]) && (string) $order[$column] === (string) $userId) {
                return true;
            }
        }

        return false;
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, cashier:users!orders_cashier_id_fkey(id,name), customer:customers(id,name,phone), items:order_items(*, product:products(id,name,brand))',
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

        return view('seller.orders.index', ['orders' => $orders, 'transitions' => self::TRANSITIONS, 'userId' => auth()->user()->supabase_id ?? auth()->id()]);
    }

    public function show($orderId)
    {
        $order = $this->supabase->find('orders', $orderId, '*, cashier:users!orders_cashier_id_fkey(id,name), customer:customers(*), items:order_items(*, product:products(id,name,brand)), branch:branches(id,name,address), notes:order_notes(*)');
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
        if (isset($order['notes'])) {
            $order['notes'] = collect($order['notes'])->map(fn($n) => (object) $n);
        }

        return view('seller.orders.show', ['order' => (object) $order, 'transitions' => self::TRANSITIONS, 'userId' => auth()->user()->supabase_id ?? auth()->id()]);
    }

    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,picked,served',
            'note' => 'required|string|max:2000',
        ]);

        $order = $this->supabase->find('orders', $orderId);
        if (!$order || $order['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        $current = $order['status'] ?? 'pending';
        $next = $request->status;
        $userId = auth()->user()->supabase_id ?? auth()->id();

        if (!in_array($next, self::TRANSITIONS[$current] ?? [], true)) {
            return back()->with('error', 'Invalid status transition.');
        }

        if ($current !== 'pending' && !$this->isOwnedBy($order, $userId)) {
            return back()->with('error', 'This order was picked by another staff member. Only they can update it.');
        }

        $updateData = [
            'status' => $next,
            'updated_at' => now()->toIso8601String(),
        ];
        if ($next === 'picked') {
            $updateData['assigned_to'] = $userId;
            $updateData['cashier_id'] = $userId;
            $updateData['assigned_at'] = now()->toIso8601String();
        }
        if ($next === 'served' && $this->supabase->tableHasColumn('orders', 'served_at')) {
            $updateData['served_at'] = now()->toIso8601String();
        }

        $updated = $this->supabase->update('orders', $updateData, ['id' => $orderId, 'status' => $current]);

        if (empty($updated)) {
            // Optimistic-lock missed the row: another staff member changed it.
            // Re-read and reflect the real status instead of guessing.
            $liveStatus = $this->supabase->find('orders', $orderId)['status'] ?? null;

            if ($liveStatus === $next) {
                return back()->with('success', 'Order status is already ' . $next . '.');
            }

            $reason = $this->supabase->lastErrorMessage();
            $detail = $reason ? ' (' . $reason . ')' : '';
            return back()->with('error', 'The order is now "' . ($liveStatus ?: 'unknown') . '". Please refresh and try again.' . $detail);
        }

        $this->supabase->insert('order_notes', [
            'order_id' => $orderId,
            'note' => $request->note,
            'created_by' => $userId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

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