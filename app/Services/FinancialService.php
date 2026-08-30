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

        // Revenue - paid sales (1 API call)
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

        // COGS - fetch ALL sale items for this branch in one call (1 API call)
        $allSaleItems = $this->supabase->query('sale_items', [
            'select' => 'sale_id,unit_cost,quantity',
        ]);

        // Filter to only items belonging to sales in this branch/date range
        $saleIds = array_map(fn($s) => $s['id'], $sales);
        $filteredItems = array_filter($allSaleItems, function ($item) use ($saleIds) {
            return in_array($item['sale_id'] ?? '', $saleIds) || in_array((int)($item['sale_id'] ?? 0), $saleIds);
        });

        $cogs = array_sum(array_map(fn($i) => ($i['unit_cost'] ?? 0) * ($i['quantity'] ?? 0), $filteredItems));
        $productsSold = array_sum(array_map(fn($i) => $i['quantity'] ?? 0, $filteredItems));

        $grossProfit = $revenue - $cogs;

        // Expenses (1 API call)
        $expenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
        ]);
        $expenses = array_filter($expenses, function ($e) use ($start, $end) {
            $created = $e['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });
        $totalExpenses = array_sum(array_map(fn($e) => $e['amount'] ?? 0, $expenses));

        $netProfit = $grossProfit - $totalExpenses;

        // Stock info (1 API call)
        $stocks = $this->supabase->query('branch_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'quantity',
        ]);
        $stockRemaining = array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $stocks));

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $totalExpenses,
            'net_profit' => $netProfit,
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
            'select' => 'sale_id,unit_cost,quantity',
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
        }

        $total = [
            'revenue' => 0, 'cogs' => 0, 'gross_profit' => 0,
            'expenses' => 0, 'net_profit' => 0, 'transaction_count' => 0,
            'products_sold' => 0, 'by_branch' => [],
        ];

        foreach ($branches as $branch) {
            $bid = $branch['id'];
            $branchSales = $salesByBranch[$bid] ?? [];
            $branchExpenses = $expensesByBranch[$bid] ?? [];

            $revenue = array_sum(array_map(fn($s) => $s['total'] ?? 0, $branchSales));

            // COGS
            $cogs = 0;
            $productsSold = 0;
            foreach ($branchSales as $sale) {
                $items = $saleItemsBySale[$sale['id']] ?? [];
                $cogs += array_sum(array_map(fn($i) => ($i['unit_cost'] ?? 0) * ($i['quantity'] ?? 0), $items));
                $productsSold += array_sum(array_map(fn($i) => $i['quantity'] ?? 0, $items));
            }

            $totalExpenses = array_sum(array_map(fn($e) => $e['amount'] ?? 0, $branchExpenses));
            $grossProfit = $revenue - $cogs;
            $netProfit = $grossProfit - $totalExpenses;

            $branchFinancials = [
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'transaction_count' => count($branchSales),
                'products_sold' => $productsSold,
                'branch_name' => $branch['name'],
            ];

            $total['by_branch'][$bid] = $branchFinancials;
            $total['revenue'] += $revenue;
            $total['cogs'] += $cogs;
            $total['gross_profit'] += $grossProfit;
            $total['expenses'] += $totalExpenses;
            $total['net_profit'] += $netProfit;
            $total['transaction_count'] += count($branchSales);
            $total['products_sold'] += $productsSold;
        }

        return $total;
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
