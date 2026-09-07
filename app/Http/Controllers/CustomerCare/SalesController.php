<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Customer care sees sales for their branch with customer + cashier names.
     */
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $sales = collect($this->supabase->query('sales', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ]));

        // PHP-side joins (PostgREST expansions return 0 rows)
        $branchIds = $sales->pluck('branch_id')->filter()->unique()->values()->toArray();
        $customerIds = $sales->pluck('customer_id')->filter()->unique()->values()->toArray();
        $cashierIds = $sales->pluck('cashier_id')->filter()->unique()->values()->toArray();

        $branches = [];
        if (!empty($branchIds)) {
            $list = $this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $branchIds) . ')',
            ]);
            foreach ($list as $b) $branches[$b['id']] = $b['name'];
        }

        $customers = [];
        if (!empty($customerIds)) {
            $list = $this->supabase->query('customers', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $customerIds) . ')',
            ]);
            foreach ($list as $c) $customers[$c['id']] = $c['name'];
        }

        $cashiers = [];
        if (!empty($cashierIds)) {
            $list = $this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $cashierIds) . ')',
            ]);
            foreach ($list as $u) $cashiers[$u['id']] = $u['name'];
        }

        $sales = $sales->map(function ($s) use ($branches, $customers, $cashiers) {
            $s = (array) $s;
            $s['branch'] = isset($s['branch_id']) && isset($branches[$s['branch_id']])
                ? (object) ['id' => $s['branch_id'], 'name' => $branches[$s['branch_id']]]
                : null;
            $s['customer'] = isset($s['customer_id']) && isset($customers[$s['customer_id']])
                ? (object) ['id' => $s['customer_id'], 'name' => $customers[$s['customer_id']]]
                : null;
            $s['cashier'] = isset($s['cashier_id']) && isset($cashiers[$s['cashier_id']])
                ? (object) ['id' => $s['cashier_id'], 'name' => $cashiers[$s['cashier_id']]]
                : null;
            return (object) $s;
        });

        $totalRevenue = $sales->sum('total');

        return view('customer-care.sales.index', [
            'sales' => $sales,
            'totalRevenue' => $totalRevenue,
        ]);
    }
}
