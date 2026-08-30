<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function index(Request $request)
    {
        // Fetch active branches from Supabase
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        $products = collect();
        $selectedBranch = null;

        if ($request->branch_id) {
            // Get the selected branch
            $selectedBranch = $this->supabase->find('branches', $request->branch_id);
            if ($selectedBranch) {
                $selectedBranch = (object) $selectedBranch;
            }

            if ($selectedBranch) {
                // Fetch products with stock at this branch (quantity > 0)
                $params = [
                    'select' => '*, product:products(*, images:product_images(*))',
                    'branch_id' => "eq.{$selectedBranch->id}",
                    'quantity' => 'gt.0',
                    'order' => 'created_at.desc',
                ];

                // Search filter
                if ($request->search) {
                    $search = $request->search;
                    // PostgREST doesn't support OR across tables easily, so we fetch all then filter
                }

                // Category filter
                if ($request->category) {
                    // We need to filter by product.category after fetching
                }

                $rawStock = $this->supabase->query('branch_stock', $params);

                // Apply search and category filters on the PHP side
                $stockCollection = collect($rawStock);

                if ($request->search) {
                    $search = strtolower($request->search);
                    $stockCollection = $stockCollection->filter(function ($item) use ($search) {
                        $product = $item['product'] ?? [];
                        return str_contains(strtolower($product['name'] ?? ''), $search)
                            || str_contains(strtolower($product['brand'] ?? ''), $search)
                            || str_contains(strtolower($product['category'] ?? ''), $search);
                    });
                }

                if ($request->category) {
                    $category = $request->category;
                    $stockCollection = $stockCollection->filter(function ($item) use ($category) {
                        return ($item['product']['category'] ?? '') === $category;
                    });
                }

                $products = $stockCollection->values()->map(fn($item) => (object) [
                    'id' => $item['id'],
                    'branch_id' => $item['branch_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'selling_price' => $item['selling_price'],
                    'product' => (object) array_merge($item['product'] ?? [], [
                        'images' => collect($item['product']['images'] ?? []),
                    ]),
                ]);
            }
        }

        return view('customer.products.index', compact('branches', 'products', 'selectedBranch'));
    }

    public function show(string $productId, Request $request)
    {
        // Fetch product from Supabase
        $product = $this->supabase->find('products', $productId, '*, images:product_images(*)');

        if (!$product) {
            abort(404);
        }

        // Fetch active branches
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        // Fetch branch stock for this product
        $rawStock = $this->supabase->query('branch_stock', [
            'select' => '*, branch:branches(id,name), product:products(id,name)',
            'product_id' => "eq.{$productId}",
        ]);

        $branchStocks = collect($rawStock)->map(function ($item) {
            // Restructure to match the expected format
            return (object) [
                'id' => $item['id'],
                'branch_id' => $item['branch_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'buying_cost' => $item['buying_cost'],
                'selling_price' => $item['selling_price'],
                'branch' => (object) ($item['branch'] ?? []),
            ];
        });

        // Get selected branch and price
        $selectedBranch = null;
        $price = null;

        if ($request->branch_id) {
            $selectedBranch = $this->supabase->find('branches', $request->branch_id);
            if ($selectedBranch) {
                $selectedBranch = (object) $selectedBranch;
            }
            if ($selectedBranch) {
                $stockItem = $branchStocks->first(function ($s) use ($request) {
                    return $s->branch_id == $request->branch_id && $s->quantity > 0;
                });
                $price = $stockItem?->selling_price;
            }
        }

        // Cast product to object for the view
        $product = (object) $product;
        if (isset($product->images) && is_array($product->images)) {
            $product->images = collect($product->images);
        }

        return view('customer.products.show', compact('product', 'branches', 'branchStocks', 'selectedBranch', 'price'));
    }
}
