<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Services\CashierScope;
use App\Services\BottleStockService;
use App\Services\OrderWorkflowService;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private SupabaseService $supabase;
    private CashierScope $scope;

    private ProductVarietyStockService $varieties;

    private OrderWorkflowService $workflow;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->varieties = new ProductVarietyStockService($this->supabase);
        $this->scope = new CashierScope($this->supabase);
        $this->workflow = new OrderWorkflowService($this->supabase);
    }

    /**
     * The three tabs. Pending is the shared queue anyone can claim; picked and
     * served orders are only ever shown to the cashier who picked them.
     */
    public function index(Request $request)
    {
        $branchId = $this->scope->activeBranchId();
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $tab = $this->workflow->resolveTab($request->query('tab'));
        $orders = $this->workflow->tabRows($branchId, $tab, $userId, true, $request->query('q'));

        return view('cashier.orders.index', [
            'orders' => $orders,
            'counts' => $this->workflow->counts($branchId, $userId),
            'pickers' => $this->workflow->pickerNames($orders),
            'tab' => $tab,
            'tabRoute' => 'cashier.orders.index',
            'nameRoute' => 'cashier.orders.personal-name',
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

        return view('cashier.orders.show', [
            'order' => $order,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'nameRoute' => 'cashier.orders.personal-name',
            'canName' => $this->workflow->isOwnedBy((array) $order, $userId),
        ]);
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
     * Claim a pending order.
     *
     * The claim itself lives in the workflow service so the compare-and-set is
     * checked: if another cashier picked this order a moment earlier the write
     * matches no row, and the cashier is told it has gone rather than being
     * shown a success that did not happen.
     */
    public function pick(Request $request, $orderId)
    {
        $request->validate(['note' => 'required|string|max:2000']);

        // The branch the cashier is currently working in, not their home
        // branch: the list they clicked "Pick" from is scoped to
        // activeBranchId(), so the write has to use the same branch or the
        // order is rejected as "not found" while it is plainly on screen.
        $result = $this->workflow->pick(
            (int) $orderId,
            (int) $this->scope->activeBranchId(),
            auth()->user()->supabase_id ?? auth()->id(),
            $request->note
        );

        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('cashier.orders.show', $orderId)->with('success', $result['message']);
    }

    /**
     * Save, edit or clear this cashier's personal name for the order.
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

    /**
     * Mark a picked order as served — the customer has received it.
     * Records the sale, deducts branch stock, then closes the order.
     */
    public function serve(Request $request, $orderId)
    {
        $request->validate(['note' => 'required|string|max:2000']);

        $order = $this->supabase->find('orders', $orderId);
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();
        if (!$order || !$this->workflow->isOwnedBy($order, $supabaseUserId) || ($order['status'] ?? '') !== 'picked') {
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
            if ($this->supabase->tableHasColumn('orders', 'completed_at')) {
                $serveData['completed_at'] = now()->toIso8601String();
            }
            $closed = $this->supabase->update('orders', $serveData, ['id' => $orderId, 'status' => 'picked']);

            if (empty($closed)) {
                // The row was no longer 'picked' by the time we wrote, so this
                // order was served from another tab or another cashier between
                // the check above and here. Stop before a second sale, a second
                // stock deduction and a second movement row get recorded.
                return back()->with('error', 'This order has already been served. No sale was recorded.');
            }

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
