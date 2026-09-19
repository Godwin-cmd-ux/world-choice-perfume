<?php

namespace App\Services;

/**
 * Per-product bottle variety stock.
 *
 * Oil fragrance products are bottled into small bottles of specific
 * volumes and varieties (box / logo / color). branch_stock holds one
 * total quantity per product, while branch_stock_varieties holds the
 * breakdown per (branch, product, volume, variant) recorded at
 * stock-in — e.g. how many "Test perfume" units exist as 50ml
 * With Box · With Logo · Yellow.
 */
class ProductVarietyStockService
{
    public function __construct(private SupabaseService $supabase)
    {
    }

    /**
     * Read the variety breakdown for one product at a branch.
     * Returns [volume => [variant => quantity]] (only quantities > 0).
     */
    public function stockFor(int $branchId, int $productId, bool $fresh = false): array
    {
        $rows = $this->mapRows($this->fetch($branchId, $fresh));

        return $rows[$productId] ?? [];
    }

    /**
     * Variety breakdown for many products at once.
     * Returns [product_id => [volume => [variant => quantity]]].
     */
    public function stockForProducts(int $branchId, array $productIds, bool $fresh = false): array
    {
        $rows = $this->mapRows($this->fetch($branchId, $fresh));
        $result = [];
        foreach (array_unique(array_map('intval', $productIds)) as $pid) {
            if ($pid > 0 && isset($rows[$pid])) {
                $result[$pid] = $rows[$pid];
            }
        }
        return $result;
    }

    /**
     * Pre-formatted per-product variety picker data for sale/transfer
     * forms: [product_id => [ ['volume' => 50, 'label' => '50ml',
     * 'variants' => [['key' => ..., 'label' => ..., 'available' => N]], ...], ...]]
     * Only volumes and varieties with stock > 0 are included.
     */
    public function bucketsForProducts(int $branchId, array $productIds): array
    {
        $stock = $this->stockForProducts($branchId, $productIds);
        $bottles = new BottleStockService($this->supabase);

        $result = [];
        foreach ($stock as $pid => $pidVarieties) {
            $volumes = [];
            foreach ($pidVarieties as $v => $variantsInStock) {
                if (array_sum($variantsInStock) <= 0) {
                    continue; // This product has none of this volume.
                }
                $variants = [];
                foreach ($bottles->variantBuckets((int) $v) as $key) {
                    $qty = (int) ($variantsInStock[$key] ?? 0);
                    if ($qty <= 0) {
                        continue; // Variety has no stock.
                    }
                    $variants[] = [
                        'key' => $key,
                        'label' => $bottles->variantLabel($key, (int) $v),
                        'available' => $qty,
                    ];
                }
                if (empty($variants)) {
                    continue;
                }
                $volumes[] = [
                    'volume' => (int) $v,
                    'label' => $bottles->volumeLabel((int) $v),
                    'variants' => $variants,
                ];
            }
            usort($volumes, fn ($a, $b) => $a['volume'] <=> $b['volume']);
            if (empty($volumes)) {
                continue;
            }
            $result[(string) $pid] = $volumes;
        }
        return $result;
    }

    /**
     * Whether the product has any variety records at the branch —
     * products stocked in before variety tracking have none and may
     * be sold/transferred without picking a variety.
     */
    public function hasRecords(int $branchId, int $productId, bool $fresh = false): bool
    {
        return !empty($this->stockFor($branchId, $productId, $fresh));
    }

    /**
     * Total units recorded for the product across all varieties.
     */
    public function totalFor(int $branchId, int $productId, bool $fresh = false): int
    {
        $total = 0;
        foreach ($this->stockFor($branchId, $productId, $fresh) as $variants) {
            $total += array_sum($variants);
        }
        return $total;
    }

    /**
     * Increment (positive) or decrement (negative) the matching
     * variety bucket. Creates the row when incrementing from nothing.
     */
    public function adjust(int $branchId, int $productId, int $volume, string $variant, int $delta): void
    {
        if ($delta === 0 || $volume <= 0 || $variant === '') {
            return;
        }

        $existing = $this->supabase->findOne('branch_stock_varieties', [
            'branch_id' => $branchId,
            'product_id' => $productId,
            'volume' => $volume,
            'variant' => $variant,
        ]);

        if ($existing) {
            $this->supabase->update('branch_stock_varieties', [
                'quantity' => max(((int) ($existing['quantity'] ?? 0)) + $delta, 0),
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);
        } elseif ($delta > 0) {
            $this->supabase->insert('branch_stock_varieties', [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'volume' => $volume,
                'variant' => $variant,
                'quantity' => $delta,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Sum of the picked variety across a cart of lines for one
     * product ([['volume' => .., 'variant' => ..], ..]). Used to
     * validate combined quantities before deducting.
     */
    public function availableForPicks(int $branchId, int $productId, array $picks): array
    {
        $stock = $this->stockFor($branchId, $productId, true);
        $errors = [];
        $needed = [];

        foreach ($picks as $pick) {
            $key = ((int) ($pick['volume'] ?? 0)) . '|' . (string) ($pick['variant'] ?? '');
            $needed[$key] = ($needed[$key] ?? 0) + (int) ($pick['quantity'] ?? 0);
        }

        foreach ($needed as $key => $qty) {
            [$volume, $variant] = explode('|', $key);
            $available = (int) ($stock[(int) $volume][$variant] ?? 0);
            if ($qty > $available) {
                $errors[] = "only {$available} in stock";
            }
        }

        return $errors;
    }

    private function fetch(int $branchId, bool $fresh): array
    {
        $params = [
            'select' => 'product_id,volume,variant,quantity',
            'branch_id' => "eq.{$branchId}",
        ];

        return $fresh ? $this->supabase->queryFresh('branch_stock_varieties', $params) : $this->supabase->query('branch_stock_varieties', $params);
    }

    private function mapRows(array $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            $pid = (int) ($r['product_id'] ?? 0);
            $volume = (int) ($r['volume'] ?? 0);
            if ($pid <= 0 || $volume <= 0) {
                continue;
            }
            $variant = (string) ($r['variant'] ?? BottleStockService::VARIANT_PLAIN);
            $qty = (int) ($r['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $map[$pid][$volume][$variant] = ($map[$pid][$volume][$variant] ?? 0) + $qty;
        }
        return $map;
    }

    /**
     * Human label for a bottling, e.g. "50ml With Box · With Logo · Yellow".
     */
    public function pickLabel(int $volume, string $variant): string
    {
        $bottles = new BottleStockService($this->supabase);
        $label = $bottles->volumeLabel($volume);
        if ($variant !== '') {
            $label .= ' ' . $bottles->variantLabel($variant, $volume);
        }
        return $label;
    }
}
