<?php

namespace App\Http\Controllers\Api\BranchAdmin;

use Illuminate\Http\Request;

/**
 * JSON twin of the branch-admin Sales screens: the branch's sales listing
 * (cashier + date filters), the sale-form data and the full checkout. The
 * checkout itself is the shared SmSalesSupport commit — the same code path
 * the Stock Manager and Customer Care APIs use — with the audit labels the
 * website's branch-admin store writes: action sale_created_by_admin,
 * "(Branch Admin)" movement notes and a Price Customized audit.
 */
class BaSalesController extends BaBaseController
{
    use \App\Http\Controllers\Api\StockManager\SmSalesSupport;

    /** Website branch-admin store: 'sale_created_by_admin'. */
    protected function saleAuditAction(): string
    {
        return 'sale_created_by_admin';
    }

    /** Role name used in the audit prose. */
    protected function saleActorLabel(): string
    {
        return 'Branch Admin';
    }

    /** The website's branch-admin store records a Price Customized audit. */
    protected function auditsCustomPrice(): bool
    {
        return true;
    }

    /** The website signs stock-movement notes "(Branch Admin)". */
    protected function saleNoteSuffix(): string
    {
        return ' (Branch Admin)';
    }

    public function index(Request $request)
    {
        $branchId = $this->ownBranchId($request);

        $params = [
            'select' => '*, cashier:users(id,name), customer:customers(id,name,phone), items:sale_items(*, product:products(id,name,brand))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->query('cashier_id')) {
            $params['cashier_id'] = 'eq.'.$request->query('cashier_id');
        }

        $sales = $this->supabase->query('sales', $params);

        // Date filters in PHP — PostgREST cannot take duplicate keys (the
        // website applies the very same filters the same way).
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        if ($dateFrom !== '') {
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) >= $dateFrom);
        }
        if ($dateTo !== '') {
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) <= $dateTo);
        }
        $sales = array_values($sales);

        $totalSales = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $sales));

        // The cashiers this admin can filter by — the branch's approved ones.
        $cashiers = $this->supabase->query('users', [
            'branch_id' => "eq.{$branchId}",
            'role' => 'eq.cashier',
            'status' => 'eq.approved',
            'select' => 'id,name',
        ]);

        return response()->json([
            'sales' => $sales,
            'totalSales' => $totalSales,
            'cashiers' => $cashiers,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** The sale form's data: branch stock, bottle stock, variety buckets. */
    public function options(Request $request)
    {
        $branchId = $this->ownBranchId($request);

        $rawStock = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'quantity' => 'gt.0',
            'order' => 'created_at.desc',
        ]);

        $products = collect($rawStock)->map(function ($item) {
            return [
                'id' => $item['id'],
                'branch_id' => $item['branch_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'selling_price' => $item['selling_price'],
                'product' => array_merge($item['product'] ?? [], [
                    'images' => collect($item['product']['images'] ?? [])->values()->all(),
                ]),
            ];
        })->values()->all();

        $productIds = array_map(fn ($p) => (int) $p['product_id'], $products);

        return response()->json([
            'products' => $products,
            'bottleStock' => $this->bottles->stockMap($branchId),
            'bottleVariants' => $this->bottles->variantStock($branchId),
            'productVarieties' => $this->varieties()->bucketsForProducts($branchId, $productIds),
            'is_products_only' => false,
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'payment_mode' => 'required|in:single,multi',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,bank_transfer,mobile_payment',
            'payments.*.amount' => 'required_if:payment_mode,multi|nullable|numeric|min:0',
            'items' => 'required_without:empty_bottles|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.custom_price' => 'nullable|numeric|min:0',
            'empty_bottles' => 'nullable|array',
            'empty_bottles.*.volume' => 'nullable|integer|in:6,12,30,50,100',
            'empty_bottles.*.quantity' => 'nullable|integer|min:1',
            'empty_bottles.*.price' => 'nullable|numeric|min:0',
            'empty_bottles.*.variant' => 'nullable|string|max:32',
            'items.*.volume' => 'nullable|integer',
            'items.*.variant' => 'nullable|string|max:32',
            'sale_type' => 'required|in:retail,wholesale',
        ]);

        try {
            return $this->commitSale($request, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->fail(['error' => 'Sale failed: '.$e->getMessage()]);
        }
    }

    public function show(Request $request, int $saleId)
    {
        $sale = $this->supabase->find('sales', $saleId, '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(*), cashier:users(id,name), branch:branches(id,name,address)');
        if (! $sale || ($sale['branch_id'] ?? null) != $this->ownBranchId($request)) {
            abort(404, 'Sale not found.');
        }

        return response()->json(['sale' => $sale]);
    }
}
