<?php

namespace App\Http\Controllers\Api\Seller;

use App\Services\FinancialService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;

/**
 * JSON twin of seller.dashboard: the seller's own sales totals (today and
 * overall), their recent sales, the branch's daily sales/expenses/actual
 * summary, the branch's pending-order count and the products in stock —
 * exactly the figures the Blade dashboard computes for the signed-in seller.
 */
class SeDashboardController extends SeBaseController
{
    /** Just the branch flags — the app shapes navigation from this. */
    public function scope(Request $request)
    {
        return response()->json(['scope' => $this->scopePayload($request)]);
    }

    public function dashboard(Request $request)
    {
        $userId = $this->performingUserId($request);
        $branchId = $this->ownBranchId($request);

        // Seller's own sales (the website reads the same 50-row window).
        $mySales = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$userId}",
            'select' => 'id,total,payment_method,sale_type,created_at',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        $totalSales = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $mySales));
        $totalTransactions = count($mySales);

        // Today's sales (shop business day, so it clears at midnight).
        $todaySales = array_filter($mySales, fn ($s) => BusinessDay::withinDay($s['created_at'] ?? null));
        $todayTotal = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $todaySales));

        // Branch daily financials (sales, expenses, actual = sales - expenses).
        $dailySummary = (new FinancialService($this->supabase))->getDailySummary((int) $branchId);

        // Pending orders for this branch (quick access to order processing).
        $pendingOrders = $this->supabase->count('orders', [
            'branch_id' => "eq.{$branchId}",
            'status' => 'eq.pending',
        ]);

        // Products available at this branch.
        $products = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'quantity' => 'gt.0',
            'order' => 'created_at.desc',
        ]);

        return response()->json([
            'totalSales' => $totalSales,
            'totalTransactions' => $totalTransactions,
            'todayTotal' => $todayTotal,
            'mySales' => array_values($mySales),
            'products' => array_values($products),
            'pendingOrders' => $pendingOrders,
            'dailySummary' => $dailySummary,
            'scope' => $this->scopePayload($request),
        ]);
    }
}
