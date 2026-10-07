<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use Illuminate\Http\Request;

/**
 * JSON twin of the website's Stock Manager Sales screens: the manager's own
 * sales listing, the sale-form data, and the full checkout (products, picked
 * bottlings, empty bottles, split payments, discounts).
 */
class SmSalesController extends SmBaseController
{
    use SmSalesSupport;

    public function index(Request $request)
    {
        $this->assertOwnBranchOnly($request);

        $branchId = $this->activeBranchId($request);

        $params = $this->branchParams($request, [
            'select' => '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(id,name,phone)',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        // The stock manager's sales page is HIS record, not the branch's.
        $params['cashier_id'] = 'eq.'.$this->performingUserId($request);

        $sales = $this->supabase->query('sales', $params);

        if ($request->query('date_from')) {
            $from = (string) $request->query('date_from');
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->query('date_to')) {
            $to = (string) $request->query('date_to');
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) <= $to);
        }

        $sales = array_values($sales);
        $totalSales = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $sales));

        return response()->json([
            'sales' => $sales,
            'totalSales' => $totalSales,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** The sale form's data: branch stock, bottle stock, variety buckets. */
    public function options(Request $request)
    {
        $this->assertOwnBranchOnly($request);

        $branchId = $this->activeBranchId($request);

        $rawStock = $this->supabase->query('branch_stock', $this->branchParams($request, [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'quantity' => 'gt.0',
            'order' => 'created_at.desc',
        ]));

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
            'is_products_only' => $this->isProductsOnly($request),
        ]);
    }

    public function store(Request $request)
    {
        $this->assertOwnBranchOnly($request);
        $this->assertWritable($request);

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
            'items.*.discount_price' => 'nullable|numeric|min:0',
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
        $branchId = $this->activeBranchId($request);
        $sale = $this->supabase->find('sales', $saleId, '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(*), cashier:users(id,name), branch:branches(id,name,address)');
        if (! $sale || $sale['branch_id'] != $branchId) {
            abort(404, 'Sale not found.');
        }

        return response()->json(['sale' => $sale]);
    }
}
