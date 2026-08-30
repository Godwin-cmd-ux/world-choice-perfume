<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        $branches = $this->supabase->query('branches', [
            'select' => '*',
            'order' => 'name.asc',
        ]);

        // Get cashier counts per branch and cast to objects for views
        $branches = collect($branches)->map(function ($branch) {
            $supabase = new SupabaseService();
            $branch['cashiers_count'] = $supabase->count('users', [
                'role' => 'eq.cashier',
                'branch_id' => "eq.{$branch['id']}",
            ]);
            $branch['users'] = collect($supabase->query('users', [
                'select' => 'id,name,email,role,status',
                'branch_id' => "eq.{$branch['id']}",
            ]))->map(fn($u) => (object) $u);
            return (object) $branch;
        });

        return view('super-admin.branches.index', compact('branches'));
    }

    public function create()
    {
        // Get branch admins without a branch
        $admins = $this->supabase->query('users', [
            'select' => 'id,name,email',
            'role' => 'eq.branch_admin',
            'status' => 'eq.active',
            'branch_id' => 'is.null',
        ]);

        return view('super-admin.branches.create', ['admins' => collect($admins)->map(fn($a) => (object) $a)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'admin_id' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        $profilePicture = null;
        if ($request->hasFile('profile_picture')) {
            $cloudinaryService = new CloudinaryService();
            $profilePicture = $cloudinaryService->upload($request->file('profile_picture'), 'branches');
        }

        // Create branch in Supabase
        $branch = $this->supabase->insert('branches', [
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'profile_picture' => $profilePicture,
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Also create in SQLite
        $localBranch = \App\Models\Branch::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'profile_picture' => $profilePicture,
            'is_active' => true,
        ]);

        // Assign admin if provided
        if (!empty($validated['admin_id']) && $branch) {
            $this->supabase->update('users', ['branch_id' => $branch['id']], ['id' => $validated['admin_id']]);
            \App\Models\User::where('id', $validated['admin_id'])->update(['branch_id' => $localBranch->id]);
        }

        return redirect()->route('super-admin.branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit($branchId)
    {
        $branch = $this->supabase->find('branches', $branchId);
        if (!$branch) abort(404);

        $admins = $this->supabase->query('users', [
            'select' => 'id,name,email',
            'role' => 'eq.branch_admin',
        ]);

        return view('super-admin.branches.edit', ['branch' => (object) $branch, 'admins' => collect($admins)->map(fn($a) => (object) $a)]);
    }

    public function update(Request $request, $branchId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $cloudinaryService = new CloudinaryService();
            $validated['profile_picture'] = $cloudinaryService->upload($request->file('profile_picture'), 'branches');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_at'] = now()->toIso8601String();

        // Update in Supabase
        $this->supabase->update('branches', $validated, ['id' => $branchId]);

        // Also update in SQLite by matching name
        $branch = $this->supabase->find('branches', $branchId);
        if ($branch) {
            \App\Models\Branch::where('name', $branch['name'])->update($validated);
        }

        return redirect()->route('super-admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy($branchId)
    {
        $this->supabase->update('branches', ['is_active' => false, 'updated_at' => now()->toIso8601String()], ['id' => $branchId]);

        $branch = $this->supabase->find('branches', $branchId);
        if ($branch) {
            \App\Models\Branch::where('name', $branch['name'])->update(['is_active' => false]);
        }

        return redirect()->route('super-admin.branches.index')->with('success', 'Branch deactivated.');
    }
}
