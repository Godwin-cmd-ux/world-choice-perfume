<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use App\Support\BranchAccess;
use App\Support\BranchCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * JSON twin of SuperAdmin\BranchController — the same Supabase + SQLite
 * writes, the same validation rules (including the BranchAccess exception
 * names and the deactivate-before-purge rule), only the responses differ.
 */
class AdminBranchController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    /** Branch list with per-branch cashier counts and staff roster. */
    public function index()
    {
        $branches = $this->supabase->query('branches', [
            'select' => '*',
            'order' => 'name.asc',
        ]);

        $rows = [];
        foreach ($branches as $branch) {
            $branch['cashiers_count'] = $this->supabase->count('users', [
                'role' => 'eq.cashier',
                'branch_id' => "eq.{$branch['id']}",
            ]);
            $branch['users'] = $this->supabase->query('users', [
                'select' => 'id,name,email,role,status',
                'branch_id' => "eq.{$branch['id']}",
            ]);

            $rows[] = $branch;
        }

        return response()->json(['branches' => $rows]);
    }

    /** Everything the create/edit forms need: unassigned admins + categories. */
    public function options()
    {
        $admins = $this->supabase->query('users', [
            'select' => 'id,name,email',
            'role' => 'eq.branch_admin',
            'status' => 'eq.active',
            'branch_id' => 'is.null',
        ]);

        return response()->json([
            'admins' => $admins,
            'categories' => BranchCategory::all(),
        ]);
    }

    public function show($branchId)
    {
        $branch = $this->supabase->find('branches', $branchId);
        if (! $branch) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $admins = $this->supabase->query('users', [
            'select' => 'id,name,email',
            'role' => 'eq.branch_admin',
        ]);

        return response()->json(['branch' => $branch, 'admins' => $admins]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (BranchAccess::isExceptionName($value)) {
                        $fail(BranchAccess::rejectionMessage((string) $value));
                    }
                },
            ],
            'address' => 'nullable|string',
            'admin_id' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'category' => ['nullable', 'string', Rule::in(BranchCategory::all())],
            'profile_picture' => 'nullable|image|max:51200',
        ]);

        $profilePicture = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicture = (new CloudinaryService)->upload($request->file('profile_picture'), 'branches');
        }

        $category = BranchCategory::from($validated['category'] ?? null);

        $branch = $this->supabase->insert('branches', [
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'category' => $category,
            'profile_picture' => $profilePicture,
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $localBranch = Branch::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'category' => $category,
            'profile_picture' => $profilePicture,
            'is_active' => true,
        ]);

        if (! empty($validated['admin_id']) && $branch) {
            $this->supabase->update('users', ['branch_id' => $branch['id']], ['id' => $validated['admin_id']]);
            User::where('id', $validated['admin_id'])->update(['branch_id' => $localBranch->id]);
        }

        return response()->json([
            'message' => 'Branch created successfully.',
            'branch' => $branch,
        ], 201);
    }

    public function update(Request $request, $branchId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
            'category' => ['nullable', 'string', Rule::in(BranchCategory::all())],
            'profile_picture' => 'nullable|image|max:51200',
        ]);

        $branch = $this->supabase->find('branches', $branchId);
        if (! $branch) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        // Same exception-name rule as the website: a branch may keep (and
        // resubmit) the name it already holds, but may not move onto one.
        $attempted = (string) $validated['name'];
        $currentName = (string) ($branch['name'] ?? '');

        if (BranchAccess::isExceptionName($attempted)
            && ! (BranchAccess::isExceptionName($currentName)
                && BranchAccess::normalise($attempted) === BranchAccess::normalise($currentName))) {
            return response()->json([
                'message' => BranchAccess::rejectionMessage($attempted),
                'errors' => ['name' => [BranchAccess::rejectionMessage($attempted)]],
            ], 422);
        }

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = (new CloudinaryService)->upload($request->file('profile_picture'), 'branches');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_at'] = now()->toIso8601String();

        if (! empty($validated['category'])) {
            $validated['category'] = BranchCategory::from($validated['category']);
        } else {
            unset($validated['category']);
        }

        $this->supabase->update('branches', $validated, ['id' => $branchId]);
        Branch::where('name', $currentName)->update($validated);

        return response()->json(['message' => 'Branch updated successfully.']);
    }

    /** Deactivate (soft) — same as the website's delete button. */
    public function destroy($branchId)
    {
        $this->supabase->update('branches', ['is_active' => false, 'updated_at' => now()->toIso8601String()], ['id' => $branchId]);

        $branch = $this->supabase->find('branches', $branchId);
        if (! $branch) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        Branch::where('name', $branch['name'])->update(['is_active' => false]);

        (new AuditService)->recordCriticalAction(
            'branch_deactivated',
            'branch_deactivated',
            'Branch Deactivated',
            "Branch {$branch['name']} was deactivated.",
            ['branch_id' => $branchId, 'branch_name' => $branch['name'] ?? null],
            'branches',
            (string) $branchId,
            ['is_active' => true],
            ['is_active' => false]
        );

        return response()->json(['message' => 'Branch deactivated.']);
    }

    /** Permanent delete — re-checks deactivate-first and exception names. */
    public function purge($branchId)
    {
        $branch = $this->supabase->find('branches', $branchId);
        if (! $branch) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        if (! empty($branch['is_active'])) {
            return response()->json([
                'message' => 'Deactivate this branch before deleting it permanently.',
            ], 422);
        }

        if (BranchAccess::isExceptionName($branch['name'] ?? null)) {
            return response()->json([
                'message' => BranchAccess::deletionMessage((string) $branch['name']),
            ], 422);
        }

        $staffIds = array_map(
            fn ($u) => $u['id'],
            $this->supabase->query('users', [
                'select' => 'id',
                'branch_id' => "eq.{$branchId}",
            ])
        );

        $this->supabase->delete('stock_transfers', ['from_branch_id' => "eq.{$branchId}"]);
        $this->supabase->delete('stock_transfers', ['to_branch_id' => "eq.{$branchId}"]);
        $this->supabase->delete('inquiries', ['branch_id' => "eq.{$branchId}"]);
        $this->supabase->delete('news_posts', ['branch_id' => "eq.{$branchId}"]);

        if (! $this->supabase->delete('branches', ['id' => $branchId])) {
            return response()->json([
                'message' => 'The branch could not be deleted, so nothing was removed.',
            ], 500);
        }

        $stuckStaff = [];
        foreach ($staffIds as $staffId) {
            if (! $this->supabase->delete('users', ['id' => $staffId])) {
                $stuckStaff[] = $staffId;
            }
        }

        Branch::where('name', $branch['name'])->delete();

        (new AuditService)->recordCriticalAction(
            'branch_deleted',
            'branch_deleted',
            'Branch Deleted',
            "Branch {$branch['name']} was deleted permanently, with all of its data.",
            ['branch_id' => $branchId, 'branch_name' => $branch['name'] ?? null],
            'branches',
            (string) $branchId,
            ['name' => $branch['name'] ?? null, 'is_active' => false],
            []
        );

        if ($stuckStaff !== []) {
            return response()->json([
                'message' => 'The branch and its records were deleted, but '.count($stuckStaff)
                    .' staff account(s) could not be removed. They are now unassigned — delete them from Staff.',
            ]);
        }

        return response()->json([
            'message' => "Branch {$branch['name']} and all of its data were deleted permanently.",
        ]);
    }
}
