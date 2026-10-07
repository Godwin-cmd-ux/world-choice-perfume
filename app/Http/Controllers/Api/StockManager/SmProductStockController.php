<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use App\Services\ProductVarietyStockService;
use Illuminate\Http\Request;

/**
 * JSON twin of the website's Product Stock screens (always available —
 * product stock is the one module every branch category shares).
 */
class SmProductStockController extends SmBaseController
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request)
    {
        $branchId = $this->activeBranchId($request);

        $params = $this->branchParams($request, [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'order' => 'created_at.desc',
        ]);

        $stocks = $this->supabase->query('branch_stock', $params);

        $stocks = collect($stocks)->map(function ($s) {
            if (isset($s['product']) && is_array($s['product'])) {
                if (isset($s['product']['images']) && is_array($s['product']['images'])) {
                    $s['product']['images'] = collect($s['product']['images']);
                }
                $s['product'] = (object) $s['product'];
            }

            return (object) $s;
        })->all();

        // Variety rows replace their parent aggregate row, exactly like the
        // website listing, so nothing is counted twice.
        $varietyService = new ProductVarietyStockService($this->supabase);
        $bucketsByProduct = [];
        foreach ($varietyService->listForProducts($branchId, array_map(fn ($s) => (int) ($s->product_id ?? 0), $stocks)) as $bucket) {
            $bucketsByProduct[$bucket['product_id']][] = $bucket;
        }

        $rows = [];
        foreach ($stocks as $stock) {
            $product = $stock->product ?? null;
            if (! $product) {
                continue;
            }
            $name = (string) ($product->name ?? '');
            $brand = (string) ($product->brand ?? '');
            $category = $stock->category ?? ($product->category ?? '');
            $buckets = $bucketsByProduct[(int) ($stock->product_id ?? 0)] ?? [];

            if (! empty($buckets)) {
                foreach ($buckets as $bucket) {
                    $rows[] = [
                        'kind' => 'variety',
                        'label' => $name.' - '.$bucket['label'],
                        'search' => $name.' '.$brand.' '.$category.' '.$bucket['label'],
                        'product' => $product,
                        'stock_id' => $stock->id ?? null,
                        'quantity' => $bucket['quantity'],
                        'selling_price' => $bucket['selling_price'] > 0 ? $bucket['selling_price'] : (float) ($stock->selling_price ?? 0),
                        'category' => $category,
                        'date_received' => $stock->date_received ?? null,
                        'variety' => $bucket,
                    ];
                }

                continue;
            }

            $rows[] = [
                'kind' => 'product',
                'label' => $name,
                'search' => $name.' '.$brand.' '.$category,
                'product' => $product,
                'stock_id' => $stock->id ?? null,
                'quantity' => (int) ($stock->quantity ?? 0),
                'selling_price' => (float) ($stock->selling_price ?? 0),
                'category' => $category,
                'date_received' => $stock->date_received ?? null,
                'variety' => null,
            ];
        }

        if ($request->query('search')) {
            $search = strtolower(trim((string) $request->query('search')));
            $rows = array_values(array_filter($rows, fn ($r) => str_contains(strtolower($r['search']), $search)));
        }

        $totalValue = array_sum(array_map(fn ($r) => $r['quantity'] * $r['selling_price'], $rows));

        return response()->json([
            'rows' => $rows,
            'totalValue' => $totalValue,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** The restocking list behind the dashboard's low-stock line. */
    public function lowStock(Request $request)
    {
        $stocks = $this->supabase->query('branch_stock', $this->branchParams($request, [
            'select' => 'id,product_id,quantity,selling_price,category,date_received,product:products(id,name,brand,category)',
            'quantity' => 'lte.'.self::LOW_STOCK_THRESHOLD,
            'order' => 'quantity.asc',
        ]));

        $rows = collect($stocks)->map(function ($stock) {
            $product = is_array($stock['product'] ?? null) ? (object) $stock['product'] : null;

            return [
                'product' => $product,
                'product_id' => $stock['product_id'] ?? null,
                'name' => $product->name ?? 'Unknown product',
                'brand' => $product->brand ?? null,
                'category' => $stock['category'] ?? ($product->category ?? null),
                'quantity' => (int) ($stock['quantity'] ?? 0),
                'selling_price' => (float) ($stock['selling_price'] ?? 0),
            ];
        })->filter(fn ($row) => $row['product_id'] !== null)->values()->all();

        return response()->json([
            'rows' => $rows,
            'threshold' => self::LOW_STOCK_THRESHOLD,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** Stock-in form data: active products + this branch's bottle variety stock. */
    public function entry(Request $request)
    {
        $products = $this->supabase->query('products', [
            'is_active' => 'eq.true',
            'select' => 'id,name,brand,category',
            'order' => 'name.asc',
        ]);

        $preselect = null;
        $productId = (string) ($request->query('product_id') ?? '');
        if ($productId !== '') {
            foreach ($products as $product) {
                if ((string) ($product['id'] ?? '') === $productId) {
                    $preselect = ['id' => $product['id'], 'category' => $product['category'] ?? null];

                    break;
                }
            }
        }

        $bottleBranchId = $this->ownBranchId($request);

        return response()->json([
            'products' => $products,
            'preselectProductId' => $preselect['id'] ?? null,
            'preselectCategory' => $preselect['category'] ?? null,
            'bottleVariants' => $this->bottles->variantStock($bottleBranchId),
            'bottleVariantsBranchName' => $this->scope->branchName($bottleBranchId),
            'is_products_only' => $this->isProductsOnly($request),
        ]);
    }

    public function storeEntry(Request $request)
    {
        $this->assertWritable($request);

        $validated = $request->validate([
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
            'selling_price' => 'required|numeric|min:0',
            'variety_price' => 'nullable|numeric|min:0',
            'category' => 'required|in:Oil Fragrance,Brand Perfume',
            'bottle_volume' => 'nullable|integer|in:6,12,30,50,100',
            'bottle_variant' => 'nullable|string|max:32',
            'date_received' => 'required|date',
        ]);

        $branchId = $this->ownBranchId($request);
        $category = $validated['category'];
        $bottleVolume = $validated['bottle_volume'] ?? null;

        // Oil fragrance entries consume empty bottles from this branch's own
        // bottle stock and must state the exact variety for 30/50/100ml.
        if ($category === 'Oil Fragrance') {
            if (empty($bottleVolume)) {
                $this->fail(['bottle_volume' => 'Bottle volume is required for Oil Fragrance entries.']);
            }

            $bottleVolume = (int) $bottleVolume;

            $variant = null;
            if ($this->bottles->volumeHasDetails($bottleVolume)) {
                $variant = $validated['bottle_variant'] ?? '';
                if ($variant === '') {
                    $this->fail(['bottle_variant' => 'Please choose the bottle variety (box / logo / color) for this volume.']);
                }
            } else {
                $variant = 'plain';
            }

            $variantStock = $this->bottles->variantStock($branchId);
            $available = $variantStock[$bottleVolume][$variant] ?? 0;

            if ($available < $validated['quantity']) {
                $this->fail([
                    'bottle_variant' => "Insufficient bottle stock for {$bottleVolume}ml ({$this->bottles->variantLabel($variant, $bottleVolume)}). Available: {$available}.",
                ]);
            }
        }

        $varietyPrice = (float) ($validated['variety_price'] ?? 0);
        $effectivePrice = (float) $validated['selling_price'];
        if ($category === 'Oil Fragrance' && $bottleVolume && $varietyPrice > 0) {
            $effectivePrice = $varietyPrice;
        }

        $existing = $this->supabase->findOne('branch_stock', [
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
        ]);

        if ($existing) {
            $newQty = ($existing['quantity'] ?? 0) + $validated['quantity'];
            $this->supabase->update('branch_stock', [
                'quantity' => $newQty,
                'selling_price' => $effectivePrice,
                'category' => $category,
                'date_received' => $validated['date_received'],
                'entered_by' => $this->performingUserId($request),
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);

            if ((float) ($existing['selling_price'] ?? 0) !== (float) $effectivePrice) {
                (new AuditService)->recordCriticalAction(
                    'price_customization',
                    'price_change_stock_in',
                    'Price Changed',
                    "Selling price changed for product #{$validated['product_id']} during stock-in: ".number_format((float) ($existing['selling_price'] ?? 0)).' → '.number_format((float) $effectivePrice).' TZS.',
                    ['branch_stock_id' => $existing['id'], 'product_id' => $validated['product_id'], 'old_price' => $existing['selling_price'] ?? 0, 'new_price' => $effectivePrice],
                    'branch_stock',
                    (string) $existing['id'],
                    ['selling_price' => $existing['selling_price'] ?? 0],
                    ['selling_price' => $effectivePrice]
                );
            }
        } else {
            $this->supabase->insert('branch_stock', [
                'branch_id' => $branchId,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'selling_price' => $effectivePrice,
                'category' => $category,
                'date_received' => $validated['date_received'],
                'entered_by' => $this->performingUserId($request),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        $this->supabase->insert('stock_movements', [
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
            'type' => 'entry',
            'quantity' => $validated['quantity'],
            'unit_price' => $effectivePrice,
            'performed_by' => $this->performingUserId($request),
            'notes' => 'Stock entry',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Auto-outstock the empty bottles used for this bottling and record
        // the per-product variety breakdown.
        if ($category === 'Oil Fragrance' && $bottleVolume) {
            $this->bottles->deduct(
                $branchId,
                $bottleVolume,
                (int) $validated['quantity'],
                'Auto outstock for oil fragrance stock entry',
                (string) $this->performingUserId($request),
                $variant
            );

            $this->addProductVarietyStock(
                $branchId,
                (int) $validated['product_id'],
                (int) $bottleVolume,
                (string) $variant,
                (int) $validated['quantity'],
                $effectivePrice
            );
        }

        return response()->json(['message' => 'Stock entry recorded successfully.']);
    }

    /** Increment (or create) the per-product variety row for this branch. */
    private function addProductVarietyStock(int $branchId, int $productId, int $volume, string $variant, int $quantity, ?float $unitPrice = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        $existing = $this->supabase->findOne('branch_stock_varieties', [
            'branch_id' => $branchId,
            'product_id' => $productId,
            'volume' => $volume,
            'variant' => $variant,
        ]);

        $price = ($unitPrice !== null && $unitPrice > 0) ? round($unitPrice, 2) : null;

        if ($existing) {
            $update = [
                'quantity' => ((int) ($existing['quantity'] ?? 0)) + $quantity,
                'updated_at' => now()->toIso8601String(),
            ];
            if ($price !== null) {
                $update['selling_price'] = $price;
            }
            $this->supabase->update('branch_stock_varieties', $update, ['id' => $existing['id']]);
        } else {
            $row = [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'volume' => $volume,
                'variant' => $variant,
                'quantity' => $quantity,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];
            if ($price !== null) {
                $row['selling_price'] = $price;
            }
            $this->supabase->insert('branch_stock_varieties', $row);
        }
    }

    public function update(Request $request, int $stockId)
    {
        $this->assertWritable($request);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $branchId = $this->ownBranchId($request);

        $stock = $this->supabase->findOne('branch_stock', [
            'id' => $stockId,
            'branch_id' => $branchId,
        ]);

        if (! $stock) {
            $this->fail(['error' => 'Stock record not found.']);
        }

        $oldQty = $stock['quantity'] ?? 0;
        $newQty = $validated['quantity'];
        $oldPrice = $stock['selling_price'] ?? 0;
        $newPrice = $validated['selling_price'];

        $this->supabase->update('branch_stock', [
            'quantity' => $newQty,
            'selling_price' => $newPrice,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $stockId]);

        // Keep the variety breakdown in sync with a manual quantity change
        // (largest buckets take the difference — no variety was stated).
        if ($newQty != $oldQty) {
            $product = $this->supabase->findOne('products', ['id' => $stock['product_id']]);
            if (($product['category'] ?? '') === 'Oil Fragrance') {
                $varietyService = new ProductVarietyStockService($this->supabase);
                $buckets = $varietyService->stockFor($branchId, (int) $stock['product_id']);
                $hasRecords = false;
                foreach ($buckets as $variants) {
                    if (array_sum($variants) > 0) {
                        $hasRecords = true;

                        break;
                    }
                }
                if ($hasRecords) {
                    $delta = $newQty - $oldQty;
                    $flat = [];
                    foreach ($buckets as $volume => $variants) {
                        foreach ($variants as $variant => $qty) {
                            if ($qty > 0) {
                                $flat[] = ['volume' => $volume, 'variant' => $variant, 'qty' => $qty];
                            }
                        }
                    }
                    usort($flat, fn ($a, $b) => $b['qty'] <=> $a['qty']);
                    if ($delta > 0) {
                        if (! empty($flat)) {
                            $varietyService->adjust($branchId, (int) $stock['product_id'], (int) $flat[0]['volume'], (string) $flat[0]['variant'], $delta);
                        }
                    } else {
                        $remaining = -$delta;
                        foreach ($flat as $bucket) {
                            if ($remaining <= 0) {
                                break;
                            }
                            $take = min((int) $bucket['qty'], $remaining);
                            $varietyService->adjust($branchId, (int) $stock['product_id'], (int) $bucket['volume'], (string) $bucket['variant'], -$take);
                            $remaining -= $take;
                        }
                    }
                }
            }
        }

        if ($newQty != $oldQty) {
            $this->supabase->insert('stock_movements', [
                'branch_id' => $branchId,
                'product_id' => $stock['product_id'],
                'type' => $newQty > $oldQty ? 'entry' : 'sale',
                'quantity' => $newQty - $oldQty,
                'unit_price' => $oldPrice,
                'performed_by' => $this->performingUserId($request),
                'notes' => 'Manual stock adjustment',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        $audit = new AuditService();

        if ((float) $oldPrice !== (float) $newPrice) {
            $audit->recordCriticalAction(
                'price_customization',
                'price_change_manual',
                'Price Changed',
                "Manual selling price change for product #{$stock['product_id']}: ".number_format((float) $oldPrice).' → '.number_format((float) $newPrice).' TZS.',
                ['branch_stock_id' => $stockId, 'product_id' => $stock['product_id'], 'old_price' => $oldPrice, 'new_price' => $newPrice],
                'branch_stock',
                (string) $stockId,
                ['selling_price' => $oldPrice, 'quantity' => $oldQty],
                ['selling_price' => $newPrice, 'quantity' => $newQty]
            );
        }

        if ($newQty != $oldQty) {
            $audit->recordCriticalAction(
                'stock_adjusted',
                'stock_adjusted_manual',
                'Stock Adjusted',
                "Manual stock adjustment for product #{$stock['product_id']}: {$oldQty} → {$newQty} units.",
                ['branch_stock_id' => $stockId, 'product_id' => $stock['product_id'], 'old_qty' => $oldQty, 'new_qty' => $newQty],
                'branch_stock',
                (string) $stockId,
                ['quantity' => $oldQty],
                ['quantity' => $newQty]
            );
        }

        return response()->json(['message' => 'Stock updated successfully.']);
    }

    public function destroy(Request $request, int $stockId)
    {
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $stock = $this->supabase->findOne('branch_stock', [
            'id' => $stockId,
            'branch_id' => $branchId,
        ]);

        if (! $stock) {
            $this->fail(['error' => 'Stock record not found.']);
        }

        $this->supabase->delete('branch_stock', ['id' => $stockId]);

        $this->supabase->delete('branch_stock_varieties', [
            'branch_id' => $branchId,
            'product_id' => $stock['product_id'],
        ]);

        (new AuditService)->recordCriticalAction(
            'stock_deleted',
            'stock_record_deleted',
            'Stock Record Deleted',
            "Stock record deleted for product #{$stock['product_id']} ({$stock['quantity']} units @ ".number_format((float) ($stock['selling_price'] ?? 0)).' TZS).',
            ['branch_stock_id' => $stockId, 'product_id' => $stock['product_id'], 'quantity' => $stock['quantity'], 'selling_price' => $stock['selling_price'] ?? 0],
            'branch_stock',
            (string) $stockId,
            ['quantity' => $stock['quantity'], 'selling_price' => $stock['selling_price'] ?? 0],
            []
        );

        return response()->json(['message' => 'Stock record deleted.']);
    }

    public function updateVariety(Request $request, int $varietyId)
    {
        $this->assertWritable($request);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $branchId = $this->ownBranchId($request);
        $varietyService = new ProductVarietyStockService($this->supabase);

        $bucket = $varietyService->findRow($branchId, $varietyId);

        if (! $bucket) {
            $this->fail(['error' => 'Variety stock record not found.']);
        }

        $oldQty = (int) ($bucket['quantity'] ?? 0);
        $newQty = (int) $validated['quantity'];
        $oldPrice = (float) ($bucket['selling_price'] ?? 0);
        $newPrice = (float) $validated['selling_price'];
        $productId = (int) $bucket['product_id'];
        $label = $varietyService->pickLabel((int) $bucket['volume'], (string) $bucket['variant']);

        $varietyService->updateRow((int) $bucket['id'], $newQty, $newPrice);
        $varietyService->syncAggregateQuantity($branchId, $productId, $newQty - $oldQty);

        if ($newQty !== $oldQty) {
            $this->supabase->insert('stock_movements', [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => 'adjustment',
                'quantity' => $newQty - $oldQty,
                'unit_price' => $oldPrice,
                'performed_by' => $this->performingUserId($request),
                'notes' => "Manual variety adjustment — {$label}",
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        $audit = new AuditService();

        if ($oldPrice !== $newPrice) {
            $audit->recordCriticalAction(
                'price_customization',
                'variety_price_change_manual',
                'Price Changed',
                "Manual selling price change for product #{$productId} ({$label}): ".number_format($oldPrice, 2).' → '.number_format($newPrice, 2).' TZS.',
                ['branch_stock_varieties_id' => $bucket['id'], 'product_id' => $productId, 'label' => $label, 'old_price' => $oldPrice, 'new_price' => $newPrice],
                'branch_stock_varieties',
                (string) $bucket['id'],
                ['selling_price' => $oldPrice],
                ['selling_price' => $newPrice]
            );
        }

        if ($newQty !== $oldQty) {
            $audit->recordCriticalAction(
                'stock_adjusted',
                'variety_stock_adjusted_manual',
                'Stock Adjusted',
                "Manual stock adjustment for product #{$productId} ({$label}): {$oldQty} → {$newQty} units.",
                ['branch_stock_varieties_id' => $bucket['id'], 'product_id' => $productId, 'label' => $label, 'old_qty' => $oldQty, 'new_qty' => $newQty],
                'branch_stock_varieties',
                (string) $bucket['id'],
                ['quantity' => $oldQty],
                ['quantity' => $newQty]
            );
        }

        return response()->json(['message' => "{$label} updated successfully."]);
    }

    public function destroyVariety(Request $request, int $varietyId)
    {
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);
        $varietyService = new ProductVarietyStockService($this->supabase);

        $bucket = $varietyService->findRow($branchId, $varietyId);

        if (! $bucket) {
            $this->fail(['error' => 'Variety stock record not found.']);
        }

        $productId = (int) $bucket['product_id'];
        $removedQty = (int) ($bucket['quantity'] ?? 0);
        $label = $varietyService->pickLabel((int) $bucket['volume'], (string) $bucket['variant']);

        $varietyService->deleteRow((int) $bucket['id']);
        $varietyService->syncAggregateQuantity($branchId, $productId, -$removedQty);

        (new AuditService)->recordCriticalAction(
            'stock_deleted',
            'variety_stock_record_deleted',
            'Variety Stock Deleted',
            "Variety stock deleted for product #{$productId} ({$label}, {$removedQty} units).",
            ['branch_stock_varieties_id' => $bucket['id'], 'product_id' => $productId, 'label' => $label, 'quantity' => $removedQty],
            'branch_stock_varieties',
            (string) $bucket['id'],
            ['quantity' => $removedQty, 'selling_price' => $bucket['selling_price'] ?? 0],
            []
        );

        return response()->json(['message' => "{$label} stock deleted."]);
    }

    public function movements(Request $request)
    {
        $params = $this->branchParams($request, [
            'select' => '*, product:products(id,name,brand)',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        if ($request->query('product_id')) {
            $params['product_id'] = 'eq.'.$request->query('product_id');
        }

        if ($request->query('type')) {
            $params['type'] = 'eq.'.$request->query('type');
        }

        $movements = $this->supabase->query('stock_movements', $params);

        $userIds = [];
        foreach ($movements as $m) {
            if (! empty($m['performed_by'])) {
                $userIds[$m['performed_by']] = true;
            }
        }
        $names = $this->resolvePerformedByNames(array_keys($userIds));

        $movements = collect($movements)->map(function ($m) use ($names) {
            if (isset($m['product']) && is_array($m['product'])) {
                $m['product'] = (object) $m['product'];
            }
            $m['performedBy'] = ! empty($m['performed_by']) && isset($names[$m['performed_by']])
                ? ['id' => (int) $m['performed_by'], 'name' => $names[$m['performed_by']]]
                : null;

            return $m;
        })->values()->all();

        $products = $this->supabase->query('products', [
            'is_active' => 'eq.true',
            'select' => 'id,name,brand',
            'order' => 'name.asc',
        ]);

        return response()->json([
            'movements' => $movements,
            'products' => $products,
            'scope' => $this->scopePayload($request),
        ]);
    }
}
