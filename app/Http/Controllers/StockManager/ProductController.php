<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $params = [
            'select' => '*, images:product_images(*)',
            'order' => 'created_at.desc',
        ];

        if (!$request->boolean('include_inactive')) {
            $params['is_active'] = 'eq.true';
        }

        $products = $this->supabase->query('products', $params);

        // Apply search filter in PHP
        if ($request->search) {
            $search = strtolower($request->search);
            $products = array_filter($products, function ($p) use ($search) {
                return str_contains(strtolower($p['name'] ?? ''), $search)
                    || str_contains(strtolower($p['brand'] ?? ''), $search)
                    || str_contains(strtolower($p['category'] ?? ''), $search);
            });
        }

        $products = collect(array_values($products))->map(function ($p) {
            if (isset($p['images'])) {
                $p['images'] = collect($p['images']);
            }
            return (object) $p;
        });

        return view('stock-manager.products.index', compact('products'));
    }

    public function create()
    {
        return view('stock-manager.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category' => 'required|in:Oil Fragrance,Brand Perfume',
            'sex_category' => 'nullable|in:male,female,unisex,accessories,gift sets',
            'images.*' => 'nullable|image|max:2048',
        ]);

        // Create product in Supabase
        $product = $this->supabase->insert('products', [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category' => $validated['category'],
            'sex_category' => $validated['sex_category'] ?? null,
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Also create in SQLite for Eloquent compatibility
        $localProduct = \App\Models\Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category' => $validated['category'],
            'sex_category' => $validated['sex_category'] ?? null,
        ]);

        // Upload images to Cloudinary
        if ($request->hasFile('images')) {
            $cloudinary = new CloudinaryService();
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
                        \App\Models\ProductImage::create([
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
        if (!$product) abort(404);

        if (isset($product['images'])) {
            $product['images'] = collect($product['images']);
        }

        return view('stock-manager.products.edit', ['product' => (object) $product]);
    }

    public function update(Request $request, $productId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category' => 'required|in:Oil Fragrance,Brand Perfume',
            'sex_category' => 'nullable|in:male,female,unisex,accessories,gift sets',
            'is_active' => 'boolean',
            'images.*' => 'nullable|image|max:2048',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_at'] = now()->toIso8601String();

        $this->supabase->update('products', $validated, ['id' => $productId]);

        // Also update in SQLite by matching name
        $product = $this->supabase->find('products', $productId);
        if ($product) {
            \App\Models\Product::where('name', $product['name'])->update($validated);
        }

        if ($request->hasFile('images')) {
            $cloudinary = new CloudinaryService();
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

        (new \App\Services\AuditService())->recordCriticalAction(
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
            \App\Models\Product::where('name', $product['name'])->update(['is_active' => false]);

            (new \App\Services\AuditService())->recordCriticalAction(
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
        if (!$image) abort(404);

        $productId = $image['product_id'];
        $this->supabase->delete('product_images', ['id' => $imageId]);

        return redirect()->route('stock-manager.products.edit', $productId)->with('success', 'Image removed.');
    }
}
