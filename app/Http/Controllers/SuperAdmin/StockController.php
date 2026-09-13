<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Cross-branch stock overview for the Super Admin.
     * Shows the stock summary, product-level stock and recent stock activity
     * for EVERY branch — including the Kinondoni branch.
     */
    public function crossBranch(Request $request)
    {
        $branchId = $request->branch_id ? (int) $request->branch_id : null;

        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address',
            'order' => 'name.asc',
        ]);

        $branchMap = [];
        foreach ($branches as $b) {
            $branchMap[(int) $b['id']] = $b['name'] ?? ('Branch #' . $b['id']);
        }

        // ------------------------------------------------------------
        // 1. Per-branch summary (PHP-side aggregation — all branches)
        // ------------------------------------------------------------
        $allBranchStock = $this->supabase->query('branch_stock', [
            'select' => 'branch_id,quantity,selling_price',
        ]);
        $allBottleStock = $this->supabase->query('bottle_stock', [
            'select' => 'branch_id,quantity',
        ]);
        $allOilStock = $this->supabase->query('oil_fragrance_stock', [
            'select' => 'branch_id,quantity',
        ]);

        $stockByBranch = [];
        foreach ($allBranchStock as $s) {
            $stockByBranch[(int) ($s['branch_id'] ?? 0)][] = $s;
        }
        $bottlesByBranch = [];
        foreach ($allBottleStock as $s) {
            $bottlesByBranch[(int) ($s['branch_id'] ?? 0)][] = $s;
        }
        $oilsByBranch = [];
        foreach ($allOilStock as $s) {
            $oilsByBranch[(int) ($s['branch_id'] ?? 0)][] = $s;
        }

        $summary = collect($branches)->map(function ($b) use ($stockByBranch, $bottlesByBranch, $oilsByBranch) {
            $id = (int) $b['id'];
            $stock = $stockByBranch[$id] ?? [];
            $bottles = $bottlesByBranch[$id] ?? [];
            $oils = $oilsByBranch[$id] ?? [];

            $lowStock = array_filter($stock, fn($s) => ($s['quantity'] ?? 0) <= 5);

            return (object) [
                'id' => $id,
                'name' => $b['name'] ?? '',
                'address' => $b['address'] ?? null,
                'productItems' => count($stock),
                'totalProductQty' => array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $stock)),
                'lowStock' => count($lowStock),
                'totalValue' => array_sum(array_map(fn($s) => ($s['quantity'] ?? 0) * ($s['selling_price'] ?? 0), $stock)),
                'totalBottles' => array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $bottles)),
                'totalOils' => array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $oils)),
            ];
        });

        // ------------------------------------------------------------
        // 2. Product-level stock (branch filter applies)
        // ------------------------------------------------------------
        $detailParams = [
            'select' => '*, product:products(id,name,brand), branch:branches(id,name)',
            'order' => 'created_at.desc',
            'limit' => 300,
        ];
        if ($branchId) {
            $detailParams['branch_id'] = "eq.{$branchId}";
        }

        $stocks = $this->supabase->query('branch_stock', $detailParams);

        $detail = array_map(function ($s) {
            return [
                'product_id' => $s['product_id'] ?? null,
                'product' => $s['product']['name'] ?? 'Unknown',
                'branch' => $s['branch']['name'] ?? 'Unknown',
                'quantity' => $s['quantity'] ?? 0,
                'selling_price' => $s['selling_price'] ?? 0,
                'stock_value' => ($s['quantity'] ?? 0) * ($s['selling_price'] ?? 0),
            ];
        }, $stocks);

        // ------------------------------------------------------------
        // 3. Recent stock activity (branch filter applies)
        // ------------------------------------------------------------
        $activity = collect(array_merge(
            $this->productMovements($branchId),
            $this->bottleMovements($branchId),
            $this->oilMovements($branchId)
        ))->sortByDesc('created_at')->values();

        $branchesList = collect($branches)->map(fn($b) => (object) $b);

        return view('super-admin.stock.cross-branch', [
            'summary' => $summary,
            'detail' => $detail,
            'activity' => $activity,
            'branches' => $branchesList,
            'branchMap' => $branchMap,
            'selectedBranchId' => $branchId,
        ]);
    }

    private function movementParams(?int $branchId, int $limit = 60): array
    {
        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => $limit,
        ];
        if ($branchId) {
            $params['branch_id'] = "eq.{$branchId}";
        }
        return $params;
    }

    private function resolvedNames(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            if (!empty($r['performed_by'])) {
                $ids[(int) $r['performed_by']] = true;
            }
        }
        $ids = array_keys($ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        if (empty($ids)) {
            return [];
        }

        $map = [];
        $supRows = $this->supabase->query('users', [
            'select' => 'id,name',
            'id' => 'in.(' . implode(',', $ids) . ')',
            'limit' => 200,
        ]);
        foreach ($supRows as $u) {
            $map[(int) $u['id']] = $u['name'];
        }
        return $map;
    }

    private function productMovements(?int $branchId): array
    {
        $rows = $this->supabase->queryFresh('stock_movements', $this->movementParams($branchId));
        $names = $this->resolvedNames($rows);

        $productIds = array_values(array_unique(array_map(fn($r) => (int) ($r['product_id'] ?? 0), $rows)));
        $productIds = array_filter($productIds, fn($id) => $id > 0);
        $productNames = [];
        if (!empty($productIds)) {
            $products = $this->supabase->query('products', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $productIds) . ')',
            ]);
            foreach ($products as $p) {
                $productNames[(int) $p['id']] = $p['name'];
            }
        }

        return array_map(function ($r) use ($names, $productNames) {
            return [
                'kind' => 'product',
                'type' => $r['type'] ?? '',
                'branch_id' => $r['branch_id'] ?? null,
                'title' => $productNames[(int) ($r['product_id'] ?? 0)] ?? ('Product #' . ($r['product_id'] ?? '?')),
                'detail' => $r['notes'] ?? null,
                'quantity' => (float) ($r['quantity'] ?? 0),
                'performed_by' => $names[(int) ($r['performed_by'] ?? 0)] ?? null,
                'created_at' => $r['created_at'] ?? '',
            ];
        }, $rows);
    }

    private function bottleMovements(?int $branchId): array
    {
        $rows = $this->supabase->queryFresh('bottle_stock_movements', $this->movementParams($branchId));
        $names = $this->resolvedNames($rows);

        return array_map(function ($r) use ($names) {
            $title = trim(($r['volume'] ?? '') . (isset($r['variant']) && $r['variant'] ? ' — ' . $r['variant'] : ''));
            return [
                'kind' => 'bottle',
                'type' => $r['type'] ?? '',
                'branch_id' => $r['branch_id'] ?? null,
                'title' => $title !== '' ? $title : 'Bottle',
                'detail' => $r['reason'] ?? null,
                'quantity' => (float) ($r['quantity'] ?? 0),
                'performed_by' => $names[(int) ($r['performed_by'] ?? 0)] ?? null,
                'created_at' => $r['created_at'] ?? '',
            ];
        }, $rows);
    }

    private function oilMovements(?int $branchId): array
    {
        $rows = $this->supabase->queryFresh('oil_fragrance_movements', $this->movementParams($branchId));
        $names = $this->resolvedNames($rows);

        return array_map(function ($r) use ($names) {
            $title = (string) ($r['name'] ?? 'Oil fragrance');
            if (!empty($r['volume'])) {
                $title .= ' (' . $r['volume'] . ')';
            }
            return [
                'kind' => 'oil',
                'type' => $r['type'] ?? '',
                'branch_id' => $r['branch_id'] ?? null,
                'title' => $title,
                'detail' => $r['reason'] ?? null,
                'quantity' => (float) ($r['quantity'] ?? 0),
                'performed_by' => $names[(int) ($r['performed_by'] ?? 0)] ?? null,
                'created_at' => $r['created_at'] ?? '',
            ];
        }, $rows);
    }
}