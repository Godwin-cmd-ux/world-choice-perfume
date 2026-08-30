<?php

namespace App\Services;

use Carbon\Carbon;

class ReportService
{
    private SupabaseService $supabase;
    private FinancialService $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->supabase = new SupabaseService();
        $this->financialService = $financialService;
    }

    public function salesReport(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toIso8601String();
        $end = $endDate->toIso8601String();

        $allSales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'select' => '*, cashier:users(id,name), items:sale_items(*, product:products(id,name))',
        ]);

        $sales = array_filter($allSales, function ($s) use ($start, $end) {
            $created = $s['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });

        $totalItemsSold = 0;
        foreach ($sales as $s) {
            $totalItemsSold += array_sum(array_map(fn($i) => $i['quantity'] ?? 0, $s['items'] ?? []));
        }

        return [
            'period' => ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()],
            'total_sales' => array_sum(array_map(fn($s) => $s['total'] ?? 0, $sales)),
            'total_transactions' => count($sales),
            'total_items_sold' => $totalItemsSold,
            'sales' => collect($sales),
        ];
    }

    public function cashierPerformanceReport(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toIso8601String();
        $end = $endDate->toIso8601String();

        $cashiers = $this->supabase->query('users', [
            'branch_id' => "eq.{$branchId}",
            'role' => 'eq.cashier',
            'status' => 'eq.approved',
            'select' => 'id,name',
        ]);

        $results = [];
        foreach ($cashiers as $cashier) {
            $sales = $this->supabase->query('sales', [
                'cashier_id' => "eq.{$cashier['id']}",
                'select' => '*, items:sale_items(quantity)',
            ]);

            $filtered = array_filter($sales, function ($s) use ($start, $end) {
                $created = $s['created_at'] ?? '';
                return $created >= $start && $created <= $end;
            });

            $totalItems = array_sum(array_map(function ($s) {
                return array_sum(array_map(fn($i) => $i['quantity'] ?? 0, $s['items'] ?? []));
            }, $filtered));

            $results[] = [
                'cashier_id' => $cashier['id'],
                'cashier_name' => $cashier['name'],
                'total_sales' => array_sum(array_map(fn($s) => $s['total'] ?? 0, $filtered)),
                'transaction_count' => count($filtered),
                'items_sold' => $totalItems,
            ];
        }

        return $results;
    }

    public function productPerformanceReport(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toIso8601String();
        $end = $endDate->toIso8601String();

        $sales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'id,created_at,items:sale_items(quantity,total, product:products(id,name))',
        ]);

        $filtered = array_filter($sales, function ($s) use ($start, $end) {
            $created = $s['created_at'] ?? '';
            return $created >= $start && $created <= $end;
        });

        $productStats = [];
        foreach ($filtered as $sale) {
            foreach ($sale['items'] ?? [] as $item) {
                $pid = $item['product']['id'] ?? 'unknown';
                $pname = $item['product']['name'] ?? 'Unknown';
                if (!isset($productStats[$pid])) {
                    $productStats[$pid] = ['id' => $pid, 'name' => $pname, 'total_sold' => 0, 'total_revenue' => 0];
                }
                $productStats[$pid]['total_sold'] += $item['quantity'] ?? 0;
                $productStats[$pid]['total_revenue'] += $item['total'] ?? 0;
            }
        }

        usort($productStats, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        return $productStats;
    }

    public function stockReport(int $branchId): array
    {
        $stocks = $this->supabase->query('branch_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => '*, product:products(id,name)',
        ]);

        return array_map(function ($stock) {
            return [
                'product' => $stock['product']['name'] ?? 'Unknown',
                'quantity' => $stock['quantity'] ?? 0,
                'buying_cost' => $stock['buying_cost'] ?? 0,
                'selling_price' => $stock['selling_price'] ?? 0,
                'stock_value' => ($stock['quantity'] ?? 0) * ($stock['buying_cost'] ?? 0),
            ];
        }, $stocks);
    }
}
