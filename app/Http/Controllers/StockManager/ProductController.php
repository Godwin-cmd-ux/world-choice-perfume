<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditService;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public const FUNDAMENTAL_INGREDIENTS = [
        'Floral', 'Fresh/Citrus', 'Wood', 'Amber/Spicy', 'Fruity', 'Oud', 'Gourmand', 'Aromatic',
    ];

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    /**
     * All brand names registered in the brands table (managed by the graphic
     * designer). Product entry/edit is limited to these brands.
     */
    private function registeredBrandNames(): array
    {
        return collect($this->supabase->query('brands', [
            'select' => 'name',
            'order' => 'name.asc',
        ]))->pluck('name')->all();
    }

    public function index(Request $request)
    {
        $params = [
            'select' => '*, images:product_images(*)',
            'order' => 'created_at.desc',
        ];

        if (! $request->boolean('include_inactive')) {
            $params['is_active'] = 'eq.true';
        }

        $products = $this->supabase->query('products', $params);

        // Apply search filter in PHP
        if ($request->search) {
            $search = strtolower($request->search);
            $products = array_filter($products, function ($p) use ($search) {
                return str_contains(strtolower($p['name'] ?? ''), $search)
                    || str_contains(strtolower($p['brand'] ?? ''), $search)
                    || str_contains(strtolower($p['category'] ?? ''), $search)
                    || str_contains(strtolower($p['fundamental_ingredient'] ?? ''), $search);
            });
        }

        $products = collect(array_values($products))->map(function ($p) {
            if (isset($p['images'])) {
                // Supabase embeds return arrays of arrays — cast each image to
                // an object so views can use ->image_url safely.
                $p['images'] = collect($p['images'])->map(fn ($img) => (object) $img);
            }

            return (object) $p;
        });

        return view('stock-manager.products.index', compact('products'));
    }

    public function create()
    {
        return view('stock-manager.products.create', ['brands' => $this->registeredBrandNames()]);
    }

    public function store(Request $request)
    {
        $brands = $this->registeredBrandNames();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => ['nullable', Rule::in($brands)],
            'category' => 'required|in:Oil Fragrance,Brand Perfume',
            'sex_category' => 'nullable|in:male,female,unisex,accessories',
            'fundamental_ingredient' => 'nullable|in:'.implode(',', self::FUNDAMENTAL_INGREDIENTS),
            'images.*' => 'nullable|image|max:51200',
        ], [
            'brand.in' => 'The selected brand is not registered. Brands are added by the graphic designer.',
        ]);

        $category = $validated['category'];

        // Create product in Supabase
        $product = $this->supabase->insert('products', [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category' => $category,
            'sex_category' => $validated['sex_category'] ?? null,
            'fundamental_ingredient' => $validated['fundamental_ingredient'] ?? null,
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Also create in SQLite for Eloquent compatibility
        $localProduct = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category' => $category,
            'sex_category' => $validated['sex_category'] ?? null,
            'fundamental_ingredient' => $validated['fundamental_ingredient'] ?? null,
        ]);

        // Upload images to Cloudinary
        if ($request->hasFile('images')) {
            $cloudinary = new CloudinaryService;
            foreach ($request->file('images') as $index => $image) {
                $url = $cloudinary->upload($image, 'products');
                if ($url && $product) {
                    $this->supabase->insert('product_images', [
                        'product_id' => $product['id'],
                        'image_url' => $url,
                        'sort_order' => $index,
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ]);
                    // Also in SQLite
                    if ($localProduct) {
                        ProductImage::create([
                            'product_id' => $localProduct->id,
                            'image_url' => $url,
                            'sort_order' => $index,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('stock-manager.products.index')->with('success', 'Product created successfully.');
    }

    public function edit($productId)
    {
        $product = $this->supabase->find('products', $productId, '*, images:product_images(*)');
        if (! $product) {
            abort(404);
        }

        if (isset($product['images'])) {
            $product['images'] = collect($product['images'])->map(fn ($img) => (object) $img);
        }

        // Always include the product's current brand even if it isn't in the
        // registered list (e.g. a brand removed by the graphic designer), so
        // the dropdown never loses the existing value.
        $brands = $this->registeredBrandNames();
        $currentBrand = trim((string) ($product['brand'] ?? ''));
        if ($currentBrand !== '' && ! in_array($currentBrand, $brands, true)) {
            $brands[] = $currentBrand;
        }

        return view('stock-manager.products.edit', ['product' => (object) $product, 'brands' => $brands]);
    }

    public function update(Request $request, $productId)
    {
        $currentProduct = $this->supabase->find('products', $productId);
        if (! $currentProduct) {
            abort(404);
        }

        $brands = $this->registeredBrandNames();
        $currentBrand = trim((string) ($currentProduct['brand'] ?? ''));
        if ($currentBrand !== '' && ! in_array($currentBrand, $brands, true)) {
            $brands[] = $currentBrand;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => ['nullable', Rule::in($brands)],
            'category' => 'required|in:Oil Fragrance,Brand Perfume',
            'sex_category' => 'nullable|in:male,female,unisex,accessories',
            'fundamental_ingredient' => 'nullable|in:'.implode(',', self::FUNDAMENTAL_INGREDIENTS),
            'is_active' => 'boolean',
            'images.*' => 'nullable|image|max:51200',
        ], [
            'brand.in' => 'The selected brand is not registered. Brands are added by the graphic designer.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_at'] = now()->toIso8601String();

        // Only persist the product's data columns — file uploads and timestamps
        // are not database columns ("images" is a multipart file array).
        $productData = collect($validated)->except(['images', 'updated_at'])->all();

        $this->supabase->update('products', $productData, ['id' => $productId]);

        // Also update in SQLite by matching name
        $product = $this->supabase->find('products', $productId);
        if ($product) {
            Product::where('name', $product['name'])->update($productData);
        }

        if ($request->hasFile('images')) {
            $cloudinary = new CloudinaryService;
            // Get current image count for sort_order
            $existingImages = $this->supabase->query('product_images', [
                'product_id' => "eq.{$productId}",
            ]);
            $sortOffset = count($existingImages);

            foreach ($request->file('images') as $index => $image) {
                $url = $cloudinary->upload($image, 'products');
                if ($url) {
                    $this->supabase->insert('product_images', [
                        'product_id' => $productId,
                        'image_url' => $url,
                        'sort_order' => $sortOffset + $index,
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ]);
                }
            }
        }

        (new AuditService)->recordCriticalAction(
            'product_updated',
            'product_updated',
            'Product Updated',
            "Product {$product['name']} was updated (id #{$productId}).",
            ['product_id' => $productId, 'product_name' => $product['name'] ?? null, 'validated' => array_keys($validated)],
            'products',
            (string) $productId
        );

        return redirect()->route('stock-manager.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy($productId)
    {
        $this->supabase->update('products', [
            'is_active' => false,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $productId]);

        // Also update SQLite
        $product = $this->supabase->find('products', $productId);
        if ($product) {
            Product::where('name', $product['name'])->update(['is_active' => false]);

            (new AuditService)->recordCriticalAction(
                'product_deactivated',
                'product_deactivated',
                'Product Deactivated',
                "Product {$product['name']} was deactivated (id #{$productId}).",
                ['product_id' => $productId, 'product_name' => $product['name'] ?? null],
                'products',
                (string) $productId,
                ['is_active' => true],
                ['is_active' => false]
            );
        }

        return redirect()->route('stock-manager.products.index')->with('success', 'Product deactivated.');
    }

    public function removeImage($imageId)
    {
        $image = $this->supabase->find('product_images', $imageId);
        if (! $image) {
            abort(404);
        }

        $productId = $image['product_id'];
        $this->supabase->delete('product_images', ['id' => $imageId]);

        return redirect()->route('stock-manager.products.edit', $productId)->with('success', 'Image removed.');
    }
}
