<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Services\CashierScope;
use App\Services\BottleStockService;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private SupabaseService $supabase;
    private CashierScope $scope;

    private ProductVarietyStockService $varieties;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->varieties = new ProductVarietyStockService($this->supabase);
        $this->scope = new CashierScope($this->supabase);
    }

    public function index(Request $request)
    {
        $branchId = $this->scope->activeBranchId();
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $inCrossBranch = $this->scope->inCrossBranchMode();

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

        // With no status filter, show the shared pending queue plus the
        // orders this cashier picked. Once picked, an order belongs to
        // that cashier alone.
        if (!$request->status) {
            $orders = array_filter($orders, function ($o) use ($userId) {
                return ($o['status'] ?? '') === 'pending' || $this->isOwnedBy($o, $userId);
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

        return view('cashier.orders.index', compact('orders') + [
            'inCrossBranch' => $inCrossBranch,
            'activeBranchName' => $this->scope->activeBranchName(),
        ]);
    }

    public function show($orderId)
    {
        $order = $this->supabase->find('orders', $orderId, '*, customer:customers(*), items:order_items(*, product:products(id,name,brand)), cashier:users!orders_cashier_id_fkey(id,name), branch:branches(id,name,address), notes:order_notes(*)');
        if (!$order || (int) ($order['branch_id'] ?? 0) !== $this->scope->activeBranchId()) {
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
        if (isset($order['notes'])) {
            $order['notes'] = collect($order['notes'])->sortBy('created_at')->map(fn($n) => (object) $n)->values();
        }

        return view('cashier.orders.show', ['order' => (object) $order]);
    }

    private function noteFromRequest(Request $request): array
    {
        return [
            'note' => $request->note,
            'created_by' => auth()->user()->supabase_id ?? auth()->id(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Whether this cashier is the one holding the order.
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

    public function pick(Request $request, $orderId)
    {
        $request->validate(['note' => 'required|string|max:2000']);

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
            'assigned_to' => auth()->user()->supabase_id ?? auth()->id(),
            'status' => 'picked',
            'assigned_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $orderId, 'status' => 'pending']);

        // Record the progress note
        $note = $this->noteFromRequest($request);
        $note['order_id'] = $orderId;
        $this->supabase->insert('order_notes', $note);

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

    /**
     * Mark a picked order as served — the customer has received it.
     * Records the sale, deducts branch stock, then closes the order.
     */
    public function serve(Request $request, $orderId)
    {
        $request->validate(['note' => 'required|string|max:2000']);

        $order = $this->supabase->find('orders', $orderId);
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();
        if (!$order || !$this->isOwnedBy($order, $supabaseUserId) || ($order['status'] ?? '') !== 'picked') {
            abort(403);
        }

        try {
            // 1. Close the order
            $serveData = [
                'status' => 'served',
                'updated_at' => now()->toIso8601String(),
            ];
            if ($this->supabase->tableHasColumn('orders', 'served_at')) {
                $serveData['served_at'] = now()->toIso8601String();
            }
            $this->supabase->update('orders', $serveData, ['id' => $orderId, 'status' => 'picked']);

            // Record the progress note
            $note = $this->noteFromRequest($request);
            $note['order_id'] = $orderId;
            $this->supabase->insert('order_notes', $note);

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
                'select' => 'id,product_id,quantity,selling_price',
            ]));
            $stockMap = [];
            foreach ($allStock as $s) {
                $stockMap[$s['product_id']] = $s;
            }

            // 6. Prepare sale items and stock updates (no HTTP calls)
            $saleItems = [];
            $stockUpdates = [];
            $stockMovements = [];

            // Product categories and per-product variety availability — order
            // items may not state a bottling, so deduct from the largest
            // buckets first (FIFO by quantity) to keep variety counts true.
            $orderProductIds = array_values(array_unique(array_filter(array_map(fn ($oi) => (int) ($oi['product_id'] ?? 0), $orderItems))));
            $categoryMap = [];
            if ($orderProductIds) {
                foreach ($this->supabase->query('products', [
                    'select' => 'id,category',
                    'id' => 'in.(' . implode(',', $orderProductIds) . ')',
                ]) as $p) {
                    $categoryMap[(int) $p['id']] = $p['category'] ?? '';
                }
            }
            $varietyStock = $this->varieties->stockForProducts((int) $order['branch_id'], $orderProductIds, fresh: true);

            foreach ($orderItems as $orderItem) {
                $stock = $stockMap[$orderItem['product_id']] ?? null;

                if ($stock && ($stock['quantity'] ?? 0) >= ($orderItem['quantity'] ?? 0)) {
                    $qty = (int) ($orderItem['quantity'] ?? 0);
                    $isOil = ($categoryMap[(int) $orderItem['product_id']] ?? '') === 'Oil Fragrance';

                    $saleItems[] = [
                        'sale_id' => $sale['id'],
                        'product_id' => $orderItem['product_id'],
                        'quantity' => $orderItem['quantity'],
                        'unit_price' => $orderItem['unit_price'],
                        'total' => $orderItem['total'],
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ];

                    $stockUpdates[] = [
                        'id' => $stock['id'],
                        'newQty' => ($stock['quantity'] ?? 0) - $qty,
                    ];

                    $stockMovements[] = [
                        'branch_id' => $order['branch_id'],
                        'product_id' => $orderItem['product_id'],
                        'type' => 'sale',
                        'quantity' => -$qty,
                        'unit_price' => $orderItem['unit_price'],
                        'reference_type' => 'sale',
                        'reference_id' => $sale['id'],
                        'performed_by' => $supabaseUserId,
                        'notes' => "Order {$order['order_number']} served",
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ];

                    // Deduct the sold units from the product's variety buckets —
                    // largest bucket first until the quantity is consumed.
                    if ($isOil && !empty($varietyStock[(int) $orderItem['product_id']])) {
                        $remaining = $qty;
                        foreach ($varietyStock[(int) $orderItem['product_id']] as $volume => $variants) {
                            foreach ($variants as $variant => $available) {
                                if ($remaining <= 0) {
                                    break 2;
                                }
                                if ($available <= 0) {
                                    continue;
                                }
                                $take = min($available, $remaining);
                                $this->varieties->adjust((int) $order['branch_id'], (int) $orderItem['product_id'], (int) $volume, (string) $variant, -$take);
                                $varietyStock[(int) $orderItem['product_id']][$volume][$variant] -= $take;
                                $remaining -= $take;
                            }
                        }
                    }
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
                'action' => 'order_served',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('cashier.orders.index')
                ->with('success', 'Order served and sale recorded!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to serve order: ' . $e->getMessage()]);
        }
    }
}
