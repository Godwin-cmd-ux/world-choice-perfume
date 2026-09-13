<?php

namespace App\Services;

class BottleStockService
{
    public const VOLUMES = [6, 12, 30, 50, 100];

    // Volumes that carry box/logo/color details (30ml, 50ml, 100ml).
    public const DETAIL_VOLUMES = [30, 50, 100];

    // Variant buckets per volume (>= 30ml only).
    public const VARIANT_BOX_LOGO_YELLOW = 'box_logo_yellow';
    public const VARIANT_BOX_LOGO_BLACK  = 'box_logo_black';
    public const VARIANT_BOX_NOLOGO_BLACK = 'box_nologo_black';
    public const VARIANT_BOX_NOLOGO_WHITE = 'box_nologo_white';
    public const VARIANT_NO_BOX = 'no_box';
    public const VARIANT_PLAIN = 'plain';

    public const VARIANT_DETAILS = [
        self::VARIANT_BOX_LOGO_YELLOW,
        self::VARIANT_BOX_LOGO_BLACK,
        self::VARIANT_BOX_NOLOGO_BLACK,
        self::VARIANT_BOX_NOLOGO_WHITE,
    ];

    private SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function volumeLabel(int $volume): string
    {
        return "{$volume}ml";
    }

    public function parseVolume(string $label): ?int
    {
        $volume = (int) str_replace('ml', '', $label);
        return in_array($volume, self::VOLUMES, true) ? $volume : null;
    }

    /**
     * Whether a volume participates in the box/logo/color breakdown
     * (30ml, 50ml, 100ml). 6ml and 12ml neglect the details flow.
     */
    public function volumeHasDetails(int $volume): bool
    {
        return in_array($volume, self::DETAIL_VOLUMES, true);
    }

    /**
     * All variant buckets available for a volume.
     */
    public function variantBuckets(int $volume): array
    {
        if (!$this->volumeHasDetails($volume)) {
            return [self::VARIANT_PLAIN];
        }

        return array_merge(self::VARIANT_DETAILS, [self::VARIANT_NO_BOX, self::VARIANT_PLAIN]);
    }

    /**
     * Canonical variant key for a stock entry.
     *
     * Flow for 30/50/100ml:
     *   Without Box  -> no_box
     *   With Box + With Logo   -> box_logo_{yellow|black}
     *   With Box + No Logo     -> box_nologo_{black|white}
     * 6/12ml always resolve to plain (no details).
     */
    public function variantKey(int $volume, ?string $hasBox, ?string $hasLogo = null, ?string $logoColor = null): string
    {
        if (!$this->volumeHasDetails($volume)) {
            return self::VARIANT_PLAIN;
        }

        if ($hasBox === 'no') {
            return self::VARIANT_NO_BOX;
        }

        if ($hasBox === 'yes') {
            if ($hasLogo === 'yes') {
                return $logoColor === 'black' ? self::VARIANT_BOX_LOGO_BLACK : self::VARIANT_BOX_LOGO_YELLOW;
            }
            if ($hasLogo === 'no') {
                return $logoColor === 'white' ? self::VARIANT_BOX_NOLOGO_WHITE : self::VARIANT_BOX_NOLOGO_BLACK;
            }
        }

        return self::VARIANT_NO_BOX;
    }

    /**
     * Human-readable label for a variant bucket.
     */
    public function variantLabel(string $variant, int $volume = 0): string
    {
        return match ($variant) {
            self::VARIANT_BOX_LOGO_YELLOW => 'With Box · With Logo · Yellow',
            self::VARIANT_BOX_LOGO_BLACK  => 'With Box · With Logo · Black',
            self::VARIANT_BOX_NOLOGO_BLACK => 'With Box · No Logo · Black',
            self::VARIANT_BOX_NOLOGO_WHITE => 'With Box · No Logo · White',
            self::VARIANT_NO_BOX => 'Without Box',
            default => $this->volumeHasDetails($volume) ? 'Unclassified' : 'No details',
        };
    }

