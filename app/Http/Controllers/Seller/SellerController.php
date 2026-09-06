<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function dashboard()
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();
        $branchId = auth()->user()->branch_id;

        // Seller's own sales
        $mySales = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$userId}",
            'select' => 'id,total,payment_method,sale_type,created_at',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        $totalSales = array_sum(array_map(fn($s) => $s['total'] ?? 0, $mySales));
        $totalTransactions = count($mySales);

        // Today's sales
        $todayStart = now()->startOfDay()->toIso8601String();
        $todaySales = array_filter($mySales, fn($s) => ($s['created_at'] ?? '') >= $todayStart);
        $todayTotal = array_sum(array_map(fn($s) => $s['total'] ?? 0, $todaySales));

        // Products available at this branch
        $products = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'quantity' => 'gt.0',
            'order' => 'created_at.desc',
        ]);

        return view('seller.dashboard', compact('totalSales', 'totalTransactions', 'todayTotal', 'mySales', 'products'));
    }

    public function sales(Request $request)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $params = [
            'select' => '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(id,name,phone)',
            'cashier_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ];

        $sales = $this->supabase->query('sales', $params);

        if ($request->date_from) {
            $from = $request->date_from;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->date_to) {
            $to = $request->date_to;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) <= $to);
        }

        $sales = collect(array_values($sales))->map(function ($s) {
            if (isset($s['customer']) && is_array($s['customer'])) $s['customer'] = (object) $s['customer'];
            if (isset($s['items'])) {
                $s['items'] = collect($s['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $s;
        });

        $totalSales = $sales->sum('total');

        return view('seller.sales', ['sales' => $sales, 'totalSales' => $totalSales]);
    }
}
