<?php

namespace App\Http\Controllers\CustomerCare;

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

    /** Tab slug => the single status that tab shows. */
    private const TABS = [
        'pending' => 'pending',
        'ongoing' => 'picked',
        'completed' => 'served',
    ];

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Whether the order sits with this staff member.
     *
     * assigned_to is the canonical picker column, but orders created
     * before that column existed only ever got cashier_id written, so
     * both have to be checked or older orders would look unowned.
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

    private function cast($o)
    {
        if (isset($o['cashier']) && is_array($o['cashier'])) $o['cashier'] = (object) $o['cashier'];
        if (isset($o['customer']) && is_array($o['customer'])) $o['customer'] = (object) $o['customer'];
        if (isset($o['items'])) {
            $o['items'] = collect($o['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }
        return (object) $o;
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'pending';
        $tabStatus = self::TABS[$tab];

        // Three columns is a cheap pass over the branch and gives the tab
        // counts without an extra round trip per tab.
        $counts = ['pending' => 0, 'ongoing' => 0, 'completed' => 0];
        foreach ($this->supabase->query('orders', [
            'select' => 'status,assigned_to,cashier_id',
            'branch_id' => "eq.{$branchId}",
            'limit' => 1000,
        ]) as $row) {
            $status = $row['status'] ?? 'pending';
            if ($status === 'pending') {
                $counts['pending']++;
            } elseif (in_array($status, ['picked', 'served'], true) && $this->isOwnedBy($row, $userId)) {
                $counts[$status === 'picked' ? 'ongoing' : 'completed']++;
            }
        }

        $rows = $this->supabase->query('orders', [
            'select' => '*, cashier:users!orders_cashier_id_fkey(id,name), customer:customers(id,name,phone), items:order_items(*, product:products(id,name,brand))',
            'branch_id' => "eq.{$branchId}",
            'status' => "eq.{$tabStatus}",
            'order' => 'created_at.desc',
            'limit' => 200,
        ]);

        // Pending work belongs to whoever gets there first. Picked and
        // served work belongs to the staff member who picked it, so
        // orders sitting with a colleague never reach this list.
        if ($tabStatus !== 'pending') {
            $rows = array_values(array_filter($rows, fn($o) => $this->isOwnedBy($o, $userId)));
        }

        $orders = collect($rows)->map(fn($o) => $this->cast($o));

        return view('customer-care.orders.index', [
            'orders' => $orders,
            'counts' => $counts,
            'tab' => $tab,
            'transitions' => self::TRANSITIONS,
            'userId' => $userId,
        ]);
    }

    public function show($orderId)
    {
        $order = $this->supabase->find('orders', $orderId, '*, cashier:users!orders_cashier_id_fkey(id,name), customer:customers(*), items:order_items(*, product:products(id,name,brand)), branch:branches(id,name,address), notes:order_notes(*)');
        if (!$order || $order['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        // An order another staff member has picked is not visible here at all.
        if (($order['status'] ?? 'pending') !== 'pending'
            && !$this->isOwnedBy($order, auth()->user()->supabase_id ?? auth()->id())) {
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
            $order['notes'] = collect($order['notes'])->sortBy('created_at')->map(fn($n) => (object) $n)->values();
        }

        return view('customer-care.orders.show', ['order' => (object) $order, 'transitions' => self::TRANSITIONS, 'userId' => auth()->user()->supabase_id ?? auth()->id()]);
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
        if ($next === 'served') {
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
