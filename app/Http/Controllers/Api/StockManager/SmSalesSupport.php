<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use App\Services\BottleStockService;

/**
 * Port of SalesController::store — the full checkout. Everything the website
 * validates (stock, picked bottlings, empty-bottle variants, split payments,
 * retail discounts) is validated here identically; only the error channel
 * changed (422 JSON instead of a redirect with errors).
 */
trait SmSalesSupport
{
    /** Audit action for the created sale — the Stock Manager default. */
    protected function saleAuditAction(): string
    {
        return 'sale_created_by_stock_manager';
    }

    /** Role name used in the audit prose. */
    protected function saleActorLabel(): string
    {
        return 'Stock Manager';
    }

    /** Whether a custom price raises the Price Customized audit (CC/BA yes, SM no). */
    protected function auditsCustomPrice(): bool
    {
        return false;
    }

    /** Extra suffix for the stock-movement note (Branch Admin signs its notes). */
    protected function saleNoteSuffix(): string
    {
        return '';
    }

    private function commitSale($request, array $validated): \Illuminate\Http\JsonResponse
    {
        // Never sell empty bottles on retail sales.
        $validated['items'] = array_values(array_filter($validated['items'] ?? [], fn ($i) => ! empty($i['product_id'] ?? null)));
        if (($validated['sale_type'] ?? 'retail') === 'retail') {
            $validated['empty_bottles'] = [];
        }

        $branchId = $this->ownBranchId($request);
        $supabaseUserId = $this->performingUserId($request);
        $varieties = $this->varieties();

        // 1. Determine customer.
        $customerId = null;
        if (! empty($validated['customer_id'])) {
            $customerId = $validated['customer_id'];
        } elseif (! empty($validated['customer_name']) || ! empty($validated['customer_phone'])) {
            $typedName = trim((string) ($validated['customer_name'] ?? ''));
            $typedPhone = trim((string) ($validated['customer_phone'] ?? ''));
            $existingCustomer = null;
            if ($typedPhone !== '') {
                $existingCustomer = $this->supabase->findOne('customers', [
                    'phone' => $typedPhone,
                ]);
            }
            $nameAgrees = $typedName === ''
                || (isset($existingCustomer['name']) && mb_strtolower(trim((string) $existingCustomer['name'])) === mb_strtolower($typedName));
            if ($existingCustomer && $nameAgrees) {
                $customerId = $existingCustomer['id'];
            } else {
                $customer = $this->supabase->insert('customers', [
                    'name' => $typedName !== '' ? $typedName : ($existingCustomer['name'] ?? null),
                    'phone' => $typedPhone !== '' ? $typedPhone : ($existingCustomer['phone'] ?? null),
                    'whatsapp' => $typedPhone !== '' ? $typedPhone : ($existingCustomer['whatsapp'] ?? null),
                ]);
                $customerId = $customer['id'] ?? null;
            }
        }

        $saleNumber = 'SALE-'.date('YmdHis').'-'.strtoupper(substr(uniqid(), -4));

        // 2. ALL stock for this branch in ONE query.
        $allStock = collect($this->supabase->query('branch_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'id,product_id,quantity,selling_price,buying_cost,product:products(id,name)',
        ]));

        $stockMap = [];
        foreach ($allStock as $s) {
            $stockMap[$s['product_id']] = $s;
        }

        // 3. Validate stock and calculate totals.
        $subtotal = 0;
        $saleItems = [];
        $stockUpdates = [];
        $stockMovements = [];
        $discountDetails = [];
        $priceOverridden = false;

        $varietyStock = $varieties->stockForProducts(
            $branchId,
            array_map(fn ($i) => (int) ($i['product_id'] ?? 0), $validated['items']),
            fresh: true
        );

