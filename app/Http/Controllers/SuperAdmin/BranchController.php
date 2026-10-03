<?php

namespace App\Http\Controllers\SuperAdmin;

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

class BranchController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    public function index()
    {
        $branches = $this->supabase->query('branches', [
            'select' => '*',
            'order' => 'name.asc',
        ]);

        // Get cashier counts per branch and cast to objects for views
        $branches = collect($branches)->map(function ($branch) {
            $supabase = new SupabaseService;
            $branch['cashiers_count'] = $supabase->count('users', [
                'role' => 'eq.cashier',
                'branch_id' => "eq.{$branch['id']}",
            ]);
            $branch['users'] = collect($supabase->query('users', [
                'select' => 'id,name,email,role,status',
                'branch_id' => "eq.{$branch['id']}",
            ]))->map(fn ($u) => (object) $u);

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

        return view('super-admin.branches.create', ['admins' => collect($admins)->map(fn ($a) => (object) $a)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Access is keyed on the branch name, so a branch created with
                // one of the two exception names would silently inherit
                // privileges no one meant to give it.
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
            $cloudinaryService = new CloudinaryService;
            $profilePicture = $cloudinaryService->upload($request->file('profile_picture'), 'branches');
        }

        // A branch with no category chosen is the baseline: an autonomous
        // branch, which is what Dodoma is.
        $category = BranchCategory::from($validated['category'] ?? null);

        // Create branch in Supabase
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

        // Also create in SQLite
        $localBranch = Branch::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'category' => $category,
            'profile_picture' => $profilePicture,
            'is_active' => true,
        ]);

        // Assign admin if provided
        if (! empty($validated['admin_id']) && $branch) {
            $this->supabase->update('users', ['branch_id' => $branch['id']], ['id' => $validated['admin_id']]);
            User::where('id', $validated['admin_id'])->update(['branch_id' => $localBranch->id]);
        }

        return redirect()->route('super-admin.branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit($branchId)
    {
        $branch = $this->supabase->find('branches', $branchId);
        if (! $branch) {
            abort(404);
        }

        $admins = $this->supabase->query('users', [
            'select' => 'id,name,email',
            'role' => 'eq.branch_admin',
        ]);

        return view('super-admin.branches.edit', ['branch' => (object) $branch, 'admins' => collect($admins)->map(fn ($a) => (object) $a)]);
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
            abort(404);
        }

        // Access is keyed on the branch name, so no branch may be moved ONTO an
        // exception name. The branch that already holds one keeps it: its edit
        // form resubmits that name on every save, so refusing it here would make
        // Kinondoni and Head Quarters impossible to edit at all. Renaming either
        // of them away is still allowed — that gives up privileges rather than
        // granting them.
        $attempted = (string) $validated['name'];
        $currentName = (string) ($branch['name'] ?? '');

        if (BranchAccess::isExceptionName($attempted)
            && ! (BranchAccess::isExceptionName($currentName)
                && BranchAccess::normalise($attempted) === BranchAccess::normalise($currentName))) {
            return back()->withInput()->withErrors([
                'name' => BranchAccess::rejectionMessage($attempted),
            ]);
        }

        if ($request->hasFile('profile_picture')) {
            $cloudinaryService = new CloudinaryService;
            $validated['profile_picture'] = $cloudinaryService->upload($request->file('profile_picture'), 'branches');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_at'] = now()->toIso8601String();

        // A form that does not carry a category leaves the stored one alone,
        // so a partial submit cannot quietly reset the branch to the baseline.
        if (! empty($validated['category'])) {
            $validated['category'] = BranchCategory::from($validated['category']);
        } else {
            unset($validated['category']);
        }

        // Update in Supabase
        $this->supabase->update('branches', $validated, ['id' => $branchId]);

        // Also update in SQLite, matched on the name the row still has. This
        // used to re-read the branch after the Supabase write and match on the
        // name it had just been given, so a rename updated nothing.
        Branch::where('name', $currentName)->update($validated);

        return redirect()->route('super-admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy($branchId)
    {
        $this->supabase->update('branches', ['is_active' => false, 'updated_at' => now()->toIso8601String()], ['id' => $branchId]);

        $branch = $this->supabase->find('branches', $branchId);
        if ($branch) {
            Branch::where('name', $branch['name'])->update(['is_active' => false]);
        }

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

        return redirect()->route('super-admin.branches.index')->with('success', 'Branch deactivated.');
    }

    /**
     * Delete a branch permanently, along with everything that belongs to it.
     *
     * This is irreversible. It is offered only for a branch that has already
     * been deactivated, and that is re-checked here so a direct request cannot
     * skip the deactivate step and jump straight to the delete.
     */
    public function purge($branchId)
    {
        $branch = $this->supabase->find('branches', $branchId);
        if (! $branch) {
            abort(404);
        }

        if (! empty($branch['is_active'])) {
            return redirect()->route('super-admin.branches.index')
                ->with('error', 'Deactivate this branch before deleting it permanently.');
        }

        // Kinondoni carries cross-branch stock monitoring and Head
        // Quarters-Mikocheni carries the company mailbox with news and
        // inquiries moderation. Both are keyed on the branch NAME, so deleting
        // either would remove a company-wide capability outright and leave
        // nothing to reassign it to. Renaming is the way to give one up.
        if (BranchAccess::isExceptionName($branch['name'] ?? null)) {
            return redirect()->route('super-admin.branches.index')
                ->with('error', BranchAccess::deletionMessage((string) $branch['name']));
        }

        // Read the staff accounts BEFORE the branch row goes. users.branch_id
        // is ON DELETE SET NULL, so once the branch is gone there is nothing
        // left tying these people to it and they could never be found again.
        $staffIds = array_map(
            fn ($u) => $u['id'],
            $this->supabase->query('users', [
                'select' => 'id',
                'branch_id' => "eq.{$branchId}",
            ])
        );

        // Stock transfers reference the branch ON DELETE RESTRICT, so deleting
        // the branch row while any exist would be rejected outright. Their
        // items cascade from the transfer itself.
        $this->supabase->delete('stock_transfers', ['from_branch_id' => "eq.{$branchId}"]);
        $this->supabase->delete('stock_transfers', ['to_branch_id' => "eq.{$branchId}"]);

        // Neither of these is declared anywhere in the project's SQL, so their
        // cascade behaviour cannot be known from here. Clear them explicitly
        // rather than risk rows left pointing at a branch that no longer exists.
        $this->supabase->delete('inquiries', ['branch_id' => "eq.{$branchId}"]);
        $this->supabase->delete('news_posts', ['branch_id' => "eq.{$branchId}"]);

        // Everything else — branch_stock, stock_movements, bottle and oil
        // stock, branch_stock_varieties, customers, sales, orders, expenses,
        // cashier accounts and discrepancies — cascades from this one row.
        if (! $this->supabase->delete('branches', ['id' => $branchId])) {
            return redirect()->route('super-admin.branches.index')
                ->with('error', 'The branch could not be deleted, so nothing was removed.');
        }

        $stuckStaff = [];
        foreach ($staffIds as $staffId) {
            if (! $this->supabase->delete('users', ['id' => $staffId])) {
                $stuckStaff[] = $staffId;
            }
        }

        // The local mirror, matched on the name the row still has.
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
            return redirect()->route('super-admin.branches.index')->with(
                'error',
                'The branch and its records were deleted, but '.count($stuckStaff)
                .' staff account(s) could not be removed. They are now unassigned — delete them from Staff.'
            );
        }

        return redirect()->route('super-admin.branches.index')
            ->with('success', "Branch {$branch['name']} and all of its data were deleted permanently.");
    }
}
