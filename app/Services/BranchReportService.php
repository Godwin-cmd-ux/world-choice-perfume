<?php

namespace App\Services;

use App\Support\BusinessDay;
use Carbon\Carbon;

/**
 * The report data behind a single branch's report module.
 *
 * The Super Admin reports run across every branch and take a branch filter;
 * the Branch Admin and Cashier modules see the same six reports but are always
 * pinned to one branch. This service produces the same shapes as the Super
 * Admin controller so the two modules stay in step.
 */
class BranchReportService
{
    private SupabaseService $supabase;
    private ProfitReportService $profitReports;

    public function __construct(
        ?SupabaseService $supabase = null,
        ?ProfitReportService $profitReports = null
    ) {
        $this->supabase = $supabase ?? new SupabaseService();
        $this->profitReports = $profitReports ?? new ProfitReportService($this->supabase);
    }

    public function branchName(int $branchId): string
    {
        if (!$branchId) {
            return 'Branch';
        }

        $rows = $this->supabase->query('branches', [
            'select' => 'id,name',
            'id' => "eq.{$branchId}",
            'limit' => 1,
        ]);

        return $rows[0]['name'] ?? 'Branch';
    }

    /**
     * Daily sales for the branch, grouped the same way the Super Admin sales
     * report groups them (one section per branch — just one here).
     */
    public function sales(int $branchId, Carbon $date): array
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $sales = $this->supabase->query('sales', [
            'select' => '*, cashier:users(id,name), items:sale_items(quantity,total)',
            'branch_id' => "eq.{$branchId}",
            'created_at' => BusinessDay::rangeFilter($dayStart, $dayEnd),
            'order' => 'created_at.desc',
            'limit' => 500,
        ]);

        $branchName = $this->branchName($branchId);

        $salesCollection = collect($sales)->map(function ($s) use ($branchName) {
            if (isset($s['cashier']) && is_array($s['cashier'])) {
                $s['cashier'] = (object) $s['cashier'];
            }
            $s['branch_name'] = $branchName;
            $s['items_count'] = collect($s['items'] ?? [])->sum('quantity');

            return (object) $s;
        });

        $branchGroups = $salesCollection->isEmpty()
            ? collect()
            : collect([[
                'branch_name' => $branchName,
                'total_sales' => $salesCollection->sum('total'),
                'transactions' => $salesCollection->count(),
                'items_sold' => $salesCollection->sum('items_count'),
                'sales' => $salesCollection,
            ]]);

