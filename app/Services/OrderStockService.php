<?php

namespace App\Services;

/**
 * Deducts a served order's items from branch stock.
 *
 * An order is a promise of stock; the branch only actually hands that stock
 * over when the order is served. The cashier handover already records a sale
 * and deducts what was sold, but an order can equally be completed from the
 * Branch Admin, Seller, Stock Manager or Customer Care Orders pages — and
 * those only move the status. This service is the one place that turns
 * "served" into a stock movement, whatever role performed the handover.
 *
 * Two stock layers are kept in step, exactly as the cashier sale does:
 *
 *  - branch_stock.quantity is the product's total at the branch.
 *  - branch_stock_varieties holds the per-size, per-packaging buckets an oil
 *    fragrance is bottled from, so a 50ml sale must empty 50ml bottles.
 *
 * Every deduction writes stock_movements rows with reference_type "order" and
 * reference_id = the order id. That reference is also the idempotency key: a
 * retried serve can never take the stock twice.
 *
 * Stock is never allowed to go negative. If an order line asks for more than
 * the branch still has (two orders promising the same last bottle), the
 * available units are deducted and the shortfall is reported back to the
 * caller instead of failing the handover or inventing stock.
 */
class OrderStockService
{
    /** stock_movements.reference_type for movements created from an order. */
    public const REFERENCE_TYPE = 'order';

    private ProductVarietyStockService $varieties;

    public function __construct(private SupabaseService $supabase)
    {
        $this->varieties = new ProductVarietyStockService($supabase);
    }

    /**
     * Whether this order has already moved stock.
     *
     * Read fresh: the order may have been served moments ago in another
     * request, and a request-level cache could still hold the "not deducted"
     * answer from before that happened.
     */
    public function hasDeducted(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        return $this->supabase->queryFresh('stock_movements', [
            'select' => 'id',
            'reference_type' => 'eq.' . self::REFERENCE_TYPE,
            'reference_id' => "eq.{$orderId}",
            'limit' => 1,
        ]) !== [];
    }

    /**
     * Move a served order's items out of the branch's stock.
     *
     * @param  array  $order  Raw orders row (id, branch_id and order_number are used).
     * @param  int|string|null  $performedBy  Staff user id recorded on the movement.
     * @param  int|null  $saleId  Set when the handover also created a sale.
     * @return array{
     *     ok: bool,
     *     already: bool,
     *     deducted: int,
     *     skipped: array<int, array{product_id: int, ordered: int, deducted: int}>,
     *     message: string
     * }
     */
    public function deductForOrder(array $order, $performedBy = null, ?int $saleId = null): array
    {
        $orderId = (int) ($order['id'] ?? 0);
        $branchId = (int) ($order['branch_id'] ?? 0);

        if ($orderId <= 0 || $branchId <= 0) {
            return $this->result(false, false, 0, [], 'The order is missing its branch, so stock could not be deducted.');
        }

        if ($this->hasDeducted($orderId)) {
            return $this->result(true, true, 0, [], 'Stock for this order was already deducted.');
        }

        $items = $this->supabase->queryFresh('order_items', [
            'order_id' => "eq.{$orderId}",
        ]);

        if ($items === []) {
            return $this->result(true, false, 0, [], 'This order has no items to deduct.');
        }

        $productIds = array_values(array_unique(array_filter(array_map(
            fn ($item) => (int) ($item['product_id'] ?? 0),
            $items
        ))));

        // The product's category decides whether it has bottling buckets to
        // empty as well as its aggregate branch_stock row.
        $categoryMap = [];
        if ($productIds !== []) {
            foreach ($this->supabase->queryFresh('products', [
                'select' => 'id,category',
                'id' => 'in.(' . implode(',', $productIds) . ')',
            ]) as $product) {
                $categoryMap[(int) $product['id']] = $product['category'] ?? '';
            }
        }

        $stockMap = [];
        foreach ($this->supabase->queryFresh('branch_stock', [
            'select' => 'id,product_id,quantity,selling_price',
            'branch_id' => "eq.{$branchId}",
        ]) as $stock) {
            $stockMap[(int) $stock['product_id']] = $stock;
        }

        $varietyStock = $this->varieties->stockForProducts($branchId, $productIds, fresh: true);

        $now = now()->toIso8601String();
        $movements = [];
        $skipped = [];
        $deductedUnits = 0;

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $ordered = max((int) ($item['quantity'] ?? 0), 0);

            if ($productId <= 0 || $ordered === 0) {
                continue;
            }

            $stock = $stockMap[$productId] ?? null;
            $available = $stock === null ? 0 : max((int) ($stock['quantity'] ?? 0), 0);

            if ($available <= 0) {
                $skipped[] = ['product_id' => $productId, 'ordered' => $ordered, 'deducted' => 0];
                continue;
            }

            // Never take more than the branch holds. A short line still
            // deducts what is there; the caller is told about the remainder.
            $quantity = min($ordered, $available);

            if ($quantity < $ordered) {
                $skipped[] = ['product_id' => $productId, 'ordered' => $ordered, 'deducted' => $quantity];
            }

            $remaining = $available - $quantity;

            $moved = $this->supabase->update('branch_stock', [
                'quantity' => $remaining,
                'updated_at' => $now,
            ], ['id' => $stock['id']]);

            if ($moved === []) {
                // No movement row is written when the quantity could not be
                // changed, so the order reference stays clean for a retry.
                $skipped[] = ['product_id' => $productId, 'ordered' => $ordered, 'deducted' => 0];
                continue;
            }

            // Keep the in-memory copy current so a second line for the same
            // product deducts from what the first line left, not from the
            // quantity we read before either line ran.
            $stockMap[$productId]['quantity'] = $remaining;

            $notes = "Order {$order['order_number']} served";
            if ($saleId !== null) {
                $notes .= " (sale #{$saleId})";
            }

            $movements[] = [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => 'sale',
                'quantity' => -$quantity,
                'unit_price' => $item['unit_price'] ?? ($stock['selling_price'] ?? 0),
                'reference_type' => self::REFERENCE_TYPE,
                'reference_id' => $orderId,
                'performed_by' => $performedBy,
                'notes' => $notes,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $deductedUnits += $quantity;

            // Empty the bottling the customer chose first, then the other
            // buckets of the same product until the sold units are covered.
            if (($categoryMap[$productId] ?? '') === 'Oil Fragrance' && !empty($varietyStock[$productId])) {
                $this->deductVarieties(
                    $branchId,
                    $productId,
                    $quantity,
                    (int) ($item['volume'] ?? 0),
                    trim((string) ($item['variant'] ?? '')),
                    $varietyStock
                );
            }
        }

        if ($movements !== []) {
            $inserted = $this->supabase->insertMany('stock_movements', $movements);

            if ($inserted === null) {
                // The quantities have already moved, so a retry would take
                // them a second time. The failure is reported loudly instead
                // of being hidden behind a successful serve message.
                return $this->result(
                    false,
                    false,
                    $deductedUnits,
                    $skipped,
                    'Stock quantities were reduced but the stock movements could not be recorded.'
                );
            }
        }

        $message = $deductedUnits > 0
            ? "Deducted {$deductedUnits} unit(s) from branch stock."
            : 'No stock was available to deduct for this order.';

        return $this->result(true, false, $deductedUnits, $skipped, $message);
    }

