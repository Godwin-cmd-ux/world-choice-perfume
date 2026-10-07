<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * JSON twin of SuperAdmin\StaffController — staff list with filters/search,
 * account creation, detail with sales/expenses/audit trails, block/unblock,
 * arbitrary status changes and deletion. All writes go through the same
 * Supabase + SQLite mirror and audit log as the website.
 */
class AdminStaffController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $params = [
            'select' => '*, branch:branches(id,name)',
            'order' => 'created_at.desc',
            'limit' => 200,
        ];

        if ($request->role) {
            $params['role'] = "eq.{$request->role}";
        }
        if ($request->status) {
            $params['status'] = "eq.{$request->status}";
        }

        $users = $this->supabase->query('users', $params);

        if ($request->search) {
            $search = strtolower($request->search);
            $users = array_filter($users, function ($u) use ($search) {
                return str_contains(strtolower($u['name'] ?? ''), $search)
                    || str_contains(strtolower($u['email'] ?? ''), $search)
                    || str_contains(strtolower($u['phone'] ?? ''), $search);
            });
        }

        return response()->json(['users' => array_values($users)]);
    }

    /** Branches + roles for the create form. */
    public function options()
    {
        $branches = $this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]);

        return response()->json([
            'branches' => $branches,
            'roles' => ['branch_admin', 'cashier', 'stock_manager', 'seller', 'customer_care', 'graphic_designer'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:branch_admin,cashier,stock_manager,seller,customer_care,graphic_designer',
            'branch_id' => 'required',
        ]);

        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return response()->json([
                'message' => 'This email is already registered.',
                'errors' => ['email' => ['This email is already registered.']],
            ], 422);
        }

        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (! $branch) {
            return response()->json([
                'message' => 'Selected branch does not exist.',
                'errors' => ['branch_id' => ['Selected branch does not exist.']],
            ], 422);
        }

        if (! Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        $hashedPassword = Hash::make($validated['password']);

        $sbUser = $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => $validated['role'],
            'status' => 'active',
            'branch_id' => (int) $validated['branch_id'],
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $sbUser || ! isset($sbUser['id'])) {
            return response()->json([
                'message' => 'Failed to create the staff account. Please contact support.',
                'errors' => ['email' => ['Failed to create the staff account. Please contact support.']],
            ], 500);
        }

        User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => $validated['role'],
                'status' => 'active',
                'branch_id' => (int) $validated['branch_id'],
                'otp_verified' => false,
                'supabase_id' => $sbUser['id'],
            ]
        );

        try {
            (new \App\Services\AuditService())->recordCriticalAction(
                'staff_created',
                'created_user',
                'Staff Created',
                "Staff {$validated['name']} ({$validated['role']}) registered into branch #{$validated['branch_id']} by super admin.",
                ['user_name' => $validated['name'], 'role' => $validated['role'], 'branch_id' => $validated['branch_id']],
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
            'user' => $sbUser,
        ], 201);
    }

    public function show($userId)
    {
        $user = $this->supabase->find('users', $userId, '*, branch:branches(id,name,address)');
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $sales = $this->supabase->query('sales', [
            'select' => 'id,sale_number,total,payment_method,payment_summary,created_at,customer:customers(name,phone)',
            'cashier_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 20,
        ]);

        $expenses = $this->supabase->query('expenses', [
            'select' => 'id,category,amount,description,created_at',
            'user_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 20,
        ]);

        $auditLogs = $this->supabase->query('audit_logs', [
            'select' => 'id,action,created_at',
            'user_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 20,
        ]);

        return response()->json([
            'user' => $user,
            'sales' => $sales,
            'expenses' => $expenses,
            'audit_logs' => $auditLogs,
            'total_sales' => array_sum(array_map(fn ($s) => $s['total'] ?? 0, $sales)),
            'total_expenses' => array_sum(array_map(fn ($e) => $e['amount'] ?? 0, $expenses)),
        ]);
    }

    public function toggleStatus($userId, Request $request)
    {
        $user = $this->supabase->find('users', $userId);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $currentStatus = $user['status'] ?? 'active';
        $newStatus = $currentStatus === 'blocked' ? 'active' : 'blocked';

        $result = $this->supabase->update('users', [
            'status' => $newStatus,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $userId]);

        if (empty($result)) {
            return response()->json([
                'message' => $newStatus === 'blocked'
                    ? 'Could not block this user: the database rejected the status update. Ensure the "blocked" value exists in the user_status enum (run SUPABASE_USER_STATUS_FIX.sql in the Supabase SQL Editor), then try again.'
                    : 'Could not unblock this user. Please try again.',
            ], 500);
        }

        $this->audit($request, $newStatus === 'blocked' ? 'staff_blocked' : 'staff_unblocked',
            $newStatus === 'blocked' ? 'blocked_user' : 'unblocked_user',
            $newStatus === 'blocked' ? 'Staff Blocked' : 'Staff Unblocked',
            "Staff {$user['name']} was {$newStatus}.",
            ['user_id' => $userId, 'user_name' => $user['name'], 'status' => $newStatus],
            ['status' => $currentStatus], ['status' => $newStatus]);

        return response()->json(['message' => "User has been {$newStatus}."]);
    }

    public function changeStatus(Request $request, $userId)
    {
        $request->validate([
            'status' => 'required|in:active,pending,approved,rejected,blocked',
        ]);

        $user = $this->supabase->find('users', $userId);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (($user['role'] ?? '') === 'super_admin') {
            return response()->json(['message' => 'The super admin status cannot be changed.'], 422);
        }

        $currentStatus = $user['status'] ?? 'active';
        $newStatus = $request->status;

        if ($currentStatus === $newStatus) {
            return response()->json(['message' => "{$user['name']} status is already {$newStatus}."]);
        }

        $result = $this->supabase->update('users', [
            'status' => $newStatus,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $userId]);

        if (empty($result)) {
            return response()->json([
                'message' => "Could not change {$user['name']} status to {$newStatus}: the database rejected the update. Ensure the \"blocked\" value exists in the user_status enum (run SUPABASE_USER_STATUS_FIX.sql in the Supabase SQL Editor), then try again.",
            ], 500);
        }

        try {
            \App\Models\User::where('email', $user['email'])->update(['status' => $newStatus]);
        } catch (\Exception $e) {
            // Local sync failure should not block the action.
        }

        $this->audit($request, 'staff_status_changed', 'changed_user_status', 'Staff Status Changed',
            "Staff {$user['name']} status changed from {$currentStatus} to {$newStatus}.",
            ['user_id' => $userId, 'user_name' => $user['name'], 'before' => $currentStatus, 'after' => $newStatus],
            ['status' => $currentStatus], ['status' => $newStatus]);

        return response()->json([
            'message' => "{$user['name']} status changed from {$currentStatus} to {$newStatus}.",
        ]);
    }

    public function destroy($userId, Request $request)
    {
        $user = $this->supabase->find('users', $userId);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $this->supabase->delete('users', ['id' => $userId]);

        $this->audit($request, 'staff_deleted', 'deleted_user', 'Staff Deleted',
            "Staff {$user['name']} (".($user['role'] ?? 'staff').') was deleted.',
            ['user_id' => $userId, 'user_name' => $user['name'], 'role' => $user['role'] ?? null],
            ['status' => $user['status'] ?? 'active', 'name' => $user['name']], []);

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /** Audit entry attributed to the session's Super Admin, never blocking. */
    private function audit(Request $request, string $action, string $category, string $title, string $description, array $context, array $before, array $after): void
    {
        try {
            $sessionUser = $request->attributes->get('staff_user');
            $adminUserId = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;
            if (! $adminUserId) {
                return;
            }

            $this->supabase->insert('audit_logs', [
                'user_id' => $adminUserId,
                'action' => $action.'_'.($context['user_id'] ?? ''),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            (new \App\Services\AuditService())->recordCriticalAction(
                $action, $category, $title, $description, $context,
                'users', (string) ($context['user_id'] ?? ''), $before, $after
            );
        } catch (\Exception $e) {
            // Audit log failure should not block the action.
        }
    }
}
