<?php

namespace App\Http\Controllers\Api\BranchAdmin;

use App\Models\Branch;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * JSON twin of the branch-admin Staff screens: list the branch's staff
 * (stock manager / seller / customer care / cashier) with each member's
 * sales total, register a new member into this branch, and approve or
 * reject pending accounts. Everything is branch-scoped — an admin can only
 * ever see, create or status-change members of their own branch.
 */
class BaStaffController extends BaBaseController
{
    private const STAFF_ROLES = ['stock_manager', 'seller', 'customer_care', 'cashier'];

    public function index(Request $request)
    {
        $branchId = $this->ownBranchId($request);

        $params = [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'role' => 'in.('.implode(',', self::STAFF_ROLES).')',
            'order' => 'created_at.desc',
        ];

        $role = (string) $request->query('role', '');
        if ($role !== '' && in_array($role, self::STAFF_ROLES, true)) {
            $params['role'] = "eq.{$role}";
        }

        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $params['status'] = "eq.{$status}";
        }

        $staff = $this->supabase->query('users', $params);

        foreach ($staff as &$member) {
            $member['total_sales'] = $this->countStaffSales((int) $member['id'], $branchId);
        }
        unset($member);

        return response()->json([
            'staff' => $staff,
            'roles' => self::STAFF_ROLES,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** The staff form's data: the branch name and the assignable roles. */
    public function create(Request $request)
    {
        $branchId = $this->ownBranchId($request);
        $branch = $this->supabase->find('branches', $branchId, 'id,name');

        return response()->json([
            'roles' => self::STAFF_ROLES,
            'branchName' => $branch['name'] ?? null,
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function store(Request $request)
    {
        $branchId = $this->ownBranchId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:'.implode(',', self::STAFF_ROLES),
        ]);

        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            $this->fail(['email' => 'This email is already registered.']);
        }

        $branch = $this->supabase->find('branches', $branchId);
        if (! $branch) {
            $this->fail(['email' => 'Your branch is missing from the records. Please contact support.']);
        }

        // Sync the branch to SQLite so Auth::login() resolves it.
        if (! Branch::find($branchId)) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        $hashedPassword = Hash::make($validated['password']);

        // Create in Supabase (primary).
        $sbUser = $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => $validated['role'],
            'status' => 'active',
            'branch_id' => $branchId,
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $sbUser || ! isset($sbUser['id'])) {
            $this->fail(['email' => 'Failed to create the staff account. Please contact support.']);
        }

        // Also create/update in SQLite so Auth::login() works.
        User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => $validated['role'],
                'status' => 'active',
                'branch_id' => $branchId,
                'otp_verified' => false,
                'supabase_id' => $sbUser['id'],
            ]
        );

        try {
            (new AuditService())->recordCriticalAction(
                'staff_created',
                'created_user',
                'Staff Created',
                "Staff {$validated['name']} ({$validated['role']}) registered into branch #{$branchId} by branch admin.",
                ['user_name' => $validated['name'], 'role' => $validated['role'], 'branch_id' => $branchId],
                'users',
                (string) $sbUser['id'],
                [],
                ['status' => 'active']
            );
        } catch (\Exception $e) {
            // Audit log failure should not block the action.
        }

        return response()->json([
            'message' => "Staff account for {$validated['name']} created successfully. They can now log in with the password you provided.",
            'staff' => $sbUser,
        ]);
    }

    public function approve(Request $request, int $userId)
    {
        return $this->changeStatus($request, $userId, 'approved');
    }

    public function reject(Request $request, int $userId)
    {
        return $this->changeStatus($request, $userId, 'rejected');
    }

    /** Shared status flip with the website's branch/role guards (403). */
    private function changeStatus(Request $request, int $userId, string $status)
    {
        $user = $this->supabase->find('users', $userId);
        if (! $user
            || $user['branch_id'] != $this->ownBranchId($request)
            || ! in_array($user['role'] ?? '', self::STAFF_ROLES, true)) {
            abort(403);
        }

        $this->supabase->update('users', ['status' => $status], ['id' => $userId]);
        User::where('email', $user['email'])->update(['status' => $status]);

        $verb = $status === 'approved' ? 'approved' : 'rejected';

        return response()->json(['message' => "{$user['name']} has been {$verb}."]);
    }

    private function countStaffSales(int $userId, int $branchId): float
    {
        $sales = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$userId}",
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
        ]);

        return (float) array_sum(array_map(fn ($s) => $s['total'] ?? 0, $sales));
    }
}
