<?php

namespace App\Services;

use App\Support\BranchAccess;
use App\Support\BranchCategory;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Low-stock alerts for the stock admin.
 *
 * One alert is kept per branch/item/threshold. Reading it clears it from the
 * unread badge; it is not recreated on every page load while the stock is low.
 * Bottle and oil-fragrance alerts are skipped for products-only branches such
 * as Head Quarters-Mikocheni, where those stock modules are not used.
 */
class StockNotificationService
{
    public const TYPE = 'stock_alert';

    public const BRAND_THRESHOLD = 2;
    public const BOTTLE_THRESHOLD = 100;
    public const OIL_THRESHOLD = 2;

    private SupabaseService $supabase;

    private static bool $syncedThisRequest = false;

    public function __construct(?SupabaseService $supabase = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
    }

    /**
     * Bring the stock-admin notification feed up to date. Safe to call often:
     * an existing alert key is never inserted twice.
     */
    public function sync(): void
    {
        if (self::$syncedThisRequest) {
            return;
        }
        self::$syncedThisRequest = true;

        try {
            $branches = $this->supabase->queryFresh('branches', [
                'select' => 'id,name,category',
            ]);

            $branchNames = [];
            $excludedBranchIds = [];
            foreach ($branches as $branch) {
                $id = (int) ($branch['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $branchNames[$id] = (string) ($branch['name'] ?? ('Branch #' . $id));
                if ($this->isBottleExemptBranch($branch)) {
                    $excludedBranchIds[$id] = true;
                }
            }

            $existingKeys = $this->existingAlertKeys();

            $this->syncBrandProducts($branchNames, $existingKeys);
            $this->syncBottles($branchNames, $excludedBranchIds, $existingKeys);
            $this->syncOilFragrances($branchNames, $excludedBranchIds, $existingKeys);
        } catch (Throwable $e) {
            // Notifications must never break a stock page or a badge refresh.
            Log::warning('Stock notifications failed: ' . $e->getMessage());
        }
    }

    private function isBottleExemptBranch(array $branch): bool
    {
        $name = trim((string) ($branch['name'] ?? ''));

        return BranchCategory::from($branch['category'] ?? null) === BranchCategory::PRODUCTS_BASED
            || mb_strtolower($name) === mb_strtolower(BranchAccess::HEAD_QUARTERS);
    }

    private function existingAlertKeys(): array
    {
        $rows = $this->supabase->query('admin_notifications', [
            'select' => 'id,data',
            'type' => 'eq.' . self::TYPE,
            'order' => 'created_at.desc',
            'limit' => 1000,
        ]);

        $keys = [];
        foreach ($rows as $row) {
            $data = $row['data'] ?? null;
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            if (is_array($data) && !empty($data['alert_key'])) {
                $keys[(string) $data['alert_key']] = true;
            }
        }

        return $keys;
    }

    private function insertAlert(
        array &$existingKeys,
        string $key,
        ?int $branchId,
        string $title,
        string $message,
        array $data
    ): void {
        if (isset($existingKeys[$key])) {
            return;
        }

        $now = now()->toIso8601String();
        $result = $this->supabase->insert('admin_notifications', [
            'type' => self::TYPE,
            'branch_id' => $branchId,
            'user_id' => null,
            'title' => $title,
            'message' => $message,
            'data' => json_encode(array_merge(['alert_key' => $key], $data)),
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($result !== null) {
            $existingKeys[$key] = true;
        }
    }

    private function syncBrandProducts(array $branchNames, array &$existingKeys): void
    {
        $stocks = $this->supabase->queryFresh('branch_stock', [
            'select' => 'id,branch_id,product_id,quantity,category',
            'quantity' => 'lte.' . self::BRAND_THRESHOLD,
        ]);

        if (empty($stocks)) {
            return;
        }

        $productIds = array_values(array_unique(array_filter(array_map(
            fn ($s) => (int) ($s['product_id'] ?? 0),
            $stocks
        ))));

        $products = [];
        if (!empty($productIds)) {
            foreach ($this->supabase->query('products', [
                'select' => 'id,name,brand,category',
                'id' => 'in.(' . implode(',', $productIds) . ')',
            ]) as $product) {
                $products[(int) ($product['id'] ?? 0)] = $product;
            }
        }

        foreach ($stocks as $stock) {
            $productId = (int) ($stock['product_id'] ?? 0);
            $product = $products[$productId] ?? [];
            $category = (string) ($stock['category'] ?? ($product['category'] ?? ''));
            if ($productId <= 0 || $category !== 'Brand Perfume') {
                continue;
            }

            $branchId = (int) ($stock['branch_id'] ?? 0);
            $quantity = (int) ($stock['quantity'] ?? 0);
            $name = trim((string) ($product['name'] ?? ('Product #' . $productId)));
            $branchName = $branchNames[$branchId] ?? ('Branch #' . $branchId);

            $this->insertAlert(
                $existingKeys,
                "brand:{$branchId}:{$productId}",
                $branchId > 0 ? $branchId : null,
                'Brand Product Low Stock',
                "{$name} at {$branchName} has reached {$quantity} units (alert threshold: " . self::BRAND_THRESHOLD . ').',
                [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'threshold' => self::BRAND_THRESHOLD,
                ]
            );
        }
    }

    private function syncBottles(array $branchNames, array $excludedBranchIds, array &$existingKeys): void
    {
        $bottles = $this->supabase->queryFresh('bottle_stock', [
            'select' => 'id,branch_id,volume,variant,quantity',
            'quantity' => 'lte.' . (self::BOTTLE_THRESHOLD - 1),
        ]);
        $bottleService = new BottleStockService($this->supabase);

        foreach ($bottles as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId <= 0 || isset($excludedBranchIds[$branchId])) {
                continue;
            }

            $volume = (int) $bottleService->parseVolume((string) ($row['volume'] ?? ''));
            if ($volume <= 0) {
                continue;
            }

            $variant = (string) ($row['variant'] ?? BottleStockService::VARIANT_PLAIN);
            $quantity = (int) ($row['quantity'] ?? 0);
            $branchName = $branchNames[$branchId] ?? ('Branch #' . $branchId);
            $detail = $bottleService->variantLabel($variant, $volume);
            $key = "bottle:{$branchId}:{$volume}:{$variant}";

            $this->insertAlert(
                $existingKeys,
                $key,
                $branchId,
                'Bottle Stock Low',
                "{$volume}ml ({$detail}) bottles at {$branchName} are below " . self::BOTTLE_THRESHOLD . " — currently {$quantity}.",
                [
                    'volume' => $volume,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'threshold' => self::BOTTLE_THRESHOLD,
                ]
            );
        }
    }

    private function syncOilFragrances(array $branchNames, array $excludedBranchIds, array &$existingKeys): void
    {
        $oils = $this->supabase->queryFresh('oil_fragrance_stock', [
            'select' => 'id,branch_id,name,quantity,volume',
            'quantity' => 'lte.' . (self::OIL_THRESHOLD - 1),
        ]);

        foreach ($oils as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId <= 0 || isset($excludedBranchIds[$branchId])) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? 'Oil Fragrance'));
            $quantity = (int) ($row['quantity'] ?? 0);
            $volume = (int) ($row['volume'] ?? 0);
            $branchName = $branchNames[$branchId] ?? ('Branch #' . $branchId);
            $label = $name . ($volume > 0 ? " ({$volume}ml)" : '');

            $this->insertAlert(
                $existingKeys,
                "oil:{$branchId}:{$row['id']}",
                $branchId,
                'Oil Fragrance Low',
                "{$label} at {$branchName} is below " . self::OIL_THRESHOLD . " — currently {$quantity}.",
                [
                    'oil_stock_id' => (int) ($row['id'] ?? 0),
                    'quantity' => $quantity,
                    'threshold' => self::OIL_THRESHOLD,
                ]
            );
        }
    }
}
