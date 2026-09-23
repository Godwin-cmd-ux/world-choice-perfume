<?php

namespace App\Http\Controllers\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    /**
     * List all brands (with product counts) for management.
     */
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

        return view('graphic-designer.brands.index', compact('brands', 'countsUi'));
    }

    public function create()
    {
        return view('graphic-designer.brands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:51200',
            'is_active' => 'boolean',
        ]);

        if ($this->brandNameExists($validated['name'])) {
            return back()->withErrors(['name' => 'A brand with this name already exists.'])
                ->withInput();
        }

        $logoUrl = null;
        if ($request->hasFile('logo')) {
            $logoUrl = (new CloudinaryService)->upload($request->file('logo'), 'brands');
        }

        $this->supabase->insert('brands', [
            'name' => $validated['name'],
            'logo_url' => $logoUrl,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->user()->supabase_id ?? auth()->id(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('graphic-designer.brands.index')
            ->with('success', 'Brand created. It will appear in the Featured Brands section on the home page.');
    }

    public function edit($brandId)
    {
        $brand = $this->supabase->find('brands', $brandId);
        if (! $brand) {
            abort(404);
        }

        return view('graphic-designer.brands.edit', ['brand' => (object) $brand]);
    }

    public function update(Request $request, $brandId)
    {
        $brand = $this->supabase->find('brands', $brandId);
        if (! $brand) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:51200',
            'is_active' => 'boolean',
        ]);

        if ($this->brandNameExists($validated['name'], $brandId)) {
            return back()->withErrors(['name' => 'A brand with this name already exists.'])
                ->withInput();
        }

        $data = [
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->user()->supabase_id ?? auth()->id(),
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

        return redirect()->route('graphic-designer.brands.index')
            ->with('success', 'Brand updated.');
    }

    public function destroy($brandId)
    {
        $brand = $this->supabase->find('brands', $brandId);
        if (! $brand) {
            abort(404);
        }

        // Protect data integrity — brands still used by products must not vanish.
        $products = $this->supabase->query('products', [
            'select' => 'id',
            'brand' => "eq.{$brand['name']}",
        ]);
        if (count($products) > 0) {
            return back()->with('error', 'This brand is still used by '.count($products).' product(s). Reassign or delete those products first.');
        }

        $this->supabase->delete('brands', ['id' => $brandId]);

        return redirect()->route('graphic-designer.brands.index')
            ->with('success', 'Brand deleted.');
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
