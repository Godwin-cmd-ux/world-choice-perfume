<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        return view('branch-admin.reports.index');
    }

    public function sales(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        // Fetch sales from Supabase
        $allSales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'select' => '*, cashier:users(id,name), items:sale_items(*, product:products(id,name))',
        ]);

        // Filter by date range
        $sales = array_filter($allSales, function ($s) use ($startDate, $endDate) {
            $created = $s['created_at'] ?? '';
            return $created >= $startDate->toIso8601String() && $created <= $endDate->toIso8601String();
        });

        $salesCollection = collect($sales)->map(function ($s) {
            if (isset($s['cashier']) && is_array($s['cashier'])) $s['cashier'] = (object) $s['cashier'];
            if (isset($s['items'])) {
                $s['items'] = collect($s['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $s;
        });

        $report = [
            'period' => ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()],
            'total_sales' => $salesCollection->sum('total'),
            'total_transactions' => $salesCollection->count(),
            'total_items_sold' => $salesCollection->sum(fn($s) => isset($s->items) ? $s->items->sum('quantity') : 0),
            'sales' => $salesCollection,
        ];

        return view('branch-admin.reports.sales', compact('report', 'startDate', 'endDate'));
    }

    public function profit(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        $financials = $this->getBranchFinancials($branchId, $startDate, $endDate);

        return view('branch-admin.reports.profit', compact('financials', 'startDate', 'endDate'));
    }

    public function expenses(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        $allExpenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
        ]);

        // Filter by date
        $filtered = array_filter($allExpenses, function ($e) use ($startDate, $endDate) {
            $created = $e['created_at'] ?? '';
            return $created >= $startDate->toIso8601String() && $created <= $endDate->toIso8601String();
        });

        // Group by category
        $byCategory = [];
        foreach ($filtered as $e) {
            $cat = $e['category'] ?? 'other';
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = ['category' => $cat, 'total' => 0, 'count' => 0];
            }
            $byCategory[$cat]['total'] += $e['amount'] ?? 0;
            $byCategory[$cat]['count']++;
        }

        return view('branch-admin.reports.expenses', ['expensesByCategory' => array_values($byCategory), 'startDate' => $startDate, 'endDate' => $endDate]);
    }

    public function stock()
    {
        $branchId = auth()->user()->branch_id;

        $stocks = $this->supabase->query('branch_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => '*, product:products(id,name,brand)',
        ]);

        $report = array_map(function ($s) {
            return [
                'product' => $s['product']['name'] ?? 'Unknown',
                'quantity' => $s['quantity'] ?? 0,
                'buying_cost' => $s['buying_cost'] ?? 0,
                'selling_price' => $s['selling_price'] ?? 0,
                'stock_value' => ($s['quantity'] ?? 0) * ($s['buying_cost'] ?? 0),
            ];
        }, $stocks);

        return view('branch-admin.reports.stock', ['report' => $report]);
    }

    public function cashierPerformance(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

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

            // Filter by date
            $filtered = array_filter($sales, function ($s) use ($startDate, $endDate) {
                $created = $s['created_at'] ?? '';
                return $created >= $startDate->toIso8601String() && $created <= $endDate->toIso8601String();
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

        return view('branch-admin.reports.cashier-performance', ['report' => $results, 'startDate' => $startDate, 'endDate' => $endDate]);
    }

    public function productPerformance(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        // Get all sale_items for this branch
        $sales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'id,created_at,items:sale_items(quantity,total, product:products(id,name))',
        ]);

        // Filter by date
        $filtered = array_filter($sales, function ($s) use ($startDate, $endDate) {
            $created = $s['created_at'] ?? '';
            return $created >= $startDate->toIso8601String() && $created <= $endDate->toIso8601String();
        });

        // Aggregate by product
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

        // Sort by revenue desc
        usort($productStats, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        return view('branch-admin.reports.product-performance', ['report' => $productStats, 'startDate' => $startDate, 'endDate' => $endDate]);
    }

    private function getBranchFinancials(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toIso8601String();
        $end = $endDate->toIso8601String();

        $sales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
        ]);
        $sales = array_filter($sales, fn($s) => ($s['created_at'] ?? '') >= $start && ($s['created_at'] ?? '') <= $end);

        $revenue = array_sum(array_map(fn($s) => $s['total'] ?? 0, $sales));

        $expenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
        ]);
        $expenses = array_filter($expenses, fn($e) => ($e['created_at'] ?? '') >= $start && ($e['created_at'] ?? '') <= $end);
        $totalExpenses = array_sum(array_map(fn($e) => $e['amount'] ?? 0, $expenses));

        // Calculate COGS from sale items
        $allSaleItems = [];
        foreach ($sales as $sale) {
            $items = $this->supabase->query('sale_items', ['sale_id' => 'eq.' . $sale['id']]);
            foreach ($items as $item) {
                $allSaleItems[] = $item;
            }
        }
        $cogs = array_sum(array_map(fn($i) => ($i['quantity'] ?? 0) * ($i['buying_cost'] ?? 0), $allSaleItems));
        $productsSold = array_sum(array_map(fn($i) => $i['quantity'] ?? 0, $allSaleItems));

        // Get stock remaining
        $stocks = $this->supabase->query('branch_stock', ['branch_id' => "eq.{$branchId}"]);
        $stockRemaining = array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $stocks));

        return [
            'revenue' => $revenue,
            'expenses' => $totalExpenses,
            'net_profit' => $revenue - $totalExpenses - $cogs,
            'transaction_count' => count($sales),
            'products_sold' => $productsSold,
            'cogs' => $cogs,
            'gross_profit' => $revenue - $cogs,
            'stock_remaining' => $stockRemaining,
        ];
    }
}
