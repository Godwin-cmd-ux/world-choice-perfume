<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

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