    /**
     * Take $quantity units out of the product's variety buckets.
     *
     * The exact bottling on the order line is used first. Lines saved before
     * the customer-facing size picker may not name a packaging, so the
     * remaining units are taken from the largest buckets first — the branch
     * packs from whichever bucket it can, and every packaging of one size
     * sells for the same price.
     *
     * @param  array  $varietyStock  [product_id => [volume => [variant => qty]]], updated in place.
     */
    private function deductVarieties(
        int $branchId,
        int $productId,
        int $quantity,
        int $volume,
        string $variant,
        array &$varietyStock
    ): void {
        $remaining = $quantity;

        if ($volume > 0 && $variant !== '') {
            $available = (int) ($varietyStock[$productId][$volume][$variant] ?? 0);
            $take = min($available, $remaining);

            if ($take > 0) {
                $this->varieties->adjust($branchId, $productId, $volume, $variant, -$take);
                $varietyStock[$productId][$volume][$variant] -= $take;
                $remaining -= $take;
            }
        }

        if ($remaining <= 0) {
            return;
        }

        // Largest bucket first, so the same order produces the same
        // deduction every time and no single packaging is needlessly
        // emptied while another still holds stock.
        $buckets = [];
        foreach ($varietyStock[$productId] as $bucketVolume => $variants) {
            foreach ($variants as $bucketVariant => $available) {
                if ((int) $available > 0) {
                    $buckets[] = [
                        'volume' => (int) $bucketVolume,
                        'variant' => (string) $bucketVariant,
                        'available' => (int) $available,
                    ];
                }
            }
        }

        usort($buckets, fn ($a, $b) => $b['available'] <=> $a['available']);

        foreach ($buckets as $bucket) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($bucket['available'], $remaining);
            if ($take <= 0) {
                continue;
            }

            $this->varieties->adjust($branchId, $productId, $bucket['volume'], $bucket['variant'], -$take);
            $varietyStock[$productId][$bucket['volume']][$bucket['variant']] -= $take;
            $remaining -= $take;
        }
    }

    /**
     * The service's uniform answer to a serve/handover caller.
     */
    private function result(bool $ok, bool $already, int $deducted, array $skipped, string $message): array
    {
        return [
            'ok' => $ok,
            'already' => $already,
            'deducted' => $deducted,
            'skipped' => $skipped,
            'message' => $message,
        ];
    }
}
