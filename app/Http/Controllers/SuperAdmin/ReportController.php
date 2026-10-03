<?php

namespace App\Http\Controllers\SuperAdmin;

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
        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        return view('super-admin.reports.index', ['branches' => $branches]);
    }

    public function sales(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::now()->setTimezone('Africa/Dar_es_Salaam');

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $params = [
            'select' => '*, cashier:users(id,name), branch:branches(id,name), items:sale_items(quantity,total)',
            'order' => 'created_at.desc',
            'limit' => 500,
        ];

        if ($request->branch_id) {
            $params['branch_id'] = "eq.{$request->branch_id}";
        }

        $allSales = $this->supabase->query('sales', $params);

        // Filter to the selected day (timezone-aware)
        $dayStartIso = $dayStart->toIso8601String();
        $dayEndIso = $dayEnd->toIso8601String();
        $filtered = array_filter($allSales, function ($s) use ($dayStartIso, $dayEndIso) {
            $created = $s['created_at'] ?? '';
            return $created >= $dayStartIso && $created <= $dayEndIso;
        });

        // Collect all branch IDs we need to look up names for
        $allBranchIds = array_unique(array_filter(array_map(fn($s) => $s['branch_id'] ?? null, $filtered)));
        $branchMap = [];
        if (!empty($allBranchIds)) {
            $branchRows = $this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $allBranchIds) . ')',
            ]);
            foreach ($branchRows as $b) $branchMap[$b['id']] = $b['name'];
        }

        // Normalise to objects
        $salesCollection = collect($filtered)->map(function ($s) use ($branchMap) {
            if (isset($s['cashier']) && is_array($s['cashier'])) $s['cashier'] = (object) $s['cashier'];
            $s['branch_name'] = $branchMap[$s['branch_id'] ?? null] ?? ($s['branch']['name'] ?? 'Unknown');
            $items = collect($s['items'] ?? []);
            $s['items_count'] = $items->sum('quantity');
            return (object) $s;
        });

        // Group by branch
        $branchGroups = $salesCollection->groupBy('branch_name')->map(function ($sales, $branchName) {
            return [
                'branch_name' => $branchName,
                'total_sales' => $sales->sum('total'),
                'transactions' => $sales->count(),
                'items_sold' => $sales->sum('items_count'),
                'sales' => $sales,
            ];
        })->sortBy('branch_name')->values();

        $branches = collect($this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']))->map(fn($b) => (object) $b);

        return view('super-admin.reports.sales', [
            'date' => $date,
            'branchGroups' => $branchGroups,
            'totalSales' => $salesCollection->sum('total'),
            'totalTransactions' => $salesCollection->count(),
            'totalItems' => $salesCollection->sum('items_count'),
            'branches' => $branches,
        ]);
    }

    public function generateSalesReport(Request $request)
    {
        [$pdf, $filename] = $this->buildSalesReportPdf($request);

        return $pdf->download($filename);
    }

    /**
     * Open a page that loads the generated sales PDF and pops the browser's
     * print dialog straight away. Pairs with generateSalesReport(), which
     * downloads the same file.
     */
    public function printSalesReport(Request $request)
    {
        return view('super-admin.reports.sales-print', [
            'pdfUrl' => route('super-admin.reports.stream-sales-report', $request->only(['date', 'branch_id'])),
            'title' => 'Print Sales Report',
        ]);
    }

    /**
     * Serve the generated sales PDF inline so the print page's embedded frame
     * can display and print it. Loaded by the browser, not linked in the UI.
     */
    public function streamSalesReport(Request $request)
    {
        [$pdf, $filename] = $this->buildSalesReportPdf($request);

        return $pdf->stream($filename);
    }

    /**
     * Build the sales report PDF and its filename for the given date / branch
     * filters. Shared by the download and print actions.
     *
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    private function buildSalesReportPdf(Request $request): array
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::now()->setTimezone('Africa/Dar_es_Salaam');

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $params = [
            'select' => '*, cashier:users(id,name), items:sale_items(quantity,total)',
            'order' => 'created_at.desc',
            'limit' => 500,
        ];

        if ($request->branch_id) {
            $params['branch_id'] = "eq.{$request->branch_id}";
        }

        $allSales = $this->supabase->query('sales', $params);

        $dayStartIso = $dayStart->toIso8601String();
        $dayEndIso = $dayEnd->toIso8601String();
        $filtered = array_filter($allSales, function ($s) use ($dayStartIso, $dayEndIso) {
            $created = $s['created_at'] ?? '';
            return $created >= $dayStartIso && $created <= $dayEndIso;
        });

        // Branch names
        $allBranchIds = array_unique(array_filter(array_map(fn($s) => $s['branch_id'] ?? null, $filtered)));
        $branchMap = [];
        if (!empty($allBranchIds)) {
            $branchRows = $this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $allBranchIds) . ')',
            ]);
            foreach ($branchRows as $b) $branchMap[$b['id']] = $b['name'];
        }

        $salesCollection = collect($filtered)->map(function ($s) use ($branchMap) {
            if (isset($s['cashier']) && is_array($s['cashier'])) $s['cashier'] = (object) $s['cashier'];
            $s['branch_name'] = $branchMap[$s['branch_id'] ?? null] ?? 'Unknown';
            $items = collect($s['items'] ?? []);
            $s['items_count'] = $items->sum('quantity');
            return (object) $s;
        });

        $branchGroups = $salesCollection->groupBy('branch_name')->map(function ($sales, $branchName) {
            return [
                'branch_name' => $branchName,
                'total_sales' => $sales->sum('total'),
                'transactions' => $sales->count(),
                'items_sold' => $sales->sum('items_count'),
                'sales' => $sales,
            ];
        })->sortBy('branch_name')->values();

        $totalSales = $salesCollection->sum('total');
        $totalTransactions = $salesCollection->count();
        $totalItems = $salesCollection->sum('items_count');

        $html = view('super-admin.reports.sales-pdf', [
            'date' => $date,
            'branchGroups' => $branchGroups,
            'totalSales' => $totalSales,
            'totalTransactions' => $totalTransactions,
            'totalItems' => $totalItems,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        $filename = 'sales-report-' . $date->timezone('Africa/Dar_es_Salaam')->format('Y-m-d') . '.pdf';

        return [$pdf, $filename];
    }

    public function expenses(Request $request)
    {
        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 200,
        ];

        if ($request->branch_id) {
            $params['branch_id'] = "eq.{$request->branch_id}";
        }

        $allExpenses = $this->supabase->query('expenses', $params);

        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        $filtered = array_filter($allExpenses, function ($e) use ($startDate, $endDate) {
            $created = $e['created_at'] ?? '';
            return $created >= $startDate->toIso8601String() && $created <= $endDate->toIso8601String();
        });

        $byCategory = [];
        foreach ($filtered as $e) {
            $cat = $e['category'] ?? 'other';
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = ['category' => $cat, 'total' => 0, 'count' => 0];
            }
            $byCategory[$cat]['total'] += $e['amount'] ?? 0;
            $byCategory[$cat]['count']++;
        }

        $branches = collect($this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']))->map(fn($b) => (object) $b);

        return view('super-admin.reports.expenses', ['expensesByCategory' => array_values($byCategory), 'startDate' => $startDate, 'endDate' => $endDate, 'branches' => $branches]);
    }

    public function stock(Request $request)
    {
        $params = [
            'select' => '*, product:products(id,name,brand), branch:branches(id,name)',
            'order' => 'created_at.desc',
        ];

        if ($request->branch_id) {
            $params['branch_id'] = "eq.{$request->branch_id}";
        }

        $stocks = $this->supabase->query('branch_stock', $params);

        $report = array_map(function ($s) {
            return [
                'product' => $s['product']['name'] ?? 'Unknown',
                'branch' => $s['branch']['name'] ?? 'Unknown',
                'quantity' => $s['quantity'] ?? 0,
                'selling_price' => $s['selling_price'] ?? 0,
                'stock_value' => ($s['quantity'] ?? 0) * ($s['selling_price'] ?? 0),
            ];
        }, $stocks);

        $branches = collect($this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']))->map(fn($b) => (object) $b);

        return view('super-admin.reports.stock', ['report' => $report, 'branches' => $branches]);
    }

    public function staffPerformance(Request $request)
    {
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        // Get all staff (cashiers + stock managers + branch admins)
        $params = [
            'select' => 'id,name,role,branch_id,branch:branches(id,name)',
            'role' => 'in.(cashier,stock_manager,branch_admin)',
            'status' => 'in.(active,approved)',
            'order' => 'name.asc',
        ];

        if ($request->branch_id) {
            $params['branch_id'] = "eq.{$request->branch_id}";
        }

        $staff = $this->supabase->query('users', $params);
        $results = [];

        foreach ($staff as $member) {
            $sales = $this->supabase->query('sales', [
                'cashier_id' => "eq.{$member['id']}",
                'select' => '*, items:sale_items(quantity,total)',
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
                'user_id' => $member['id'],
                'user_name' => $member['name'],
                'role' => $member['role'],
                'branch_name' => $member['branch']['name'] ?? '—',
                'total_sales' => array_sum(array_map(fn($s) => $s['total'] ?? 0, $filtered)),
                'transaction_count' => count($filtered),
                'items_sold' => $totalItems,
            ];
        }

        $branches = collect($this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']))->map(fn($b) => (object) $b);

        return view('super-admin.reports.staff-performance', ['report' => $results, 'startDate' => $startDate, 'endDate' => $endDate, 'branches' => $branches]);
    }

    public function productPerformance(Request $request)
    {
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        $params = [
            'select' => 'id,created_at,branch_id,items:sale_items(quantity,total, product:products(id,name))',
            'order' => 'created_at.desc',
        ];

        if ($request->branch_id) {
            $params['branch_id'] = "eq.{$request->branch_id}";
        }

        $sales = $this->supabase->query('sales', $params);

        $filtered = array_filter($sales, function ($s) use ($startDate, $endDate) {
            $created = $s['created_at'] ?? '';
            return $created >= $startDate->toIso8601String() && $created <= $endDate->toIso8601String();
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

        // Units still on hand per product, so each product's sold quantity can
        // be expressed as a share of everything that was available. Stock is a
        // current snapshot, so it is not limited to the selected date range.
        $stockParams = ['select' => 'product_id,quantity'];
        if ($request->branch_id) {
            $stockParams['branch_id'] = "eq.{$request->branch_id}";
        }

        $stockByProduct = [];
        foreach ($this->supabase->query('branch_stock', $stockParams) as $row) {
            $pid = $row['product_id'] ?? null;
            if ($pid === null) {
                continue;
            }
            $stockByProduct[$pid] = ($stockByProduct[$pid] ?? 0) + ($row['quantity'] ?? 0);
        }

        // Revenue across every product in scope, the denominator for the
        // contribution share below.
        $totalRevenue = array_sum(array_column($productStats, 'total_revenue'));

        foreach ($productStats as &$stat) {
            $remaining = $stockByProduct[$stat['id']] ?? 0;
            $available = $stat['total_sold'] + $remaining;

            $stat['remaining'] = $remaining;
            $stat['available'] = $available;

            // Sell-through: of every unit that was available, how many sold.
            $stat['sell_through'] = $available > 0
                ? round($stat['total_sold'] / $available * 100, 1)
                : 0.0;

            // Share of the overall revenue this product brought in.
            $stat['contribution'] = $totalRevenue > 0
                ? round($stat['total_revenue'] / $totalRevenue * 100, 1)
                : 0.0;
        }
        unset($stat);

        usort($productStats, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        $branches = collect($this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']))->map(fn($b) => (object) $b);

        return view('super-admin.reports.product-performance', ['report' => $productStats, 'startDate' => $startDate, 'endDate' => $endDate, 'branches' => $branches, 'totalRevenue' => $totalRevenue]);
    }
}
