<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\FinancialService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    private SupabaseService $supabase;
    private FinancialService $financials;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->financials = new FinancialService();
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

        // Branch daily financials (sales, expenses, actual = sales - expenses)
        $dailySummary = $this->financials->getDailySummary((int) $branchId);

        // Products available at this branch
        $products = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'quantity' => 'gt.0',
            'order' => 'created_at.desc',
        ]);

        return view('seller.dashboard', compact('totalSales', 'totalTransactions', 'todayTotal', 'mySales', 'products') + [
            'dailySummary' => $dailySummary,
        ]);
    }
}
