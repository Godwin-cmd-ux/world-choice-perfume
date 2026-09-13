<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
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

        $users = array_values($users);

        $users = collect($users)->map(function ($u) {
            if (isset($u['branch']) && is_array($u['branch'])) $u['branch'] = (object) $u['branch'];
            return (object) $u;
        });

        return view('super-admin.staff.index', ['users' => $users]);
    }

    public function create()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        $roles = ['branch_admin', 'cashier', 'stock_manager', 'seller', 'customer_care', 'graphic_designer'];

        return view('super-admin.staff.create', ['branches' => $branches, 'roles' => $roles]);
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
            return back()->withErrors(['email' => 'This email is already registered.']);
        }

        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (!$branch) {
            return back()->withErrors(['branch_id' => 'Selected branch does not exist.']);
        }

        // Sync the branch to SQLite
        if (!Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        $hashedPassword = Hash::make($validated['password']);

        // Create in Supabase (primary)
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

        if (!$sbUser || !isset($sbUser['id'])) {
            return back()->withErrors(['email' => 'Failed to create the staff account. Please contact support.']);
        }

        // Also create/update in SQLite so Auth::login() works
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
            // Audit log failure should not block the action
        }

        return redirect()->route('super-admin.staff.index')
            ->with('success', "Staff account for {$validated['name']} created successfully. They can now log in with the password you provided.");
    }

    public function show($userId)
    {
        $user = $this->supabase->find('users', $userId, '*, branch:branches(id,name,address)');
        if (!$user) abort(404);

        if (isset($user['branch']) && is_array($user['branch'])) $user['branch'] = (object) $user['branch'];

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

        $sales = collect($sales)->map(function ($s) {
            if (isset($s['customer']) && is_array($s['customer'])) $s['customer'] = (object) $s['customer'];
            return (object) $s;
        });
        $expenses = collect($expenses)->map(fn($e) => (object) $e);
        $auditLogs = collect($auditLogs)->map(fn($l) => (object) $l);

        $totalSales = $sales->sum('total');
        $totalExpenses = $expenses->sum('amount');

        return view('super-admin.staff.show', [
            'user' => (object) $user,
            'sales' => $sales,
            'expenses' => $expenses,
            'auditLogs' => $auditLogs,
            'totalSales' => $totalSales,
            'totalExpenses' => $totalExpenses,
        ]);
    }

    public function toggleStatus($userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user) abort(404);

        $currentStatus = $user['status'] ?? 'active';
        $newStatus = $currentStatus === 'blocked' ? 'active' : 'blocked';

        // Update user status in Supabase
        $result = $this->supabase->update('users', [
            'status' => $newStatus,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $userId]);

        if (empty($result)) {
            return back()->with(
                'error',
                $newStatus === 'blocked'
                    ? 'Could not block this user: the database rejected the status update. Ensure the "blocked" value exists in the user_status enum (run SUPABASE_USER_STATUS_FIX.sql in the Supabase SQL Editor), then try again.'
                    : 'Could not unblock this user. Please try again.'
            );
        }

        // Audit log — wrapped in try/catch so failure doesn't block the action
        try {
            $adminUserId = auth()->user()->supabase_id ?? auth()->id();
            if ($adminUserId) {
                $this->supabase->insert('audit_logs', [
                    'user_id' => $adminUserId,
                    'action' => $newStatus === 'blocked' ? "blocked_user_{$userId}" : "unblocked_user_{$userId}",
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);

                (new \App\Services\AuditService())->recordCriticalAction(
                    $newStatus === 'blocked' ? 'staff_blocked' : 'staff_unblocked',
                    $newStatus === 'blocked' ? 'blocked_user' : 'unblocked_user',
                    $newStatus === 'blocked' ? 'Staff Blocked' : 'Staff Unblocked',
                    "Staff {$user['name']} was {$newStatus}.",
                    ['user_id' => $userId, 'user_name' => $user['name'], 'status' => $newStatus],
                    'users',
                    (string) $userId,
                    ['status' => $currentStatus],
                    ['status' => $newStatus]
                );
            }
        } catch (\Exception $e) {
            // Audit log failure should not block the action
        }

        return back()->with('success', "User has been {$newStatus}.");
    }

    /**
     * Change a user's status to any allowed value (active, pending, approved,
     * blocked, rejected).
     */
    public function changeStatus(Request $request, $userId)
    {
        $request->validate([
            'status' => 'required|in:active,pending,approved,rejected,blocked',
        ]);

        $user = $this->supabase->find('users', $userId);
        if (!$user) abort(404);

        if (($user['role'] ?? '') === 'super_admin') {
            return back()->with('error', 'The super admin status cannot be changed.');
        }

        $currentStatus = $user['status'] ?? 'active';
        $newStatus = $request->status;

        if ($currentStatus === $newStatus) {
            return back()->with('success', "{$user['name']} status is already {$newStatus}.");
        }

        // Update user status in Supabase
        $result = $this->supabase->update('users', [
            'status' => $newStatus,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $userId]);

        if (empty($result)) {
            return back()->with(
                'error',
                "Could not change {$user['name']} status to {$newStatus}: the database rejected the update. Ensure the \"blocked\" value exists in the user_status enum (run SUPABASE_USER_STATUS_FIX.sql in the Supabase SQL Editor), then try again."
            );
        }

        // Keep the local (SQLite) record in sync
        try {
            \App\Models\User::where('email', $user['email'])->update(['status' => $newStatus]);
        } catch (\Exception $e) {
            // Local sync failure should not block the action
        }

        // Audit log + admin notification
        try {
            $adminUserId = auth()->user()->supabase_id ?? auth()->id();
            if ($adminUserId) {
                (new \App\Services\AuditService())->recordCriticalAction(
                    'staff_status_changed',
                    'changed_user_status',
                    'Staff Status Changed',
                    "Staff {$user['name']} status changed from {$currentStatus} to {$newStatus}.",
                    ['user_id' => $userId, 'user_name' => $user['name'], 'before' => $currentStatus, 'after' => $newStatus],
                    'users',
                    (string) $userId,
                    ['status' => $currentStatus],
                    ['status' => $newStatus]
                );
            }
        } catch (\Exception $e) {
            // Audit log failure should not block the action
        }

        return back()->with('success', "{$user['name']} status changed from {$currentStatus} to {$newStatus}.");
    }

    public function destroy($userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user) abort(404);

        $this->supabase->delete('users', ['id' => $userId]);

        try {
            $adminUserId = auth()->user()->supabase_id ?? auth()->id();
            if ($adminUserId) {
                $this->supabase->insert('audit_logs', [
                    'user_id' => $adminUserId,
                    'action' => "deleted_user_{$userId}",
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);

                (new \App\Services\AuditService())->recordCriticalAction(
                    'staff_deleted',
                    'deleted_user',
                    'Staff Deleted',
                    "Staff {$user['name']} (" . ($user['role'] ?? 'staff') . ") was deleted.",
                    ['user_id' => $userId, 'user_name' => $user['name'], 'role' => $user['role'] ?? null],
                    'users',
                    (string) $userId,
                    ['status' => $user['status'] ?? 'active', 'name' => $user['name']],
                    []
                );
            }
        } catch (\Exception $e) {
            // Audit log failure should not block the action
        }

        return redirect()->route('super-admin.staff.index')->with('success', 'User deleted successfully.');
    }
}
