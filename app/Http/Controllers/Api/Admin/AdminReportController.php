<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * JSON twin of SuperAdmin\ReportController (sales, expenses, stock, staff
 * performance, product performance) plus ReturnedStockController::index.
 *
 * The website's PDF/print buttons render the same figures through DomPDF;
 * the app receives the same grouped/totalled data and renders it natively —
 * identical numbers, phone-sized presentation.
 */
class AdminReportController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /** Report hub data: just the branch list, like the website index. */
    public function index()
    {
        return response()->json([
            'branches' => $this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']),
        ]);
    }

    public function sales(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::now()->setTimezone('Africa/Dar_es_Salaam');
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $salesCollection = $this->collectSales($request, $dayStart, $dayEnd);

        $branchGroups = $salesCollection->groupBy('branch_name')->map(function ($sales, $branchName) {
            return [
                'branch_name' => $branchName,
                'total_sales' => $sales->sum('total'),
                'transactions' => $sales->count(),
                'items_sold' => $sales->sum('items_count'),
                'sales' => $sales->values(),
            ];
        })->sortBy('branch_name')->values();

        return response()->json([
            'date' => $date->timezone('Africa/Dar_es_Salaam')->format('Y-m-d'),
            'branch_groups' => $branchGroups,
            'total_sales' => $salesCollection->sum('total'),
            'total_transactions' => $salesCollection->count(),
            'total_items' => $salesCollection->sum('items_count'),
            'branches' => $this->branchList(),
        ]);
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
            if (! isset($byCategory[$cat])) {
                $byCategory[$cat] = ['category' => $cat, 'total' => 0, 'count' => 0];
            }
            $byCategory[$cat]['total'] += $e['amount'] ?? 0;
            $byCategory[$cat]['count']++;
        }

        return response()->json([
            'expenses_by_category' => array_values($byCategory),
            'total' => array_sum(array_column($byCategory, 'total')),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'branches' => $this->branchList(),
        ]);
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
                'brand' => $s['product']['brand'] ?? null,
                'branch' => $s['branch']['name'] ?? 'Unknown',
                'quantity' => $s['quantity'] ?? 0,
                'selling_price' => $s['selling_price'] ?? 0,
                'stock_value' => ($s['quantity'] ?? 0) * ($s['selling_price'] ?? 0),
            ];
        }, $stocks);

        return response()->json([
            'report' => $report,
            'total_value' => array_sum(array_column($report, 'stock_value')),
            'total_units' => array_sum(array_column($report, 'quantity')),
            'branches' => $this->branchList(),
        ]);
    }

    public function staffPerformance(Request $request)
    {
        $startDate = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $endDate = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

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

            $filtered = array_filter($sales, function ($s) use ($startDate, $endDate) {
                $created = $s['created_at'] ?? '';

                return $created >= $startDate->toIso8601String() && $created <= $endDate->toIso8601String();
            });

            $totalItems = array_sum(array_map(function ($s) {
                return array_sum(array_map(fn ($i) => $i['quantity'] ?? 0, $s['items'] ?? []));
            }, $filtered));

            $results[] = [
                'user_id' => $member['id'],
                'user_name' => $member['name'],
                'role' => $member['role'],
                'branch_name' => $member['branch']['name'] ?? '—',
                'total_sales' => array_sum(array_map(fn ($s) => $s['total'] ?? 0, $filtered)),
                'transaction_count' => count($filtered),
                'items_sold' => $totalItems,
            ];
        }

        usort($results, fn ($a, $b) => $b['total_sales'] <=> $a['total_sales']);

        return response()->json([
            'report' => $results,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'branches' => $this->branchList(),
        ]);
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
                if (! isset($productStats[$pid])) {
                    $productStats[$pid] = ['id' => $pid, 'name' => $pname, 'total_sold' => 0, 'total_revenue' => 0];
                }
                $productStats[$pid]['total_sold'] += $item['quantity'] ?? 0;
                $productStats[$pid]['total_revenue'] += $item['total'] ?? 0;
            }
        }

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

        $totalRevenue = array_sum(array_column($productStats, 'total_revenue'));

        foreach ($productStats as &$stat) {
            $remaining = $stockByProduct[$stat['id']] ?? 0;
            $available = $stat['total_sold'] + $remaining;

            $stat['remaining'] = $remaining;
            $stat['available'] = $available;
            $stat['sell_through'] = $available > 0 ? round($stat['total_sold'] / $available * 100, 1) : 0.0;
            $stat['contribution'] = $totalRevenue > 0 ? round($stat['total_revenue'] / $totalRevenue * 100, 1) : 0.0;
        }
        unset($stat);

        usort($productStats, fn ($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        return response()->json([
            'report' => $productStats,
            'total_revenue' => $totalRevenue,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'branches' => $this->branchList(),
        ]);
    }

    /** Returned Stock module — mirrors ReturnedStockController::buildRows. */
    public function returnedStock(Request $request)
    {
        $hasDamageColumns = $this->supabase->tableHasColumn('stock_transfer_items', 'damage_type');
        $damageSelect = $hasDamageColumns
            ? ',loss_reason,damage_type,damage_reason,damage_reported_by,damage_reported_at'
            : '';

        $transferParams = [
            'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,status,officer_name,officer_phone,officer_id,created_at',
            'order' => 'created_at.desc',
            'limit' => 200,
        ];

        if ((int) $request->query('branch_id') > 0) {
            $transferParams['from_branch_id'] = 'eq.'.((int) $request->query('branch_id'));
        }

        $transfers = $this->supabase->query('stock_transfers', $transferParams);

        $transferMap = [];
        foreach ($transfers as $t) {
            $transferMap[(int) $t['id']] = $t;
        }
        $transferIds = array_keys($transferMap);

        $itemParams = [
            'select' => 'id,transfer_id,stock_type,item_index,product_id,name,volume,variant,type,color,quantity,unit_cost,unit_price,variety_unit_price,category,supplier,status,return_reason,return_status,returned_by,returned_at,resent_transfer_id'.$damageSelect,
            'transfer_id' => 'in.('.implode(',', $transferIds).')',
            'status' => 'eq.returned',
            'order' => 'returned_at.desc',
            'limit' => 300,
        ];

        $damageFilter = (string) $request->query('damage_type');
        if (in_array($damageFilter, ['lost', 'broken'], true) && $hasDamageColumns) {
            $itemParams['damage_type'] = 'eq.'.$damageFilter;
            $itemParams['return_status'] = 'eq.reported';
        }

        $items = $transferIds !== [] ? $this->supabase->query('stock_transfer_items', $itemParams) : [];

        $dateFrom = (string) $request->query('date_from');
        $dateTo = (string) $request->query('date_to');
        if ($dateFrom !== '') {
            $items = array_values(array_filter($items, fn ($it) => substr((string) ($it['returned_at'] ?? ''), 0, 10) >= $dateFrom));
        }
        if ($dateTo !== '') {
            $items = array_values(array_filter($items, fn ($it) => substr((string) ($it['returned_at'] ?? ''), 0, 10) <= $dateTo));
        }

        $branchIds = [];
        $userIds = [];
        $productIds = [];
        foreach ($items as $it) {
            $t = $transferMap[(int) ($it['transfer_id'] ?? 0)] ?? null;
            if (! $t) {
                continue;
            }
            $branchIds[] = (int) ($t['from_branch_id'] ?? 0);
            $branchIds[] = (int) ($t['to_branch_id'] ?? 0);
            foreach (['returned_by', 'damage_reported_by'] as $key) {
                if (! empty($it[$key])) {
                    $userIds[] = (int) $it[$key];
                }
            }
            if ((int) ($it['product_id'] ?? 0) > 0) {
                $productIds[] = (int) $it['product_id'];
            }
        }

        $branchNames = $this->nameMap('branches', array_values(array_unique(array_filter($branchIds))));
        $userNames = $this->nameMap('users', array_values(array_unique(array_filter($userIds))));
        $productNames = $this->nameMap('products', $productIds, 'name');

        $rows = [];
        foreach ($items as $it) {
            $t = $transferMap[(int) ($it['transfer_id'] ?? 0)] ?? null;
            if (! $t) {
                continue;
            }

            $rows[] = [
                'item' => $it,
                'item_label' => $this->itemLabel($it, $productNames),
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type_label' => $this->typeLabel((string) ($t['stock_type'] ?? '')),
                'from_branch_name' => $branchNames[(int) ($t['from_branch_id'] ?? 0)] ?? ('Branch #'.($t['from_branch_id'] ?? '?')),
                'to_branch_name' => $branchNames[(int) ($t['to_branch_id'] ?? 0)] ?? ('Branch #'.($t['to_branch_id'] ?? '?')),
                'officer_name' => $t['officer_name'] ?? null,
                'officer_phone' => $t['officer_phone'] ?? null,
                'return_reason' => $it['return_reason'] ?? null,
                'return_status' => $it['return_status'] ?? 'pending',
                'damage_type' => $it['damage_type'] ?? null,
                'damage_reason' => $it['damage_reason'] ?? null,
                'damage_reported_by_name' => $userNames[(int) ($it['damage_reported_by'] ?? 0)] ?? null,
                'damage_reported_at' => $it['damage_reported_at'] ?? null,
                'returned_at' => $it['returned_at'] ?? null,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($b['returned_at'] ?? ''), (string) ($a['returned_at'] ?? '')));

        $reported = array_values(array_filter($rows, fn ($r) => ($r['return_status'] ?? '') === 'reported' || ! empty($r['damage_reported_at'])));

        $branches = [];
        foreach ($this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']) as $b) {
            $branches[(int) $b['id']] = $b['name'] ?? ('Branch #'.$b['id']);
        }

        return response()->json([
            'rows' => $rows,
            'lost_count' => count(array_filter($reported, fn ($r) => ($r['damage_type'] ?? '') === 'lost')),
            'broken_count' => count(array_filter($reported, fn ($r) => ($r['damage_type'] ?? '') === 'broken')),
            'branches' => $branches,
            'filters' => [
                'damage_type' => $damageFilter,
                'branch_id' => (int) $request->query('branch_id'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'has_damage_columns' => $hasDamageColumns,
        ]);
    }

    /* ---------------- helpers (shared with the website's shapes) --------------- */

    private function collectSales(Request $request, Carbon $dayStart, Carbon $dayEnd)
    {
        $params = [
            'select' => '*, cashier:users(id,name), branch:branches(id,name), items:sale_items(quantity,total)',
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

        $allBranchIds = array_unique(array_filter(array_map(fn ($s) => $s['branch_id'] ?? null, $filtered)));
        $branchMap = [];
        if (! empty($allBranchIds)) {
            foreach ($this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.('.implode(',', $allBranchIds).')',
            ]) as $b) {
                $branchMap[$b['id']] = $b['name'];
            }
        }

        return collect($filtered)->map(function ($s) use ($branchMap) {
            $s['branch_name'] = $branchMap[$s['branch_id'] ?? null] ?? ($s['branch']['name'] ?? 'Unknown');
            $items = collect($s['items'] ?? []);
            $s['items_count'] = $items->sum('quantity');

            return (object) $s;
        });
    }

    private function branchList(): array
    {
        return $this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']);
    }

    private function nameMap(string $table, array $ids, string $nameColumn = 'name'): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        $map = [];
        if ($ids === []) {
            return $map;
        }

        $rows = $this->supabase->query($table, [
            'select' => "id,{$nameColumn}",
            'id' => 'in.('.implode(',', $ids).')',
            'limit' => 200,
        ]);
        foreach ($rows as $r) {
            $map[(int) $r['id']] = $r[$nameColumn] ?? null;
        }

        return $map;
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'product' => 'Product Stock',
            'bottle' => 'Bottle Stock',
            'oil_fragrance' => 'Oil Fragrance',
            'bottle_accessories' => 'Bottle Accessories',
            default => 'Stock',
        };
    }

    private function itemLabel(array $item, array $productNames): string
    {
        $type = (string) ($item['stock_type'] ?? '');

        if ($type === 'product') {
            $id = (int) ($item['product_id'] ?? 0);
            $name = $productNames[$id] ?? ('Product #'.$id);
            $volume = (int) ($item['volume'] ?? 0);
            $variant = (string) ($item['variant'] ?? '');

            return $name.($volume > 0 ? ' — '.$volume.'ml'.($variant !== '' ? ' '.$variant : '') : '');
        }

        if ($type === 'bottle') {
            $volume = (int) ($item['volume'] ?? 0);
            $variant = (string) ($item['variant'] ?? '');

            return ($volume > 0 ? $volume.'ml' : 'Bottle').($variant !== '' ? ' — '.$variant : '');
        }

        if ($type === 'oil_fragrance') {
            return (string) ($item['name'] ?? '').(($item['volume'] ?? '') !== '' ? ' ('.$item['volume'].'ml)' : '');
        }

        if ($type === 'bottle_accessories') {
            return ucfirst(str_replace('_', ' ', (string) ($item['type'] ?? ''))).' — '.ucfirst((string) ($item['color'] ?? ''));
        }

        return 'Item';
    }
}
