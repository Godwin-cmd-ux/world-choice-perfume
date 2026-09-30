<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private SupabaseService $supabase;

    private ProductVarietyStockService $varieties;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->varieties = new ProductVarietyStockService($this->supabase);
    }


    public function index(Request $request)
    {
        // Every active branch is offered, so a branch added later appears here
        // as soon as it is created.
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b)->values();

        // Full active product catalogue (independent of stock) — so out-of-stock
        // products still appear on the shop.
        $allProducts = $this->supabase->query('products', [
            'select' => 'id,name,description,brand,category,sex_category,fundamental_ingredient,is_active,created_at,updated_at,images:product_images(*)',
            'is_active' => 'eq.true',
            'order' => 'created_at.desc',
        ]);

        // Distinct brands across the catalogue for the "Brand" shop filter.
        $availableBrands = collect($allProducts)
            ->pluck('brand')
            ->filter(fn ($b) => ! empty($b))
            ->unique()
            ->sortBy(fn ($b) => strtolower($b))
            ->values()
            ->all();

        $products = collect();
        $selectedBranch = null;
        $showAllBranches = false;
        $branchCounts = [];

        if ($request->branch_id) {
            // Get the selected branch
            $selectedBranch = $this->supabase->find('branches', $request->branch_id);
            if ($selectedBranch) {
                $selectedBranch = (object) $selectedBranch;
            }

            if ($selectedBranch) {
                // All stock rows at this branch (including 0 quantity)
                $rawStock = $this->supabase->query('branch_stock', [
                    'select' => 'id,branch_id,product_id,quantity,selling_price,category,date_received,product:products(id,name,description,brand,category,sex_category,fundamental_ingredient,is_active,created_at,updated_at,images:product_images(*))',
                    'branch_id' => "eq.{$selectedBranch->id}",
                    'order' => 'created_at.desc',
                ]);

                $stockCollection = collect($rawStock);

                // Merge in catalogue products that have no stock row at this branch
                $stockedIds = $stockCollection->pluck('product_id')->map(fn ($id) => (int) $id)->all();
                $missing = collect($allProducts)
                    ->filter(fn ($p) => ! in_array((int) $p['id'], $stockedIds))
                    ->map(fn ($p) => [
                        'id' => null,
                        'branch_id' => $selectedBranch->id,
                        'product_id' => $p['id'],
                        'quantity' => 0,
                        'selling_price' => null,
                        'product' => $p,
                    ]);

                $stockCollection = $stockCollection->concat($missing);

                $products = $this->applyFilters($stockCollection, $request);
            }
        } else {
            // All Branches — fetch all branch_stock (including 0 quantity)
            $showAllBranches = true;

            $rawStock = $this->supabase->query('branch_stock', [
                'select' => 'id,branch_id,product_id,quantity,selling_price,category,date_received,product:products(id,name,description,brand,category,sex_category,fundamental_ingredient,is_active,created_at,updated_at,images:product_images(*))',
                'order' => 'created_at.desc',
            ]);

            $stockCollection = collect($rawStock);

            // The same perfume is stocked at more than one branch, so
            // "All branches" cannot quote a single price for it.
            $branchCounts = $this->inStockBranchCounts($rawStock);

            // Merge in catalogue products that have no stock row anywhere
            $stockedIds = $stockCollection->pluck('product_id')->map(fn ($id) => (int) $id)->all();
            $missing = collect($allProducts)
                ->filter(fn ($p) => ! in_array((int) $p['id'], $stockedIds))
                ->map(fn ($p) => [
                    'id' => null,
                    'branch_id' => null,
                    'product_id' => $p['id'],
                    'quantity' => 0,
                    'selling_price' => null,
                    'product' => $p,
                ]);

            // Deduplicate by product_id — keep only one entry per product,
            // preferring an in-stock row so a product available at any branch
            // is never shown as out of stock (or hidden) in the default view.
            $stockCollection = $stockCollection->concat($missing)
                ->sortByDesc('quantity')
                ->unique('product_id')
                ->values();

            $products = $this->applyFilters($stockCollection, $request);
        }

        return view('customer.products.index', [
            ...compact('branches', 'products', 'selectedBranch', 'availableBrands'),
            // In-stock bottlings per branch so an Oil Fragrance card that is
            // sold in several varieties does not advertise a single price.
            'varietiesByBranch' => $this->varietiesByBranch($products),
            // A perfume stocked at more than one branch is listed per branch
            // instead of being given one number for the whole country.
            'branchCounts' => $branchCounts,
        ]);
    }

    /**
     * How many branches actually stock each product: [product_id => count].
     * Out-of-stock rows are left out — a branch with nothing on the shelf is
     * not one of the prices a customer can choose from.
     *
     * @return array<int, int>
     */
    private function inStockBranchCounts(array $rawStock): array
    {
        $counts = [];
        foreach ($rawStock as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($productId <= 0 || $branchId <= 0 || (int) ($row['quantity'] ?? 0) <= 0) {
                continue;
            }
            $counts[$productId] = ($counts[$productId] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Variety picker data for every branch the listed cards point at.
     *
     * In "All branches" view each card links to the branch its own stock row
     * belongs to, so the varieties have to be read per branch rather than
     * once for the whole page.
     *
     * @return array<int, array<int, array>> [branch_id => [product_id => [volumes]]]
     */
    private function varietiesByBranch($products): array
    {
        $productIds = [];
        foreach ($products as $stock) {
            // Rows reach this point as arrays or objects depending on the
            // branch view, so read them the same way the view does.
            $branchId = (int) data_get($stock, 'branch_id');
            $productId = (int) data_get($stock, 'product_id');
            if ($branchId > 0 && $productId > 0) {
                $productIds[$branchId][$productId] = true;
            }
        }

        $result = [];
        foreach ($productIds as $branchId => $ids) {
            $result[$branchId] = $this->varieties->bucketsForProducts((int) $branchId, array_keys($ids));
        }

        return $result;
    }

    public function show(string $productId, Request $request)
    {
        // Fetch product from Supabase (unit cost columns excluded — internal only)
        $product = $this->supabase->find('products', $productId, 'id,name,description,brand,category,sex_category,fundamental_ingredient,is_active,created_at,updated_at,images:product_images(*)');

        if (! $product) {
            abort(404);
        }

        // Every active branch is offered, matching the shop listing.
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b)->values();

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
                'selling_price' => $item['selling_price'],
                'branch' => (object) ($item['branch'] ?? []),
            ];
        });

        // In-stock bottlings for this product at every branch it is stocked in.
        // A product sold in several varieties has no single price, so the page
        // lists each bottling with its own price and the customer orders the
        // one they want.
        $varietiesByBranch = [];
        foreach ($branchStocks->map(fn ($s) => (int) $s->branch_id)->filter()->unique() as $branchId) {
            $varietiesByBranch[$branchId] = $this->varieties->bucketsForProducts($branchId, [$productId])[(int) $productId] ?? [];
        }

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
            // Supabase embeds return arrays of arrays — cast each image to an
            // object so views can use ->image_url safely.
            $product->images = collect($product->images)->map(fn ($img) => (object) $img);
        }

        // Which branches actually stock it. With no branch picked the page
        // lists each of them with its own price; with one picked the page
        // belongs to that branch alone.
        $inStockBranches = $branchStocks->filter(fn ($s) => $s->quantity > 0);

        return view('customer.products.show', [
            ...compact('product', 'branches', 'branchStocks', 'selectedBranch', 'price'),
            'varietiesByBranch' => $varietiesByBranch,
            'varieties' => $selectedBranch ? ($varietiesByBranch[(int) $selectedBranch->id] ?? []) : [],
            'inStockBranchCount' => $inStockBranches->count(),
        ]);
    }

    private function applyFilters($stockCollection, Request $request)
    {
        // Search filter
        if ($request->search) {
            $search = strtolower($request->search);
            $stockCollection = $stockCollection->filter(function ($item) use ($search) {
                $product = $item['product'] ?? [];

                return str_contains(strtolower($product['name'] ?? ''), $search)
                    || str_contains(strtolower($product['brand'] ?? ''), $search)
                    || str_contains(strtolower($product['category'] ?? ''), $search)
                    || str_contains(strtolower($product['sex_category'] ?? ''), $search);
            });
        }

        // Sex category filter (male, female, unisex, accessories)
        if ($request->sex_category) {
            $sexCategory = $request->sex_category;
            $stockCollection = $stockCollection->filter(function ($item) use ($sexCategory) {
                return ($item['product']['sex_category'] ?? null) === $sexCategory;
            });
        }

        // Category filter (legacy — Oil Fragrance / Brand Perfume)
        if ($request->category) {
            $category = $request->category;
            $stockCollection = $stockCollection->filter(function ($item) use ($category) {
                return ($item['product']['category'] ?? '') === $category;
            });
        }

        // Fundamental ingredient filter (Floral, Fresh/Citrus, Wood, Amber/Spicy,
        // Fruity, Oud, Gourmand, Aromatic)
        if ($request->fundamental_ingredient) {
            $ingredient = $request->fundamental_ingredient;
            $stockCollection = $stockCollection->filter(function ($item) use ($ingredient) {
                return ($item['product']['fundamental_ingredient'] ?? null) === $ingredient;
            });
        }

        // Brand filter (e.g. Gucci, Dior, Lattafa)
        if ($request->brand) {
            $brand = strtolower($request->brand);
            $stockCollection = $stockCollection->filter(function ($item) use ($brand) {
                return strtolower((string) ($item['product']['brand'] ?? '')) === $brand;
            });
        }

        // Show all products — including out-of-stock items, which are flagged
        // with an "Out of Stock" badge in the view so customers always see the
        // full catalogue when filtering.

        return $stockCollection->values()->map(fn ($item) => (object) [
            'id' => $item['id'] ?? null,
            'branch_id' => $item['branch_id'] ?? null,
            'product_id' => $item['product_id'] ?? null,
            'quantity' => $item['quantity'] ?? 0,
            'selling_price' => $item['selling_price'] ?? null,
            'product' => (object) array_merge($item['product'] ?? [], [
                // Cast each image to an object so views can use ->image_url.
                'images' => collect($item['product']['images'] ?? [])->map(fn ($img) => (object) $img),
            ]),
        ]);
    }
}
