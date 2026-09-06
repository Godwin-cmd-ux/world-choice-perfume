<?php

namespace App\Services;

class BottleStockService
{
    public const VOLUMES = [6, 12, 30, 50, 100];

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
                $map[$volume] = (int) ($row['quantity'] ?? 0);
            }
        }

        return $map;
    }

    /**
     * Deduct bottle stock, recording a stock_out movement.
     * Returns the new quantity, or null when stock is insufficient.
     */
    public function deduct(int $branchId, int $volume, int $quantity, string $reason, ?string $performedBy = null): ?int
    {
        if ($quantity <= 0 || !in_array($volume, self::VOLUMES, true)) {
            return null;
        }

        $label = $this->volumeLabel($volume);

        // queryFresh avoids the request cache so consecutive deductions read the latest value
        $rows = $this->supabase->queryFresh('bottle_stock', [
            'branch_id' => "eq.{$branchId}",
            'volume' => "eq.{$label}",
            'limit' => 1,
        ]);
        $existing = $rows[0] ?? null;

        if (!$existing || (int) ($existing['quantity'] ?? 0) < $quantity) {
            return null;
        }

        $newQty = (int) ($existing['quantity'] ?? 0) - $quantity;

        $this->supabase->update('bottle_stock', [
            'quantity' => $newQty,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $existing['id']]);

        $this->supabase->insert('bottle_stock_movements', [
            'branch_id' => $branchId,
            'volume' => $label,
            'type' => 'stock_out',
            'quantity' => $quantity,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

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
            'category' => 'Empty Bottle',
            'is_active' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}