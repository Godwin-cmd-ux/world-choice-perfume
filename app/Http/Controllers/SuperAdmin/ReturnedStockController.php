<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ReturnedStockController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Dedicated Returned Stock module for the Super Admin: every rejected /
     * returned transfer item, the mandatory lost / broken reports filed by
     * the Kinondoni stock manager, and the transfer officer attached to each
     * report so physical action can be taken.
     */
    public function index(Request $request)
    {
        return view('super-admin.returned-stock.index', $this->buildRows($request));
    }

    /**
     * Printable report of the lost / broken items with their reasons and the
     * registered transfer officers, for the Super Admin to act on physically.
     */
    public function printableReport(Request $request)
    {
        $data = $this->buildRows($request, 500);

        return view('super-admin.returned-stock.report', $data);
    }

    private function buildRows(Request $request, int $limit = 300): array
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

        // Optional branch filter straight from the query.
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
            'limit' => $limit,
        ];

        // Optional damage-type filter: only items with a filed report.
        $damageFilter = (string) $request->query('damage_type');
        if (in_array($damageFilter, ['lost', 'broken'], true) && $hasDamageColumns) {
            $itemParams['damage_type'] = 'eq.'.$damageFilter;
            $itemParams['return_status'] = 'eq.reported';
        }

        $items = $transferIds !== [] ? $this->supabase->query('stock_transfer_items', $itemParams) : [];

        // Optional date-range filter (on the return date).
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
        $productNames = [];
        $productIds = array_values(array_unique(array_filter($productIds)));
        if ($productIds !== []) {
            foreach ($this->nameMap('products', $productIds, 'name') as $pid => $pname) {
                $productNames[(int) $pid] = ['name' => $pname];
            }
        }

        $rows = [];
        foreach ($items as $it) {
            $t = $transferMap[(int) ($it['transfer_id'] ?? 0)] ?? null;
            if (! $t) {
                continue;
            }

            $rows[] = (object) [
                'item' => (object) $it,
                'item_label' => $this->itemLabel($it, $productNames),
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type_label' => $this->typeLabel((string) ($t['stock_type'] ?? '')),
                'from_branch_name' => $branchNames[(int) ($t['from_branch_id'] ?? 0)] ?? ('Branch #'.($t['from_branch_id'] ?? '?')),
                'to_branch_name' => $branchNames[(int) ($t['to_branch_id'] ?? 0)] ?? ('Branch #'.($t['to_branch_id'] ?? '?')),
                'officer_name' => $t['officer_name'] ?? null,
                'officer_phone' => $t['officer_phone'] ?? null,
                'officer_id' => $t['officer_id'] ?? null,
                'return_reason' => $it['return_reason'] ?? null,
                'return_status' => $it['return_status'] ?? 'pending',
                'damage_type' => $it['damage_type'] ?? null,
                'damage_reason' => $it['damage_reason'] ?? null,
                'damage_reported_by_name' => $userNames[(int) ($it['damage_reported_by'] ?? 0)] ?? null,
                'damage_reported_at' => $it['damage_reported_at'] ?? null,
                'returned_at' => $it['returned_at'] ?? null,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($b->returned_at ?? ''), (string) ($a->returned_at ?? '')));
        $rows = collect($rows);

        $reported = $rows->filter(fn ($r) => ($r->return_status ?? '') === 'reported' || ! empty($r->damage_reported_at));

        $branches = [];
        foreach ($this->supabase->query('branches', ['select' => 'id,name', 'order' => 'name.asc']) as $b) {
            $branches[(int) $b['id']] = $b['name'] ?? ('Branch #'.$b['id']);
        }

        return [
            'rows' => $rows,
            'reportedRows' => $reported,
            'lostCount' => $reported->where('damage_type', 'lost')->count(),
            'brokenCount' => $reported->where('damage_type', 'broken')->count(),
            'branches' => $branches,
            'filters' => [
                'damage_type' => $damageFilter,
                'branch_id' => (int) $request->query('branch_id'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'hasDamageColumns' => $hasDamageColumns,
        ];
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
            $name = $productNames[$id]['name'] ?? ('Product #'.$id);
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
