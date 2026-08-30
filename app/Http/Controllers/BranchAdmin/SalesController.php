<?php

namespace App\Http\Controllers\BranchAdmin;

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

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, cashier:users(id,name), customer:customers(id,name,phone), items:sale_items(*, product:products(id,name,brand))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->cashier_id) {
            $params['cashier_id'] = "eq.{$request->cashier_id}";
        }

        $sales = $this->supabase->query('sales', $params);

        // Apply date filters in PHP (PostgREST doesn't support duplicate keys)
        if ($request->date_from) {
            $from = $request->date_from;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->date_to) {
            $to = $request->date_to;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) <= $to);
        }

        $sales = array_values($sales);
        $totalSales = array_sum(array_map(fn($s) => $s['total'] ?? 0, $sales));

        // Get cashiers for this branch
        $cashiers = $this->supabase->query('users', [
            'branch_id' => "eq.{$branchId}",
            'role' => 'eq.cashier',
            'status' => 'eq.approved',
            'select' => 'id,name',
        ]);

        // Cast for views
        $sales = collect($sales)->map(function ($s) {
            if (isset($s['cashier']) && is_array($s['cashier'])) $s['cashier'] = (object) $s['cashier'];
            if (isset($s['customer']) && is_array($s['customer'])) $s['customer'] = (object) $s['customer'];
            if (isset($s['items'])) {
                $s['items'] = collect($s['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $s;
        });

        return view('branch-admin.sales.index', [
            'sales' => $sales,
            'totalSales' => $totalSales,
            'cashiers' => collect($cashiers),
        ]);
    }

    public function show($saleId)
    {
        $sale = $this->supabase->find('sales', $saleId, '*, cashier:users(id,name), customer:customers(*), items:sale_items(*, product:products(id,name,brand)), branch:branches(id,name,address)');
        if (!$sale) abort(404);

        if (isset($sale['cashier']) && is_array($sale['cashier'])) $sale['cashier'] = (object) $sale['cashier'];
        if (isset($sale['customer']) && is_array($sale['customer'])) $sale['customer'] = (object) $sale['customer'];
        if (isset($sale['branch']) && is_array($sale['branch'])) $sale['branch'] = (object) $sale['branch'];
        if (isset($sale['items'])) {
            $sale['items'] = collect($sale['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }

        return view('branch-admin.sales.show', ['sale' => (object) $sale]);
    }
}