    /**
     * Current bottle stock for every supported volume (volume in ml => quantity).
     */
    public function stockMap(int $branchId): array
    {
        $map = [];
        foreach (self::VOLUMES as $volume) {
            $map[$volume] = 0;
        }

        $rows = $this->supabase->query('bottle_stock', [
            'branch_id' => "eq.{$branchId}",
        ]);

        foreach ($rows as $row) {
            $volume = $this->parseVolume((string) ($row['volume'] ?? ''));
            if ($volume !== null) {
                $map[$volume] += (int) ($row['quantity'] ?? 0);
            }
        }

        return $map;
    }

    /**
     * Per-variant bottle stock for every supported volume.
     * Returns [volume => [variant => quantity, ...]].
     */
    public function variantStock(int $branchId): array
    {
        $map = [];
        foreach (self::VOLUMES as $volume) {
            $map[$volume] = [];
            foreach ($this->variantBuckets($volume) as $key) {
                $map[$volume][$key] = 0;
            }
        }

        $rows = $this->supabase->query('bottle_stock', [
            'branch_id' => "eq.{$branchId}",
        ]);

        foreach ($rows as $row) {
            $volume = $this->parseVolume((string) ($row['volume'] ?? ''));
            if ($volume === null) {
                continue;
            }
            $variant = (string) ($row['variant'] ?? self::VARIANT_PLAIN);
            if (!isset($map[$volume][$variant])) {
                $map[$volume][$variant] = 0;
            }
            $map[$volume][$variant] += (int) ($row['quantity'] ?? 0);
        }

        return $map;
    }

    /**
     * Deduct bottle stock from a variant bucket, recording a stock_out
     * movement. When $variant is empty, deducts from the first bucket
     * that has enough stock.
     * Returns the new bucket quantity, or null when stock is insufficient.
     */
    public function deduct(int $branchId, int $volume, int $quantity, string $reason, ?string $performedBy = null, string $variant = ''): ?int
    {
        if ($quantity <= 0 || !in_array($volume, self::VOLUMES, true)) {
            return null;
        }

        $label = $this->volumeLabel($volume);

        // queryFresh avoids the request cache so consecutive deductions read the latest value
        $rows = $this->supabase->queryFresh('bottle_stock', [
            'branch_id' => "eq.{$branchId}",
            'volume' => "eq.{$label}",
        ]);

        $target = null;
        foreach ($rows as $row) {
            $rowVariant = (string) ($row['variant'] ?? self::VARIANT_PLAIN);
            if ($variant !== '' && $rowVariant !== $variant) {
                continue;
            }
            if ((int) ($row['quantity'] ?? 0) >= $quantity) {
                $target = $row;
                break;
            }
        }

        if (!$target) {
            return null;
        }

        $newQty = (int) ($target['quantity'] ?? 0) - $quantity;

        $this->supabase->update('bottle_stock', [
            'quantity' => $newQty,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $target['id']]);

        $movement = [
            'branch_id' => $branchId,
            'volume' => $label,
            'type' => 'stock_out',
            'quantity' => $quantity,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->supabase->tableHasColumn('bottle_stock_movements', 'variant')) {
            $movement['variant'] = (string) ($target['variant'] ?? self::VARIANT_PLAIN);
        }

        $this->supabase->insert('bottle_stock_movements', $movement);

        return $newQty;
    }

    /**
     * Get or create the product record used to sell empty bottles on a receipt.
     */
    public function findOrCreateEmptyBottleProduct(int $volume): ?array
    {
        if (!in_array($volume, self::VOLUMES, true)) {
            return null;
        }

        $name = 'Empty Bottle ' . $this->volumeLabel($volume);

        $rows = $this->supabase->query('products', [
            'name' => "eq.{$name}",
            'select' => '*',
            'limit' => 1,
        ]);
        $product = $rows[0] ?? null;

        if ($product) {
            return $product;
        }

        return $this->supabase->insert('products', [
            'name' => $name,
            'brand' => 'Empty Bottles',
            'is_active' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}