        return [
            'date' => $date,
            'branchName' => $branchName,
            'branchGroups' => $branchGroups,
            'totalSales' => $salesCollection->sum('total'),
            'totalTransactions' => $salesCollection->count(),
            'totalItems' => $salesCollection->sum('items_count'),
        ];
    }

    /**
     * Expense totals for the branch grouped by category.
     */
    public function expenses(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $expenses = $this->supabase->query('expenses', [
            'select' => 'id,amount,category,created_at',
            'branch_id' => "eq.{$branchId}",
            'created_at' => BusinessDay::rangeFilter($startDate, $endDate),
            'order' => 'created_at.desc',
            'limit' => 200,
        ]);

        $byCategory = [];
        foreach ($expenses as $expense) {
            $category = $expense['category'] ?? 'other';
            if (!isset($byCategory[$category])) {
                $byCategory[$category] = ['category' => $category, 'total' => 0, 'count' => 0];
            }
            $byCategory[$category]['total'] += $expense['amount'] ?? 0;
            $byCategory[$category]['count']++;
        }

        return [
            'expensesByCategory' => array_values($byCategory),
            'totalExpenses' => array_sum(array_column($byCategory, 'total')),
            'transactions' => count($expenses),
        ];
    }

    /**
     * Current stock levels for the branch, in the same row shape the Super
     * Admin stock report uses.
     */
    public function stock(int $branchId): array
    {
        $stocks = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(id,name,brand), branch:branches(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
        ]);

        return array_map(function ($s) {
            return [
                'product' => $s['product']['name'] ?? 'Unknown',
                'branch' => $s['branch']['name'] ?? 'Unknown',
                'quantity' => $s['quantity'] ?? 0,
                'selling_price' => $s['selling_price'] ?? 0,
                'stock_value' => ($s['quantity'] ?? 0) * ($s['selling_price'] ?? 0),
            ];
        }, $stocks);
    }

    /**
     * Sales performance for the branch's staff members.
     */
    public function staffPerformance(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $staff = $this->supabase->query('users', [
            'select' => 'id,name,role,branch_id,branch:branches(id,name)',
            'role' => 'in.(cashier,stock_manager,branch_admin)',
            'status' => 'in.(active,approved)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'name.asc',
        ]);

        $results = [];

        foreach ($staff as $member) {
            $sales = $this->supabase->query('sales', [
                'cashier_id' => "eq.{$member['id']}",
                'select' => '*, items:sale_items(quantity,total)',
                'created_at' => BusinessDay::rangeFilter($startDate, $endDate),
            ]);

            $totalItems = array_sum(array_map(function ($sale) {
                return array_sum(array_map(fn ($item) => $item['quantity'] ?? 0, $sale['items'] ?? []));
            }, $sales));

            $results[] = [
                'user_id' => $member['id'],
                'user_name' => $member['name'],
                'role' => $member['role'],
                'branch_name' => $member['branch']['name'] ?? $this->branchName($branchId),
                'total_sales' => array_sum(array_map(fn ($sale) => $sale['total'] ?? 0, $sales)),
                'transaction_count' => count($sales),
                'items_sold' => $totalItems,
            ];
        }

        return $results;
    }

    /**
     * Product sell-through and revenue contribution for the branch.
     */
    public function productPerformance(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $sales = $this->supabase->query('sales', [
            'select' => 'id,created_at,branch_id,items:sale_items(quantity,total, product:products(id,name))',
            'branch_id' => "eq.{$branchId}",
            'created_at' => BusinessDay::rangeFilter($startDate, $endDate),
            'order' => 'created_at.desc',
        ]);

        $productStats = [];
        foreach ($sales as $sale) {
            foreach ($sale['items'] ?? [] as $item) {
                $productId = $item['product']['id'] ?? 'unknown';
                $productName = $item['product']['name'] ?? 'Unknown';
                if (!isset($productStats[$productId])) {
                    $productStats[$productId] = [
                        'id' => $productId,
                        'name' => $productName,
                        'total_sold' => 0,
                        'total_revenue' => 0,
                    ];
                }
                $productStats[$productId]['total_sold'] += $item['quantity'] ?? 0;
                $productStats[$productId]['total_revenue'] += $item['total'] ?? 0;
            }
        }

        // Units still on hand per product, so each product's sold quantity can
        // be expressed as a share of everything that was available. Stock is a
        // current snapshot, so it is not limited to the selected date range.
        $stockByProduct = [];
        foreach ($this->supabase->query('branch_stock', [
            'select' => 'product_id,quantity',
            'branch_id' => "eq.{$branchId}",
        ]) as $row) {
            $productId = $row['product_id'] ?? null;
            if ($productId === null) {
                continue;
            }
            $stockByProduct[$productId] = ($stockByProduct[$productId] ?? 0) + ($row['quantity'] ?? 0);
        }

        $totalRevenue = array_sum(array_column($productStats, 'total_revenue'));

        foreach ($productStats as &$stat) {
            $remaining = $stockByProduct[$stat['id']] ?? 0;
            $available = $stat['total_sold'] + $remaining;

            $stat['remaining'] = $remaining;
            $stat['available'] = $available;
            $stat['sell_through'] = $available > 0
                ? round($stat['total_sold'] / $available * 100, 1)
                : 0.0;
            $stat['contribution'] = $totalRevenue > 0
                ? round($stat['total_revenue'] / $totalRevenue * 100, 1)
                : 0.0;
        }
        unset($stat);

        usort($productStats, fn ($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        return [
            'report' => $productStats,
            'totalRevenue' => $totalRevenue,
        ];
    }

    /**
     * Realised profit (sales − cost of goods sold − expenses) plus the profit
     * sitting in the branch's current stock.
     */
    public function profit(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $summary = $this->profitReports->salesProfit($branchId, $startDate, $endDate);
        $stock = $this->profitReports->stockProfit($branchId);

        return [
            'summary' => $summary,
            'rows' => $stock['rows'],
            'stockSummary' => $stock['summary'],
        ];
    }
}
