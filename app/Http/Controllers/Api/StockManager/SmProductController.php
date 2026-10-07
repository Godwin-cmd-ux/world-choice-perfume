<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Http\Controllers\Api\StockManager\SmBaseController;
use App\Services\AuditService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * JSON twin of the website's Stock Manager Product Management screens
 * (the catalogue itself, not the branch's stock rows).
 */
class SmProductController extends SmBaseController
{
    public const FUNDAMENTAL_INGREDIENTS = [
        'Floral', 'Fresh/Citrus', 'Wood', 'Amber/Spicy', 'Fruity', 'Oud', 'Gourmand', 'Aromatic',
    ];

    /** All brand names registered by the graphic designer. */
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

        if ($request->query('search')) {
            $search = strtolower((string) $request->query('search'));
            $products = array_filter($products, function ($p) use ($search) {
                return str_contains(strtolower($p['name'] ?? ''), $search)
                    || str_contains(strtolower($p['brand'] ?? ''), $search)
                    || str_contains(strtolower($p['category'] ?? ''), $search)
                    || str_contains(strtolower($p['fundamental_ingredient'] ?? ''), $search);
            });
        }

        return response()->json([
            'products' => array_values($products),
            'brands' => $this->registeredBrandNames(),
        ]);
    }

    /** Brand options + ingredient list for the create/edit form. */
    public function formData(Request $request)
    {
        return response()->json([
            'brands' => $this->registeredBrandNames(),
            'fundamentalIngredients' => self::FUNDAMENTAL_INGREDIENTS,
            'categories' => ['Oil Fragrance', 'Brand Perfume'],
            'sexCategories' => ['male', 'female', 'unisex', 'accessories'],
        ]);
    }

    public function store(Request $request)
    {
        $this->assertWritable($request);

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

        // Eloquent mirror for SQLite compatibility, same as the website.
        if ($product) {
            \App\Models\Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'category' => $category,
                'sex_category' => $validated['sex_category'] ?? null,
                'fundamental_ingredient' => $validated['fundamental_ingredient'] ?? null,
            ]);
        }

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
                }
            }
        }

        return response()->json(['message' => 'Product created successfully.', 'product' => $product]);
    }

    /** Edit-form data: the product with its images plus valid brand options. */
    public function show(Request $request, int $productId)
    {
        $product = $this->supabase->find('products', $productId, '*, images:product_images(*)');
        if (! $product) {
            abort(404, 'Product not found.');
        }

        $brands = $this->registeredBrandNames();
        $currentBrand = trim((string) ($product['brand'] ?? ''));
        if ($currentBrand !== '' && ! in_array($currentBrand, $brands, true)) {
            $brands[] = $currentBrand;
        }

        return response()->json([
            'product' => $product,
            'brands' => $brands,
            'fundamentalIngredients' => self::FUNDAMENTAL_INGREDIENTS,
        ]);
    }

    public function update(Request $request, int $productId)
    {
        $this->assertWritable($request);

        $currentProduct = $this->supabase->find('products', $productId);
        if (! $currentProduct) {
            abort(404, 'Product not found.');
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

        // Only data columns — "images" is a multipart file array, not a column.
        $productData = collect($validated)->except(['images', 'updated_at'])->all();

        $this->supabase->update('products', $productData, ['id' => $productId]);

        $product = $this->supabase->find('products', $productId);
        if ($product) {
            \App\Models\Product::where('name', $product['name'])->update($productData);
        }

        if ($request->hasFile('images')) {
            $cloudinary = new CloudinaryService;
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
            "Product ".($product['name'] ?? '')." was updated (id #{$productId}).",
            ['product_id' => $productId, 'product_name' => $product['name'] ?? null, 'validated' => array_keys($validated)],
            'products',
            (string) $productId
        );

        return response()->json(['message' => 'Product updated successfully.']);
    }

    /** Deactivate (never a hard delete), same as the website. */
    public function destroy(Request $request, int $productId)
    {
        $this->assertWritable($request);

        $this->supabase->update('products', [
            'is_active' => false,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $productId]);

        $product = $this->supabase->find('products', $productId);
        if ($product) {
            \App\Models\Product::where('name', $product['name'])->update(['is_active' => false]);

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

        return response()->json(['message' => 'Product deactivated.']);
    }

    public function removeImage(Request $request, int $imageId)
    {
        $this->assertWritable($request);

        $image = $this->supabase->find('product_images', $imageId);
        if (! $image) {
            abort(404, 'Image not found.');
        }

        $this->supabase->delete('product_images', ['id' => $imageId]);

        return response()->json(['message' => 'Image removed.']);
    }
}
