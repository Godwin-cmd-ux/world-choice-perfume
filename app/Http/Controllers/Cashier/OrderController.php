<?php

namespace App\Http\Controllers\Cashier;

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
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $params = [
            'select' => '*, customer:customers(id,name,phone), items:order_items(*, product:products(id,name,brand))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->status) {
            $params['status'] = "eq.{$request->status}";
        }

        $orders = $this->supabase->query('orders', $params);

        // If no status filter, show pending and my assigned orders
        if (!$request->status) {
            $orders = array_filter($orders, function ($o) use ($userId) {
                return ($o['status'] ?? '') === 'pending'
                    || (($o['cashier_id'] ?? '') == $userId && in_array($o['status'] ?? '', ['assigned', 'ready', 'completed']));
            });
        }

        $orders = collect(array_values($orders))->map(function ($o) {
            if (isset($o['customer']) && is_array($o['customer'])) $o['customer'] = (object) $o['customer'];
            if (isset($o['items'])) {
                $o['items'] = collect($o['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $o;
        });

        return view('cashier.orders.index', compact('orders'));
    }

    public function show($orderId)
    {
        $order = $this->supabase->find('orders', $orderId, '*, customer:customers(*), items:order_items(*, product:products(id,name,brand)), cashier:users(id,name), branch:branches(id,name,address)');
        if (!$order || $order['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        if (isset($order['customer']) && is_array($order['customer'])) $order['customer'] = (object) $order['customer'];
        if (isset($order['cashier']) && is_array($order['cashier'])) $order['cashier'] = (object) $order['cashier'];
        if (isset($order['branch']) && is_array($order['branch'])) $order['branch'] = (object) $order['branch'];
        if (isset($order['items'])) {
            $order['items'] = collect($order['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }

        return view('cashier.orders.show', ['order' => (object) $order]);
    }

    public function pick($orderId)
    {
        $order = $this->supabase->find('orders', $orderId);
        if (!$order || $order['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        if (($order['status'] ?? '') !== 'pending') {
            return back()->with('error', 'This order is no longer available for picking.');
        }

        // Try to assign
        $this->supabase->update('orders', [
            'cashier_id' => auth()->user()->supabase_id ?? auth()->id(),
            'status' => 'assigned',
            'assigned_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $orderId, 'status' => 'pending']);

        // Audit
        $this->supabase->insert('audit_logs', [
            'user_id' => auth()->user()->supabase_id ?? auth()->id(),
            'action' => 'order_picked',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('cashier.orders.show', $orderId)
            ->with('success', 'Order picked successfully. Prepare the order.');
    }

    public function markReady($orderId)
    {
        $order = $this->supabase->find('orders', $orderId);
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();
        if (!$order || $order['cashier_id'] != $supabaseUserId || ($order['status'] ?? '') !== 'assigned') {
            abort(403);
        }

        $this->supabase->update('orders', [
            'status' => 'ready',
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $orderId]);

        $this->supabase->insert('audit_logs', [
            'user_id' => auth()->user()->supabase_id ?? auth()->id(),
            'action' => 'order_ready',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return back()->with('success', 'Order marked as ready for pickup.');
    }

    public function complete($orderId)
    {
        $order = $this->supabase->find('orders', $orderId);
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();
        if (!$order || $order['cashier_id'] != $supabaseUserId || ($order['status'] ?? '') !== 'ready') {
            abort(403);
        }

        try {
            // 1. Mark order as completed
            $this->supabase->update('orders', [
                'status' => 'completed',
                'completed_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $orderId]);

            // 2. Generate sale number (timestamp-based, no query needed)
            $saleNumber = 'SALE-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

            // 3. Create sale (1 HTTP call)
            $sale = $this->supabase->insert('sales', [
                'sale_number' => $saleNumber,
                'branch_id' => $order['branch_id'],
                'cashier_id' => $supabaseUserId,
                'customer_id' => $order['customer_id'] ?? null,
                'subtotal' => $order['total'],
                'total' => $order['total'],
                'payment_status' => 'paid',
                'notes' => "Converted from order {$order['order_number']}",
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            // 4. Fetch all order items in ONE query
            $orderItems = $this->supabase->queryFresh('order_items', [
                'order_id' => "eq.{$orderId}",
            ]);

            // 5. Fetch ALL stock for this branch in ONE query (instead of N)
            $allStock = collect($this->supabase->query('branch_stock', [
                'branch_id' => "eq.{$order['branch_id']}",
                'select' => 'id,product_id,quantity,buying_cost',
            ]));
            $stockMap = [];
            foreach ($allStock as $s) {
                $stockMap[$s['product_id']] = $s;
            }

            // 6. Prepare sale items and stock updates (no HTTP calls)
            $saleItems = [];
            $stockUpdates = [];
            $stockMovements = [];

            foreach ($orderItems as $orderItem) {
                $stock = $stockMap[$orderItem['product_id']] ?? null;

                if ($stock && ($stock['quantity'] ?? 0) >= ($orderItem['quantity'] ?? 0)) {
                    $saleItems[] = [
                        'sale_id' => $sale['id'],
                        'product_id' => $orderItem['product_id'],
                        'quantity' => $orderItem['quantity'],
                        'unit_price' => $orderItem['unit_price'],
                        'unit_cost' => $stock['buying_cost'],
                        'total' => $orderItem['total'],
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ];

                    $stockUpdates[] = [
                        'id' => $stock['id'],
                        'newQty' => ($stock['quantity'] ?? 0) - ($orderItem['quantity'] ?? 0),
                    ];

                    $stockMovements[] = [
                        'branch_id' => $order['branch_id'],
                        'product_id' => $orderItem['product_id'],
                        'type' => 'sale',
                        'quantity' => -($orderItem['quantity'] ?? 0),
                        'unit_cost' => $stock['buying_cost'],
                        'unit_price' => $orderItem['unit_price'],
                        'reference_type' => 'sale',
                        'reference_id' => $sale['id'],
                        'performed_by' => $supabaseUserId,
                        'notes' => "Order {$order['order_number']} completed",
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ];
                }
            }

            // 7. Batch insert sale items (1 HTTP call instead of N)
            if (!empty($saleItems)) {
                $this->supabase->insertMany('sale_items', $saleItems);
            }

            // 8. Update stock quantities
            foreach ($stockUpdates as $su) {
                $this->supabase->update('branch_stock', [
                    'quantity' => $su['newQty'],
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => $su['id']]);
            }

            // 9. Batch insert stock movements (1 HTTP call instead of N)
            if (!empty($stockMovements)) {
                $this->supabase->insertMany('stock_movements', $stockMovements);
            }

            // 10. Audit log
            $this->supabase->insert('audit_logs', [
                'user_id' => $supabaseUserId,
                'action' => 'order_completed',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('cashier.orders.index')
                ->with('success', 'Order completed and sale recorded!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to complete order: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark order as served — customer has received the order.
     * Prevents the customer from claiming the order again.
     */
    public function serve($orderId)
    {
        $order = $this->supabase->find('orders', $orderId);
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

        if (!$order || $order['cashier_id'] != $supabaseUserId || ($order['status'] ?? '') !== 'completed') {
            abort(403);
        }

        $this->supabase->update('orders', [
            'status' => 'served',
            'served_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $orderId]);

        $this->supabase->insert('audit_logs', [
            'user_id' => $supabaseUserId,
            'action' => 'order_served',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('cashier.orders.index')
            ->with('success', 'Order marked as served. Customer has received their order.');
    }
}
