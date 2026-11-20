<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CompanySettingService;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use App\Support\BranchAccess;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** The only brand whose oil fragrance is offered to customers. */
    private const OWN_OIL_BRAND = 'world choice perfumes';

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

        // Oil fragrance sold under another house is not ours to sell, so the
        // shop lists only oil fragrance carrying the WORLD CHOICE PERFUMES
        // name. Brand perfumes are unaffected.
        $allProducts = array_values(array_filter($allProducts, fn ($p) => $this->visibleOnShop($p)));

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
            $rawStock = $this->supabase->query('branch_stock', [
                'select' => 'id,branch_id,product_id,quantity,selling_price,category,date_received,product:products(id,name,description,brand,category,sex_category,fundamental_ingredient,is_active,created_at,updated_at,images:product_images(*))',
                'order' => 'created_at.desc',
            ]);

            $stockCollection = collect($rawStock);

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

        // Availability and sizes are company-wide: a product on any shelf is
        // Available, without the customer being told which shelf that was.
        $listedProductIds = $products->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $availableProductIds = $this->availableProductIds($listedProductIds);
        $varietiesByProduct = $this->varieties->volumeBucketsAcrossBranches($listedProductIds);

        if ($request->expectsJson()) {
            // Mobile app: same data the view receives, as JSON. The heavy
            // variety buckets are left out of the listing — the detail
            // endpoint serves them per product.
            return response()->json([
                'branches' => $branches,
                'products' => $products,
                'brands' => $availableBrands,
                'available_product_ids' => $availableProductIds,
                'selected_branch' => $selectedBranch,
            ]);
        }

        // The shop header clip belongs to the graphics designer, not the code:
        // no row means the bundled clip, a URL means their upload, and an empty
        // value means they removed the video altogether.
        try {
            $heroVideoUrl = CompanySettingService::get('shop_hero_video_url');
        } catch (\Throwable $e) {
            report($e);
            $heroVideoUrl = null;
        }

        if ($heroVideoUrl === null) {
            $heroVideoUrl = asset('videos/reef_33.mp4');
        }

        $heroVideoPath = strtolower((string) parse_url($heroVideoUrl, PHP_URL_PATH));
        $heroVideoType = str_ends_with($heroVideoPath, '.webm') ? 'video/webm' : 'video/mp4';

        return view('customer.products.index', [
            ...compact('branches', 'products', 'selectedBranch', 'availableBrands'),
            // In-stock SIZES across the whole company so an Oil Fragrance card
            // that is bottled in several volumes does not advertise a single
            // price. Packaging is collapsed away before the customer sees it.
            'varietiesByProduct' => $varietiesByProduct,
            'availableProductIds' => $availableProductIds,
            'heroVideoUrl' => $heroVideoUrl,
            'heroVideoType' => $heroVideoType,
        ]);
    }

    /**
     * Which listed products are on a shelf somewhere in the company.
     *
     * A product stocked at a non-HQ branch still counts as Available to the
     * customer; the order itself is always routed to Mikocheni HQ.
     *
     * @return array<int, int>
     */
    private function availableProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = $this->supabase->query('branch_stock', [
            'select' => 'product_id,quantity',
            'product_id' => 'in.(' . implode(',', $productIds) . ')',
        ]);

        $listedIds = array_fill_keys($productIds, true);
        $available = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            if (isset($listedIds[$productId]) && (int) ($row['quantity'] ?? 0) > 0) {
                $available[$productId] = $productId;
            }
        }

        return array_values($available);
    }

    public function show(string $productId, Request $request)
    {
        // Fetch product from Supabase (unit cost columns excluded — internal only)
        $product = $this->supabase->find('products', $productId, 'id,name,description,brand,category,sex_category,fundamental_ingredient,is_active,created_at,updated_at,images:product_images(*)');

        if (! $product) {
            abort(404);
        }

        // Stock is read only to answer one question: is this product on any
        // shelf? Branch names, coordinates and quantities used to reach the
        // customer here; none of them are part of the answer now.
        $rawStock = $this->supabase->query('branch_stock', [
            'select' => 'id,branch_id,product_id,quantity,selling_price',
            'product_id' => "eq.{$productId}",
        ]);

        $inStockRows = collect($rawStock)
            ->filter(fn ($row) => (int) ($row['quantity'] ?? 0) > 0)
            // HQ first, so a price already used by HQ orders wins when the
            // same product is priced differently at another branch.
            ->sortByDesc(fn ($row) => (int) ($row['branch_id'] ?? 0) === BranchAccess::HEAD_QUARTERS_ID)
            ->values();

        $isAvailable = $inStockRows->isNotEmpty();

        // Sizes from every shelf are merged into one company-wide list. The
        // customer sees a volume and its price, never the branch behind it.
        $varieties = $this->varieties->volumeBucketsAcrossBranches([$productId])[(string) $productId] ?? [];

        $price = null;
        foreach ($inStockRows as $row) {
            if ((float) ($row['selling_price'] ?? 0) > 0) {
                $price = (float) $row['selling_price'];
                break;
            }
        }

        // Cast product to object for the view
        $product = (object) $product;
        if (isset($product->images) && is_array($product->images)) {
            // Supabase embeds return arrays of arrays — cast each image to an
            // object so views can use ->image_url safely.
            $product->images = collect($product->images)->map(fn ($img) => (object) $img);
        }

        $orderBranchId = BranchAccess::HEAD_QUARTERS_ID;

        if ($request->expectsJson()) {
            // Mobile app: the same single Availability answer the website
            // renders. No branch list, source stock or branch-keyed sizes.
            return response()->json([
                'product' => $product,
                'is_available' => $isAvailable,
                'order_branch_id' => $orderBranchId,
                'price' => $price,
                'varieties' => $varieties,
            ]);
        }

        return view('customer.products.show', compact(
            'product',
            'isAvailable',
            'orderBranchId',
            'price',
            'varieties'
        ));
    }

    /**
     * Whether a product may appear on the customer shop. Oil fragrance the
     * company does not make is hidden there; every other product is shown as
     * before. The back office still sees the whole catalogue.
     */
    private function visibleOnShop($product): bool
    {
        if (data_get($product, 'category') !== 'Oil Fragrance') {
            return true;
        }

        return self::OWN_OIL_BRAND === strtolower(trim((string) data_get($product, 'brand', '')));
    }

    private function applyFilters($stockCollection, Request $request)
    {
        // A branch can still hold oil fragrance from another house — it must
        // not reach the shop, whether the customer is browsing or filtering.
        $stockCollection = $stockCollection->filter(
            fn ($item) => $this->visibleOnShop(data_get($item, 'product'))
        );

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
