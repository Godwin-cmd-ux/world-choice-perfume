<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    private const STAFF_ROLES = ['stock_manager', 'seller', 'customer_care', 'cashier'];

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'role' => 'in.(' . implode(',', self::STAFF_ROLES) . ')',
            'order' => 'created_at.desc',
        ];

        if ($request->role && in_array($request->role, self::STAFF_ROLES)) {
            $params['role'] = "eq.{$request->role}";
        }

        if ($request->status) {
            $params['status'] = "eq.{$request->status}";
        }

        $staff = $this->supabase->query('users', $params);

        foreach ($staff as &$member) {
            $member['total_sales'] = $this->countStaffSales($member['id'], $branchId);
            $member = (object) $member;
        }

        return view('branch-admin.staffs.index', [
            'staff' => collect($staff),
            'roles' => self::STAFF_ROLES,
        ]);
    }

    public function create()
    {
        $branchId = (int) auth()->user()->branch_id;

        $branchName = null;
        $branch = $this->supabase->find('branches', $branchId, 'id,name');
        if ($branch) {
            $branchName = $branch['name'];
        }

        return view('branch-admin.staffs.create', [
            'roles' => self::STAFF_ROLES,
            'branchName' => $branchName,
        ]);
    }

    public function store(Request $request)
    {
        $branchId = (int) auth()->user()->branch_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', self::STAFF_ROLES),
        ]);

        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return back()->withErrors(['email' => 'This email is already registered.']);
        }

        $branch = $this->supabase->find('branches', $branchId);
        if (!$branch) {
            return back()->withErrors(['email' => 'Your branch is missing from the records. Please contact support.']);
        }

        // Sync the branch to SQLite
        if (!Branch::find($branchId)) {
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
            'branch_id' => $branchId,
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
                'branch_id' => $branchId,
                'otp_verified' => false,
                'supabase_id' => $sbUser['id'],
            ]
        );

        try {
            (new \App\Services\AuditService())->recordCriticalAction(
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
            // Audit log failure should not block the action
        }

        return redirect()->route('branch-admin.staffs.index')
            ->with('success', "Staff account for {$validated['name']} created successfully. They can now log in with the password you provided.");
    }

    public function approve($userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user || $user['branch_id'] != auth()->user()->branch_id || !in_array($user['role'] ?? '', self::STAFF_ROLES)) {
            abort(403);
        }

        $this->supabase->update('users', ['status' => 'approved'], ['id' => $userId]);
        \App\Models\User::where('email', $user['email'])->update(['status' => 'approved']);

        return back()->with('success', "{$user['name']} has been approved.");
    }

    public function reject($userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user || $user['branch_id'] != auth()->user()->branch_id || !in_array($user['role'] ?? '', self::STAFF_ROLES)) {
            abort(403);
        }

        $this->supabase->update('users', ['status' => 'rejected'], ['id' => $userId]);
        \App\Models\User::where('email', $user['email'])->update(['status' => 'rejected']);

        return back()->with('success', "{$user['name']} has been rejected.");
    }

    private function countStaffSales(int $userId, int $branchId): float
    {
        $sales = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$userId}",
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
        ]);
        return array_sum(array_map(fn($s) => $s['total'] ?? 0, $sales));
    }
}