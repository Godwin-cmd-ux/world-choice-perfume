<?php

namespace App\Http\Controllers\Api\StockManager;

use Illuminate\Http\Request;

/**
 * JSON twin of StockManagerController::dashboard / crossBranchDashboard.
 *
 * The dashboard is products-only aware exactly like the Blade: when the
 * manager's branch is `products_based`, the bottle and oil figures come back
 * zeroed and their recent movements are skipped, so the app renders the same
 * reduced board the website shows.
 */
class SmDashboardController extends SmBaseController
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function dashboard(Request $request)
    {
        $branchId = $this->activeBranchId($request);
        $isProductsOnly = $this->isProductsOnly($request);

        // Product stock stats.
        $productStock = $this->supabase->query('branch_stock', $this->branchParams($request, [
            'select' => 'quantity,selling_price',
        ]));
        $totalProductItems = array_sum(array_map(fn ($s) => $s['quantity'] ?? 0, $productStock));
        $totalProductTypes = count($productStock);
        $lowStockProducts = count(array_filter($productStock, fn ($s) => ($s['quantity'] ?? 0) <= self::LOW_STOCK_THRESHOLD));

        // Bottle / oil stats — skipped for a products-only branch.
        $bottleStock = $isProductsOnly ? [] : $this->supabase->query('bottle_stock', $this->branchParams($request, [
            'select' => '*',
        ]));
        $totalBottles = array_sum(array_map(fn ($b) => $b['quantity'] ?? 0, $bottleStock));

        $oilStock = $isProductsOnly ? [] : $this->supabase->query('oil_fragrance_stock', $this->branchParams($request, [
            'select' => '*',
        ]));
        $totalOilFragrances = array_sum(array_map(fn ($o) => $o['quantity'] ?? 0, $oilStock));

        // The manager's OWN day of sales (same cashier match as the website).
        $todayStart = now()->startOfDay()->toIso8601String();
        $mySalesToday = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'cashier_id' => 'eq.'.$this->performingUserId($request),
            'created_at' => "gte.{$todayStart}",
            'select' => 'total',
        ]);
        $mySalesTodayCount = count($mySalesToday);
        $mySalesTodayTotal = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $mySalesToday));

        // Orders summary.
        $pendingOrders = $this->supabase->count('orders', [
            'branch_id' => "eq.{$branchId}",
            'status' => 'eq.pending',
        ]);
        $openOrders = $this->supabase->count('orders', [
            'branch_id' => "eq.{$branchId}",
            'status' => 'in.(pending,picked)',
        ]);
        $ordersToday = $this->supabase->count('orders', [
            'branch_id' => "eq.{$branchId}",
            'created_at' => "gte.{$todayStart}",
        ]);

        // Recent movements (skipped for products-only branches).
        $recentBottleMovements = $isProductsOnly ? [] : $this->loadMovementsWithUser('bottle_stock_movements', $branchId, 5);
        $recentOilMovements = $isProductsOnly ? [] : $this->loadMovementsWithUser('oil_fragrance_movements', $branchId, 5);

        return response()->json([
            'totalProductItems' => $totalProductItems,
            'totalProductTypes' => $totalProductTypes,
            'lowStockProducts' => $lowStockProducts,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
            'mySalesTodayCount' => $mySalesTodayCount,
            'mySalesTodayTotal' => $mySalesTodayTotal,
            'totalBottles' => $totalBottles,
            'totalOilFragrances' => $totalOilFragrances,
            'recentBottleMovements' => $recentBottleMovements,
            'recentOilMovements' => $recentOilMovements,
            'pendingOrders' => $pendingOrders,
            'openOrders' => $openOrders,
            'ordersToday' => $ordersToday,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * JSON twin of crossBranchDashboard: per-branch stock overview for the
     * Kinondoni stock manager (every branch but his own, as on the website).
     */
    public function crossBranch(Request $request)
    {
        $this->assertCrossBranchMonitor($request);

        $myBranchId = $this->ownBranchId($request);

        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address',
            'order' => 'name.asc',
        ]);

        $rows = collect($branches)
            ->reject(fn ($b) => (int) $b['id'] === $myBranchId)
            ->map(function ($b) {
                $id = (int) $b['id'];

                $branchStock = $this->supabase->query('branch_stock', [
                    'select' => 'quantity,selling_price',
                    'branch_id' => "eq.{$id}",
                ]);
                $bottleStock = $this->supabase->query('bottle_stock', [
                    'select' => 'quantity',
                    'branch_id' => "eq.{$id}",
                ]);
                $oilStock = $this->supabase->query('oil_fragrance_stock', [
                    'select' => 'quantity',
                    'branch_id' => "eq.{$id}",
                ]);

                return [
                    'id' => $id,
                    'name' => $b['name'] ?? '',
                    'address' => $b['address'] ?? null,
                    'totalProducts' => array_sum(array_map(fn ($s) => $s['quantity'] ?? 0, $branchStock)),
                    'lowStock' => count(array_filter($branchStock, fn ($s) => ($s['quantity'] ?? 0) <= self::LOW_STOCK_THRESHOLD)),
                    'totalBottles' => array_sum(array_map(fn ($s) => $s['quantity'] ?? 0, $bottleStock)),
                    'totalOils' => array_sum(array_map(fn ($s) => $s['quantity'] ?? 0, $oilStock)),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'rows' => $rows,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * The scope flags on their own, so the app can decide which tabs to show
     * before loading a heavy screen.
     */
    public function scope(Request $request)
    {
        $payload = $this->scopePayload($request);

        // Branches the monitor may switch to (the website's enter list).
        if ($payload['can_monitor_cross_branch']) {
            $payload['monitorable_branches'] = collect($this->supabase->query('branches', [
                'select' => 'id,name,address',
                'is_active' => 'eq.true',
                'order' => 'name.asc',
            ]))->reject(fn ($b) => (int) $b['id'] === $payload['own_branch_id'])->values()->all();
        }

        return response()->json($payload);
    }
}
