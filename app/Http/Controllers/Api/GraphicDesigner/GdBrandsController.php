<?php

namespace App\Http\Controllers\Api\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * JSON twin of GraphicDesigner\BrandsController — the same brand list with
 * PHP-side product counts, duplicate-name guard, Cloudinary logo upload,
 * rename → product.brand sync and the in-use delete protection; only the
 * responses differ (JSON messages instead of Blade redirects).
 */
class GdBrandsController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    /** All brands (with product counts) for management. */
    public function index()
    {
        $brands = collect($this->supabase->query('brands', [
            'select' => '*',
            'order' => 'name.asc',
        ]));

        // Product count per brand name (PHP-side join — PostgREST embeds can
        // return 0 rows for cross-table expansions).
        $counts = [];
        $products = $this->supabase->query('products', ['select' => 'brand']);
        foreach ($products as $p) {
            $name = trim((string) ($p['brand'] ?? ''));
            if ($name === '') {
                continue;
            }
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }

        $brands = $brands->map(function ($b) use ($counts) {
            $b['product_count'] = $counts[$b['name']] ?? 0;

            return (object) $b;
        });

        $countsUi = [
            'total' => $brands->count(),
            'active' => $brands->where('is_active', true)->count(),
            'inactive' => $brands->where('is_active', false)->count(),
            'with_logo' => $brands->filter(fn ($b) => ! empty($b->logo_url))->count(),
        ];

        return response()->json(['brands' => $brands, 'counts' => $countsUi]);
    }

    /** Single brand for the mobile edit form (404 when missing). */
    public function show($brandId)
    {
        $brand = $this->supabase->find('brands', $brandId);
        if (! $brand) {
            return response()->json(['message' => 'Brand not found.'], 404);
        }

        return response()->json(['brand' => (object) $brand]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:51200',
            'is_active' => 'boolean',
        ]);

        if ($this->brandNameExists($validated['name'])) {
            return response()->json([
                'message' => 'A brand with this name already exists.',
                'errors' => ['name' => ['A brand with this name already exists.']],
            ], 422);
        }

        $logoUrl = null;
        if ($request->hasFile('logo')) {
            $logoUrl = (new CloudinaryService)->upload($request->file('logo'), 'brands');
        }

        $brand = $this->supabase->insert('brands', [
            'name' => $validated['name'],
            'logo_url' => $logoUrl,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $this->creatorId($request),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => 'Brand created. It will appear in the Featured Brands section on the home page.',
            'brand' => $brand,
        ], 201);
    }

    public function update(Request $request, $brandId)
    {
        $brand = $this->supabase->find('brands', $brandId);
        if (! $brand) {
            return response()->json(['message' => 'Brand not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:51200',
            'is_active' => 'boolean',
        ]);

        if ($this->brandNameExists($validated['name'], $brandId)) {
            return response()->json([
                'message' => 'A brand with this name already exists.',
                'errors' => ['name' => ['A brand with this name already exists.']],
            ], 422);
        }

        $data = [
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $this->creatorId($request),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($request->hasFile('logo')) {
            $data['logo_url'] = (new CloudinaryService)->upload($request->file('logo'), 'brands');
        }

        $this->supabase->update('brands', $data, ['id' => $brandId]);

        // Keep product brand strings in sync when the brand is renamed.
        if ($validated['name'] !== $brand['name']) {
            $this->supabase->update('products', ['brand' => $validated['name']], ['brand' => $brand['name']]);
            Product::where('brand', $brand['name'])->update(['brand' => $validated['name']]);
        }

        return response()->json(['message' => 'Brand updated.']);
    }

    public function destroy($brandId)
    {
        $brand = $this->supabase->find('brands', $brandId);
        if (! $brand) {
            return response()->json(['message' => 'Brand not found.'], 404);
        }

        // Protect data integrity — brands still used by products must not vanish.
        $products = $this->supabase->query('products', [
            'select' => 'id',
            'brand' => "eq.{$brand['name']}",
        ]);
        if (count($products) > 0) {
            return response()->json([
                'message' => 'This brand is still used by '.count($products).' product(s). Reassign or delete those products first.',
            ], 422);
        }

        $this->supabase->delete('brands', ['id' => $brandId]);

        return response()->json(['message' => 'Brand deleted.']);
    }

    /** The signed-in staff member (created_by mirror of auth()->id()). */
    private function creatorId(Request $request): ?string
    {
        $sessionUser = \App\Http\Middleware\EnsureStaffSessionApi::user($request);

        return isset($sessionUser['id']) ? (string) $sessionUser['id'] : null;
    }

    /**
     * Whether a brand name already exists (excluding an optional brand id).
     */
    private function brandNameExists(string $name, $exceptId = null): bool
    {
        $rows = $this->supabase->query('brands', [
            'select' => 'id,name',
            'name' => 'ilike.'.urlencode($name),
        ]);
        foreach ($rows as $row) {
            if (mb_strtolower(trim($row['name'])) === mb_strtolower(trim($name))
                && ($exceptId === null || (int) $row['id'] !== (int) $exceptId)) {
                return true;
            }
        }

        return false;
    }
}
