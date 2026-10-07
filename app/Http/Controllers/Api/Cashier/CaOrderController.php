<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;

/**
 * JSON twin of the cashier Orders screens. The tabs run ISOLATED, the same
 * way the website's cashier controller calls the workflow: pending is the
 * shared branch queue anyone may claim, and picked/served orders are only
 * listed for the cashier who claimed them.
 *
 * Two routes differ from the other staff roles, exactly as on the website:
 * pick and serve are dedicated endpoints that each REQUIRE a note, and
 * serve is not the workflow's plain close — it is the full handover: the
 * order is closed, the sale is recorded, branch stock (and the exact
 * bottling buckets) are deducted and the movements are written, ending in
 * the website's "order_served" audit.
 *
 * While an HQ monitor watches another branch every write is refused — the
 * website's readonly middleware does it there, assertNotMonitoring() here.
 */
class CaOrderController extends CaBaseController
{
    private function workflow(): OrderWorkflowService
    {
        return new OrderWorkflowService($this->supabase);
    }

    public function index(Request $request)
    {
        $workflow = $this->workflow();
        $branchId = $this->activeBranchId($request);
        $userId = $this->performingUserId($request);

        $tab = $workflow->resolveTab($request->query('tab'));
        $orders = $workflow->tabRows($branchId, $tab, $userId, true, $request->query('q'));

        return response()->json([
            'orders' => collect($orders)->map(fn ($o) => (array) $o)->values()->all(),
            'counts' => $workflow->counts($branchId, $userId, true),
            'pickers' => $workflow->pickerNames($orders),
            'tab' => $tab,
            'tabLabels' => OrderWorkflowService::TAB_LABELS,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function show(Request $request, int $orderId)
    {
        $workflow = $this->workflow();
        $userId = $this->performingUserId($request);
        $order = $workflow->findForShow($orderId, $this->activeBranchId($request), $userId, true);

        if (! $order) {
            abort(404, 'Order not found.');
        }

        // Name the bottling the customer chose so it is packed from the
        // right size and packaging (the website decorates the same way).
        $varieties = $this->varieties();
        foreach ($order->items ?? [] as $item) {
            $item->variety_label = ((int) ($item->volume ?? 0)) > 0 && (string) ($item->variant ?? '') !== ''
                ? $varieties->pickLabel((int) $item->volume, (string) $item->variant)
                : '';
        }

        return response()->json([
            'order' => (array) $order,
            'transitions' => OrderWorkflowService::TRANSITIONS,
            'userId' => $userId,
            'canName' => $workflow->isOwnedBy((array) $order, $userId),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * Claim a pending order. The note is required (the website validates
     * the very same rule before calling the workflow), and the compare-and-
     * set inside the workflow means the loser of a race is told the order
     * has gone rather than shown a success that did not happen.
     */
    public function pick(Request $request, int $orderId)
    {
        $this->assertNotMonitoring($request);
        $request->validate(['note' => 'required|string|max:2000']);

        $result = $this->workflow()->pick(
            $orderId,
            $this->activeBranchId($request),
            $this->performingUserId($request),
            (string) $request->input('note')
        );

        if (! $result['ok']) {
            $this->fail(['error' => $result['message']]);
        }

        return response()->json(['message' => $result['message']]);
    }

    /**
     * Mark a picked order as served — the customer has received it.
     * Records the sale, deducts branch stock, then closes the order.
     * Ported from the website's Cashier\OrderController::serve.
     */
    public function serve(Request $request, int $orderId)
    {
        $this->assertNotMonitoring($request);
        $request->validate(['note' => 'required|string|max:2000']);

        $order = $this->supabase->find('orders', $orderId);
        $supabaseUserId = $this->performingUserId($request);
        if (! $order || ! $this->workflow()->isOwnedBy($order, $supabaseUserId) || ($order['status'] ?? '') !== 'picked') {
            abort(403, 'Only the cashier who picked this order can serve it.');
        }

        try {
            // 1. Close the order (compare-and-set on status = picked).
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
                $this->fail(['error' => 'This order has already been served. No sale was recorded.']);
            }

            // Record the progress note.
            $now = now()->toIso8601String();
            $this->supabase->insert('order_notes', [
                'order_id' => $orderId,
                'note' => $request->input('note'),
                'created_by' => $supabaseUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 2. Generate sale number (timestamp-based, no query needed).
            $saleNumber = 'SALE-'.date('YmdHis').'-'.strtoupper(substr(uniqid(), -4));

            // 3. Create the sale.
            $sale = $this->supabase->insert('sales', [
                'sale_number' => $saleNumber,
                'branch_id' => $order['branch_id'],
                'cashier_id' => $supabaseUserId,
                'customer_id' => $order['customer_id'] ?? null,
                'subtotal' => $order['total'],
                'total' => $order['total'],
                'payment_status' => 'paid',
                'notes' => "Converted from order {$order['order_number']}",
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (! $sale) {
                $this->fail(['error' => 'The sale for this order could not be recorded. Please try again.']);
            }

            // 4. Fetch all order items in ONE query.
            $orderItems = $this->supabase->queryFresh('order_items', [
                'order_id' => "eq.{$orderId}",
            ]);

            // 5. Fetch ALL stock for this branch in ONE query.
            $allStock = collect($this->supabase->query('branch_stock', [
                'branch_id' => "eq.{$order['branch_id']}",
                'select' => 'id,product_id,quantity,selling_price',
            ]));
            $stockMap = [];
            foreach ($allStock as $s) {
                $stockMap[$s['product_id']] = $s;
            }

            // 6. Prepare sale items and stock updates.
            $saleItems = [];
            $stockUpdates = [];
            $stockMovements = [];

            // Product categories and per-product variety availability — order
            // items may not state a bottling, so deduct from the largest
            // buckets first (FIFO by quantity) to keep variety counts true.
            $varieties = $this->varieties();
            $orderProductIds = array_values(array_unique(array_filter(array_map(fn ($oi) => (int) ($oi['product_id'] ?? 0), $orderItems))));
            $categoryMap = [];
            if ($orderProductIds) {
                foreach ($this->supabase->query('products', [
                    'select' => 'id,category',
                    'id' => 'in.('.implode(',', $orderProductIds).')',
                ]) as $p) {
                    $categoryMap[(int) $p['id']] = $p['category'] ?? '';
                }
            }
            $varietyStock = $varieties->stockForProducts((int) $order['branch_id'], $orderProductIds, fresh: true);
            $saleItemsHaveVariety = $this->supabase->tableHasColumn('sale_items', 'volume')
                && $this->supabase->tableHasColumn('sale_items', 'variant');

            foreach ($orderItems as $orderItem) {
                $stock = $stockMap[$orderItem['product_id']] ?? null;

                if ($stock && ($stock['quantity'] ?? 0) >= ($orderItem['quantity'] ?? 0)) {
                    $qty = (int) ($orderItem['quantity'] ?? 0);
                    $productId = (int) $orderItem['product_id'];
                    $isOil = ($categoryMap[$productId] ?? '') === 'Oil Fragrance';

                    // The customer's choice of bottling, when the order stated one.
                    $pickVolume = (int) ($orderItem['volume'] ?? 0);
                    $pickVariant = (string) ($orderItem['variant'] ?? '');

                    $saleItem = [
                        'sale_id' => $sale['id'],
                        'product_id' => $orderItem['product_id'],
                        'quantity' => $orderItem['quantity'],
                        'unit_price' => $orderItem['unit_price'],
                        'total' => $orderItem['total'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($saleItemsHaveVariety && $pickVolume > 0 && $pickVariant !== '') {
                        $saleItem['volume'] = $pickVolume;
                        $saleItem['variant'] = $pickVariant;
                    }
                    $saleItems[] = $saleItem;

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
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Deduct the sold units from the product's variety buckets.
                    // The bottling the customer ordered is used first; a line
                    // that states none falls back to the largest buckets so the
                    // variety counts still come out right.
                    if ($isOil && ! empty($varietyStock[$productId])) {
                        $remaining = $qty;
                        if ($pickVolume > 0 && $pickVariant !== '') {
                            $take = min((int) ($varietyStock[$productId][$pickVolume][$pickVariant] ?? 0), $remaining);
                            if ($take > 0) {
                                $varieties->adjust((int) $order['branch_id'], $productId, $pickVolume, $pickVariant, -$take);
                                $varietyStock[$productId][$pickVolume][$pickVariant] -= $take;
                                $remaining -= $take;
                            }
                        }
                        foreach ($varietyStock[$productId] as $volume => $variants) {
                            foreach ($variants as $variant => $available) {
                                if ($remaining <= 0) {
                                    break 2;
                                }
                                if ($available <= 0) {
                                    continue;
                                }
                                $take = min($available, $remaining);
                                $varieties->adjust((int) $order['branch_id'], $productId, (int) $volume, (string) $variant, -$take);
                                $varietyStock[$productId][$volume][$variant] -= $take;
                                $remaining -= $take;
                            }
                        }
                    }
                }
            }

            // 7. Batch insert sale items.
            if (! empty($saleItems)) {
                $this->supabase->insertMany('sale_items', $saleItems);
            }

            // 8. Update stock quantities.
            foreach ($stockUpdates as $su) {
                $this->supabase->update('branch_stock', [
                    'quantity' => $su['newQty'],
                    'updated_at' => $now,
                ], ['id' => $su['id']]);
            }

            // 9. Batch insert stock movements.
            if (! empty($stockMovements)) {
                $this->supabase->insertMany('stock_movements', $stockMovements);
            }

            // 10. Audit log.
            $this->supabase->insert('audit_logs', [
                'user_id' => $supabaseUserId,
                'action' => 'order_served',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return response()->json(['message' => 'Order served and sale recorded!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->fail(['error' => 'Failed to serve order: '.$e->getMessage()]);
        }
    }

    /** Save, edit or clear this cashier's personal name for the order. */
    public function personalName(Request $request, int $orderId)
    {
        $this->assertNotMonitoring($request);
        $request->validate([
            'personal_order_name' => 'nullable|string|max:'.OrderWorkflowService::LABEL_MAX,
        ]);

        $result = $this->workflow()->savePersonalName(
            $orderId,
            $this->activeBranchId($request),
            $this->performingUserId($request),
            $request->input('personal_order_name')
        );

        if (! $result['ok']) {
            $this->fail(['error' => $result['message']]);
        }

        return response()->json(['message' => $result['message']]);
    }
}
