<?php

namespace App\Services;

use Carbon\Carbon;

class FinancialService
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function getBranchFinancials(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toIso8601String();
        $end = $endDate->toIso8601String();

        // Revenue - paid sales
        $sales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
        ]);

        // Filter by date range in PHP
        $sales = array_filter($sales, function ($s) use ($start, $end) {
            $created = $s['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });

        $revenue = array_sum(array_map(fn($s) => $s['total'] ?? 0, $sales));

        // Products sold count
        $allSaleItems = $this->supabase->query('sale_items', [
            'select' => 'sale_id,quantity',
        ]);
        $saleIds = array_map(fn($s) => $s['id'], $sales);
        $filteredItems = array_filter($allSaleItems, function ($item) use ($saleIds) {
            return in_array($item['sale_id'] ?? '', $saleIds) || in_array((int)($item['sale_id'] ?? 0), $saleIds);
        });
        $productsSold = array_sum(array_map(fn($i) => $i['quantity'] ?? 0, $filteredItems));

        // Expenses
        $expenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
        ]);
        $expenses = array_filter($expenses, function ($e) use ($start, $end) {
            $created = $e['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });
        $totalExpenses = array_sum(array_map(fn($e) => $e['amount'] ?? 0, $expenses));

        // Stock info
        $stocks = $this->supabase->query('branch_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'quantity',
        ]);
        $stockRemaining = array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $stocks));

        return [
            'revenue' => $revenue,
            'expenses' => $totalExpenses,
            'transaction_count' => count($sales),
            'products_sold' => $productsSold,
            'stock_remaining' => $stockRemaining,
        ];
    }

    public function getCompanyFinancials(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toIso8601String();
        $end = $endDate->toIso8601String();

        // Batch fetch ALL data in just 5 API calls instead of N per branch
        $branches = $this->supabase->query('branches', [
            'is_active' => 'eq.true',
            'select' => 'id,name',
        ]);

        $allSales = $this->supabase->query('sales', [
            'select' => 'id,branch_id,total,payment_status,created_at',
            'payment_status' => 'eq.paid',
        ]);

        $allExpenses = $this->supabase->query('expenses', [
            'select' => 'branch_id,amount,created_at',
        ]);

        $allSaleItems = $this->supabase->query('sale_items', [
            'select' => 'sale_id,quantity',
        ]);

        // Filter by date in PHP
        $allSales = array_filter($allSales, function ($s) use ($start, $end) {
            $created = $s['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });
        $allExpenses = array_filter($allExpenses, function ($e) use ($start, $end) {
            $created = $e['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });

        // Build sale_id lookup for COGS
        $saleItemsBySale = [];
        foreach ($allSaleItems as $item) {
            $sid = $item['sale_id'] ?? '';
            if (!isset($saleItemsBySale[$sid])) $saleItemsBySale[$sid] = [];
            $saleItemsBySale[$sid][] = $item;
        }

        // Group sales by branch
        $salesByBranch = [];
        foreach ($allSales as $s) {
            $bid = $s['branch_id'] ?? null;
            if ($bid) {
                if (!isset($salesByBranch[$bid])) $salesByBranch[$bid] = [];
                $salesByBranch[$bid][] = $s;
            }
        }

        // Group expenses by branch
        $expensesByBranch = [];
        foreach ($allExpenses as $e) {
            $bid = $e['branch_id'] ?? null;
            if ($bid) {
                if (!isset($expensesByBranch[$bid])) $expensesByBranch[$bid] = [];
                $expensesByBranch[$bid][] = $e;
            }
        }        $total = [
            'revenue' => 0,
            'expenses' => 0, 'transaction_count' => 0,
            'products_sold' => 0, 'by_branch' => [],
        ];

        foreach ($branches as $branch) {
            $bid = $branch['id'];
            $branchSales = $salesByBranch[$bid] ?? [];
            $branchExpenses = $expensesByBranch[$bid] ?? [];

            $revenue = array_sum(array_map(fn($s) => $s['total'] ?? 0, $branchSales));

            // COGS
            $productsSold = 0;
            foreach ($branchSales as $sale) {
                $items = $saleItemsBySale[$sale['id']] ?? [];
                $productsSold += array_sum(array_map(fn($i) => $i['quantity'] ?? 0, $items));
            }

            $totalExpenses = array_sum(array_map(fn($e) => $e['amount'] ?? 0, $branchExpenses));

            $branchFinancials = [
                'revenue' => $revenue,
                'expenses' => $totalExpenses,
                'transaction_count' => count($branchSales),
                'products_sold' => $productsSold,
                'branch_name' => $branch['name'],
            ];

            $total['by_branch'][$bid] = $branchFinancials;
            $total['revenue'] += $revenue;
            $total['expenses'] += $totalExpenses;
            $total['transaction_count'] += count($branchSales);
            $total['products_sold'] += $productsSold;
        }

        return $total;
    }

    /**
     * Today's summary for one branch: daily sales (paid), daily expenses and
     * actual sales (sales minus expenses). Optionally scoped to a single staff
     * member (used for per-staff contribution breakdowns).
     */
    public function getDailySummary(int $branchId, ?int $staffId = null): array
    {
        $todayStart = Carbon::today()->startOfDay()->toIso8601String();

        $salesParams = [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
            'created_at' => "gte.{$todayStart}",
            'select' => 'id,total,cashier_id',
        ];
        if ($staffId !== null) {
            $salesParams['cashier_id'] = "eq.{$staffId}";
        }

        $sales = $this->supabase->query('sales', $salesParams);

        $expensesParams = [
            'branch_id' => "eq.{$branchId}",
            'created_at' => "gte.{$todayStart}",
            'select' => 'id,amount,user_id',
        ];
        if ($staffId !== null) {
            $expensesParams['user_id'] = "eq.{$staffId}";
        }

        $expenses = $this->supabase->query('expenses', $expensesParams);

        $dailySales = (float) array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $sales));
        $dailyExpenses = (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $expenses));

        // Cost of goods sold from the per-sale unit costs recorded at sale time.
        $cogs = $this->sumCostOfGoods(array_map(fn ($s) => (int) $s['id'], $sales));
        $grossProfit = $dailySales - $cogs;

        return [
            'daily_sales' => $dailySales,
            'daily_expenses' => $dailyExpenses,
            'actual_sales' => $dailySales - $dailyExpenses,
            'transaction_count' => count($sales),
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit - $dailyExpenses,
        ];
    }

    /**
     * Profit summary for one branch: gross profit (revenue - cost of goods sold)
     * and net profit (gross profit - expenses). COGS comes from the unit_cost
     * recorded on each sale item at sale time (auto-derived from the product's
     * unit cost at stock-in).
     */
    public function getProfitSummary(int $branchId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $start = ($startDate ?? Carbon::today())->startOfDay()->toIso8601String();
        $end = ($endDate ?? Carbon::now())->toIso8601String();

        $sales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
            'created_at' => "gte.{$start}",
            'select' => 'id,total,created_at',
        ]);
        $sales = array_values(array_filter($sales, fn ($s) => ($s['created_at'] ?? '') <= $end));

        $revenue = (float) array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $sales));

        $cogs = $this->sumCostOfGoods(array_map(fn ($s) => (int) $s['id'], $sales));

        $expenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
            'created_at' => "gte.{$start}",
            'select' => 'id,amount,created_at',
        ]);
        $expenses = array_filter($expenses, fn ($e) => ($e['created_at'] ?? '') <= $end);
        $totalExpenses = (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $expenses));

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $revenue - $cogs,
            'expenses' => $totalExpenses,
            'net_profit' => $revenue - $cogs - $totalExpenses,
            'transaction_count' => count($sales),
        ];
    }

    /**
     * Company-wide profit summary grouped by branch. Two batch queries for
     * sales + expenses plus one sale_items lookup for COGS.
     */
    public function getCompanyProfitSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $start = ($startDate ?? Carbon::today())->startOfDay()->toIso8601String();
        $end = ($endDate ?? Carbon::now())->toIso8601String();

        $sales = $this->supabase->query('sales', [
            'payment_status' => 'eq.paid',
            'created_at' => "gte.{$start}",
            'select' => 'id,branch_id,total,created_at',
        ]);
        $sales = array_values(array_filter($sales, fn ($s) => ($s['created_at'] ?? '') <= $end));

        $expenses = $this->supabase->query('expenses', [
            'created_at' => "gte.{$start}",
            'select' => 'branch_id,amount,created_at',
        ]);
        $expenses = array_filter($expenses, fn ($e) => ($e['created_at'] ?? '') <= $end);

        // COGS per sale, then mapped to branches
        $cogsBySale = $this->costOfGoodsBySale(array_map(fn ($s) => (int) $s['id'], $sales));

        $byBranch = [];
        $totals = ['revenue' => 0.0, 'cogs' => 0.0, 'gross_profit' => 0.0, 'expenses' => 0.0, 'net_profit' => 0.0, 'transaction_count' => 0];

        $ensureBranch = function ($bid) use (&$byBranch) {
            if (!isset($byBranch[$bid])) {
                $byBranch[$bid] = ['revenue' => 0.0, 'cogs' => 0.0, 'gross_profit' => 0.0, 'expenses' => 0.0, 'net_profit' => 0.0, 'transaction_count' => 0];
            }
            return $byBranch[$bid];
        };

        foreach ($sales as $s) {
            $bid = (int) ($s['branch_id'] ?? 0);
            $row = $ensureBranch($bid);
            $revenue = (float) ($s['total'] ?? 0);
            $cogs = (float) ($cogsBySale[(int) $s['id']] ?? 0);

            $row['revenue'] += $revenue;
            $row['cogs'] += $cogs;
            $row['gross_profit'] += $revenue - $cogs;
            $row['transaction_count']++;
            $byBranch[$bid] = $row;
        }

        foreach ($expenses as $e) {
            $bid = (int) ($e['branch_id'] ?? 0);
            $row = $ensureBranch($bid);
            $row['expenses'] += (float) ($e['amount'] ?? 0);
            $byBranch[$bid] = $row;
        }

        foreach ($byBranch as $bid => $row) {
            $row['net_profit'] = $row['gross_profit'] - $row['expenses'];
            $byBranch[$bid] = $row;

            $totals['revenue'] += $row['revenue'];
            $totals['cogs'] += $row['cogs'];
            $totals['gross_profit'] += $row['gross_profit'];
            $totals['expenses'] += $row['expenses'];
            $totals['net_profit'] += $row['net_profit'];
            $totals['transaction_count'] += $row['transaction_count'];
        }

        return ['by_branch' => $byBranch] + $totals;
    }

    /**
     * Total cost of goods sold for a set of sale ids (single batched query).
     */
    private function sumCostOfGoods(array $saleIds): float
    {
        if (empty($saleIds)) {
            return 0.0;
        }

        $items = $this->supabase->query('sale_items', [
            'sale_id' => 'in.(' . implode(',', $saleIds) . ')',
            'select' => 'quantity,unit_cost',
        ]);

        return (float) array_sum(array_map(
            fn ($i) => (float) ($i['quantity'] ?? 0) * (float) ($i['unit_cost'] ?? 0),
            $items
        ));
    }

    /**
     * COGS mapped by sale id, for per-branch rollups.
     */
    private function costOfGoodsBySale(array $saleIds): array
    {
        if (empty($saleIds)) {
            return [];
        }

        $items = $this->supabase->query('sale_items', [
            'sale_id' => 'in.(' . implode(',', $saleIds) . ')',
            'select' => 'sale_id,quantity,unit_cost',
        ]);

        $map = [];
        foreach ($items as $i) {
            $sid = (int) ($i['sale_id'] ?? 0);
            $map[$sid] = ($map[$sid] ?? 0) + (float) ($i['quantity'] ?? 0) * (float) ($i['unit_cost'] ?? 0);
        }

        return $map;
    }

    public function getExpensesByCategory(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toIso8601String();
        $end = $endDate->toIso8601String();

        $expenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
        ]);

        $expenses = array_filter($expenses, function ($e) use ($start, $end) {
            $created = $e['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });

        $byCategory = [];
        foreach ($expenses as $e) {
            $cat = $e['category'] ?? 'other';
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = ['category' => $cat, 'total' => 0, 'count' => 0];
            }
            $byCategory[$cat]['total'] += $e['amount'] ?? 0;
            $byCategory[$cat]['count']++;
        }

        return array_values($byCategory);
    }
}