        foreach ($validated['items'] as $item) {
            $stock = $stockMap[$item['product_id']] ?? null;

            if (! $stock || ($stock['quantity'] ?? 0) < $item['quantity']) {
                $this->fail(["items.{$item['product_id']}" => 'Insufficient stock. Available: '.($stock['quantity'] ?? 0)]);
            }

            $isOil = ($stock['product']['category'] ?? '') === 'Oil Fragrance';
            $pickVolume = 0;
            $pickVariant = '';
            if ($isOil && ! empty($varietyStock[(int) $item['product_id']])) {
                $pickVolume = (int) ($item['volume'] ?? 0);
                $pickVariant = trim((string) ($item['variant'] ?? ''));
                if ($pickVolume <= 0 || $pickVariant === '') {
                    $this->fail(["items.{$item['product_id']}" => 'Select the bottle volume and variety for '.($stock['product']['name'] ?? 'product').'.']);
                }
                $bucket = (int) ($varietyStock[(int) $item['product_id']][$pickVolume][$pickVariant] ?? 0);
                if ($bucket < (int) $item['quantity']) {
                    $this->fail(["items.{$item['product_id']}" => 'Insufficient stock for '.($stock['product']['name'] ?? 'product').' — '
                        .$varieties->pickLabel($pickVolume, $pickVariant).". Available: {$bucket}."]);
                }
            }

            // A custom price is honoured on both sale types — the website's
            // Customer Care store prices this way for retail and wholesale.
            // Retail discounts go through discount_price below (Stock Manager).
            // A picked variety carries its own price, with the branch price as fallback.
            $varietyUnitPrice = $pickVolume > 0 && $pickVariant !== ''
                ? $varieties->priceFor($branchId, (int) $item['product_id'], $pickVolume, $pickVariant)
                : 0.0;
            if (! empty($item['custom_price'])) {
                $unitPrice = $item['custom_price'];
                $priceOverridden = true;
            } elseif ($validated['sale_type'] === 'retail' && ! empty($item['discount_price'])) {
                $unitPrice = $item['discount_price'];
            } else {
                $unitPrice = $varietyUnitPrice > 0 ? $varietyUnitPrice : ($stock['selling_price'] ?? 0);
            }

            $lineTotal = $unitPrice * $item['quantity'];
            $subtotal += $lineTotal;
            $newQty = ($stock['quantity'] ?? 0) - $item['quantity'];

            if ($validated['sale_type'] === 'retail' && ! empty($item['discount_price'])) {
                $discountDetails[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $stock['product']['name'] ?? ('Product #'.$item['product_id']),
                    'quantity' => (int) $item['quantity'],
                    'original_price' => (float) ($stock['selling_price'] ?? 0),
                    'discount_price' => (float) $item['discount_price'],
                ];
            }

            $saleItems[] = [
                'sale_id' => null,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'unit_cost' => (float) ($stock['buying_cost'] ?? 0),
                'total' => $lineTotal,
                'volume' => $pickVolume > 0 ? $pickVolume : null,
                'variant' => $pickVolume > 0 ? $pickVariant : null,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            $stockUpdates[] = [
                'id' => $stock['id'],
                'newQty' => $newQty,
                'soldQty' => (int) $item['quantity'],
                'productId' => (int) $item['product_id'],
                'varietyVolume' => $pickVolume,
                'varietyVariant' => $pickVariant,
            ];

            $stockMovements[] = [
                'branch_id' => $branchId,
                'product_id' => $item['product_id'],
                'type' => 'sale',
                'quantity' => -$item['quantity'],
                'unit_price' => $stock['selling_price'],
                'reference_type' => 'sale',
                'reference_id' => null,
                'performed_by' => $supabaseUserId,                    'notes' => "Sale {$saleNumber}".$this->saleNoteSuffix().($validated['sale_type'] === 'retail' && ! empty($item['discount_price']) ? ' (Discounted)' : ''),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];
        }

        // 4. Empty bottle lines.
        $rawBottles = $validated['empty_bottles'] ?? [];
        $bottleItems = array_values(array_filter($rawBottles, fn ($b) => ! empty($b['volume'] ?? null)));

        foreach ($bottleItems as $btl) {
            if (empty($btl['quantity']) || ! isset($btl['price']) || $btl['price'] === '' || $btl['price'] === null) {
                $this->fail(['empty_bottles' => 'Each empty bottle line requires a quantity and price.']);
            }
        }

        $bottleTotal = 0;
        $bottleDeductions = [];

        if (! empty($bottleItems)) {
            $bottleStockRows = $this->supabase->queryFresh('bottle_stock', [
                'branch_id' => "eq.{$branchId}",
            ]);
            $bottleAvailable = [];
            foreach ($bottleStockRows as $bs) {
                $bv = $this->bottles->parseVolume((string) ($bs['volume'] ?? ''));
                if ($bv !== null) {
                    $vk = (string) ($bs['variant'] ?? BottleStockService::VARIANT_PLAIN);
                    $bottleAvailable[$bv][$vk] = (int) ($bs['quantity'] ?? 0);
                }
            }

            foreach ($bottleItems as $btl) {
                $volume = (int) $btl['volume'];
                $bQty = (int) $btl['quantity'];
                $bPrice = (float) $btl['price'];

                $variant = BottleStockService::VARIANT_PLAIN;
                if ($this->bottles->volumeHasDetails($volume)) {
                    $variant = trim((string) ($btl['variant'] ?? ''));
                    if (! in_array($variant, $this->bottles->variantBuckets($volume), true)) {
                        $this->fail(['empty_bottles' => "Select the box/logo/color details (variant) for {$volume}ml empty bottles."]);
                    }
                }

                $available = (int) ($bottleAvailable[$volume][$variant] ?? 0);

                if ($available < $bQty) {
                    $variantText = $this->bottles->volumeHasDetails($volume)
                        ? ' ('.$this->bottles->variantLabel($variant, $volume).')'
                        : '';
                    $this->fail(['empty_bottles' => "Insufficient bottle stock for {$volume}ml{$variantText}. Available: {$available}."]);
                }

                $bottleProduct = $this->bottles->findOrCreateEmptyBottleProduct($volume);

                if (! $bottleProduct || empty($bottleProduct['id'])) {
                    $this->fail(['empty_bottles' => 'Could not register the empty bottle product for the receipt.']);
                }

                $lineTotal = $bPrice * $bQty;
                $bottleTotal += $lineTotal;

                $saleItems[] = [
                    'sale_id' => null,
                    'product_id' => $bottleProduct['id'],
                    'quantity' => $bQty,
                    'unit_price' => $bPrice,
                    'unit_cost' => 0,
                    'total' => $lineTotal,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ];

                $bottleDeductions[] = [
                    'volume' => $volume,
                    'quantity' => $bQty,
                    'reason' => "Sold as empty bottle - Sale {$saleNumber}",
                    'variant' => $variant,
                ];

                $bottleAvailable[$volume][$variant] -= $bQty;
            }

            $subtotal += $bottleTotal;
        }

        if (empty($saleItems)) {
            $this->fail(['items' => 'Add at least one product or empty bottle to the sale.']);
        }

        // 5. Payment summary.
        $payments = $validated['payments'] ?? [];
        if ($validated['payment_mode'] === 'multi') {
            $paymentTotal = collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0));
            if (abs($paymentTotal - $subtotal) > 0.01) {
                $this->fail(['payments' => 'Payment breakdown ('.number_format($paymentTotal).') must equal the sale total ('.number_format($subtotal).').']);
            }
        }
        $paymentParts = [];
        foreach ($payments as $p) {
            $methodLabel = str_replace('_', ' ', ucfirst($p['method']));
            if ($validated['payment_mode'] === 'multi') {
                $paymentParts[] = $methodLabel.' '.number_format($p['amount'] ?? 0);
            } else {
                $paymentParts[] = $methodLabel.' '.number_format($subtotal);
            }
        }
        $paymentSummary = implode(', ', $paymentParts);
        $primaryMethod = $payments[0]['method'] ?? 'cash';

        // 6. Create the sale.
        $saleData = [
            'sale_number' => $saleNumber,
            'branch_id' => $branchId,
            'cashier_id' => $supabaseUserId,
            'customer_id' => $customerId,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'payment_method' => $primaryMethod,
            'payment_summary' => $paymentSummary,
            'payment_status' => 'paid',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
        if ($this->supabase->tableHasColumn('sales', 'sale_type')) {
            $saleData['sale_type'] = $validated['sale_type'] ?? 'retail';
        }
        $sale = $this->supabase->insert('sales', $saleData);

        if (! $sale) {
            $this->fail(['error' => 'Failed to create sale.']);
        }

        // 7. Batch insert sale items.
        foreach ($saleItems as &$si) {
            $si['sale_id'] = $sale['id'];
        }
        unset($si);
        $itemsResult = $this->supabase->insertMany('sale_items', $saleItems);
        if ($itemsResult === null) {
            $this->supabase->delete('sales', ['id' => $sale['id']]);
            $this->fail(['error' => 'Sale items could not be saved. Please try again.']);
        }

        // 8. Update stock quantities and deduct the exact variety buckets sold.
        foreach ($stockUpdates as $su) {
            $this->supabase->update('branch_stock', [
                'quantity' => $su['newQty'],
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $su['id']]);

            if (($su['varietyVolume'] ?? 0) > 0 && ($su['varietyVariant'] ?? '') !== '') {
                $varieties->adjust(
                    $branchId,
                    (int) $su['productId'],
                    (int) $su['varietyVolume'],
                    (string) $su['varietyVariant'],
                    -(int) ($su['soldQty'] ?? 0)
                );
            }
        }

        // 9. Batch insert stock movements (failure is logged, not fatal).
        foreach ($stockMovements as &$sm) {
            $sm['reference_id'] = $sale['id'];
        }
        unset($sm);
        $movementsResult = $this->supabase->insertMany('stock_movements', $stockMovements);
        if ($movementsResult === null) {
            \Illuminate\Support\Facades\Log::warning("Stock movements not saved for sale {$saleNumber} (sale id {$sale['id']}).");
        }

        // 10. Auto-outstock empty bottles.
        foreach ($bottleDeductions as $bd) {
            $this->bottles->deduct($branchId, $bd['volume'], $bd['quantity'], $bd['reason'], (string) $supabaseUserId, $bd['variant'] ?? '');
        }

        // 11. Audit log.
        $this->supabase->insert('audit_logs', [
            'user_id' => $supabaseUserId,
            'action' => $this->saleAuditAction(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // 12. Discount notification for the super admin.
        if (! empty($discountDetails)) {
            $originalTotal = array_sum(array_map(fn ($d) => $d['original_price'] * $d['quantity'], $discountDetails));
            $discountTotal = array_sum(array_map(fn ($d) => $d['discount_price'] * $d['quantity'], $discountDetails));

            $lines = array_map(function ($d) {
                $name = $d['product_name'] ?? ('Product #'.$d['product_id']);
                $qty = $d['quantity'] > 1 ? " x{$d['quantity']}" : '';

                return "{$name}{$qty}: ".number_format($d['original_price']).' -> '.number_format($d['discount_price']);
            }, $discountDetails);

            (new AuditService)->recordCriticalAction(
                'discount_used',
                'discount_applied_sale',
                'Discount Applied',
                "Discount applied in sale {$saleNumber} ({$this->saleActorLabel()}). ".implode('; ', $lines).'. Total: '.number_format($originalTotal).' -> '.number_format($discountTotal).'.',
                [
                    'sale_id' => $sale['id'],
                    'sale_number' => $saleNumber,
                    'items' => $discountDetails,
                    'original_total' => $originalTotal,
                    'discounted_total' => $discountTotal,
                ],
                'sale',
                (string) $sale['id'],
                ['subtotal' => $subtotal, 'original_total' => $originalTotal, 'discounted_total' => $discountTotal],
                ['discount_applied' => true]
            );
        }

        // Custom prices raise a Price Customized audit where the website does
        // so (Customer Care) — the Stock Manager flow audits discounts above.
        if ($this->auditsCustomPrice() && $priceOverridden) {
            (new AuditService)->recordCriticalAction(
                'price_customization',
                'custom_price_sale',
                'Price Customized',
                "A custom sale price was used in sale {$saleNumber} ({$this->saleActorLabel()}).",
                ['sale_id' => $sale['id'], 'sale_number' => $saleNumber],
                'sale',
                (string) $sale['id'],
                [],
                ['custom_price_applied' => true]
            );
        }

        return response()->json([
            'message' => "Sale {$saleNumber} completed successfully!",
            'sale' => $sale,
        ]);
    }
}
