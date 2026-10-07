<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * JSON twin of SuperAdmin\CashierApprovalController: the Approvals screen.
 * Same roles, same pagination, same approve/reject side effects (branch
 * activation for branch admins, notification on approval) — the backend is
 * still the only thing that changes a status.
 */
class AdminApprovalController extends Controller
{
    /** All approval-eligible staff roles (everything except super_admin). */
    private const APPROVAL_ROLES = ['cashier', 'branch_admin', 'stock_manager', 'customer_care', 'seller', 'graphic_designer'];

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $role = $request->get('role', 'all');
        if ($role !== 'all' && ! in_array($role, self::APPROVAL_ROLES, true)) {
            $role = 'all';
        }
        $status = (string) $request->get('status', 'pending');
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => (string) $perPage,
            'offset' => (string) $offset,
        ];

        if ($role === 'all') {
            $params['role'] = 'in.('.implode(',', self::APPROVAL_ROLES).')';
        } else {
            $params['role'] = "eq.{$role}";
        }

        if ($status !== 'all') {
            $params['status'] = "eq.{$status}";
        }

        $result = $this->supabase->queryWithCount('users', $params);

        $branchIds = array_unique(array_filter(array_map(fn ($u) => $u['branch_id'] ?? null, $result['data'])));
        $branchesMap = [];
        foreach ($branchIds as $bid) {
            $branch = $this->supabase->find('branches', $bid, 'id,name');
            if ($branch) {
                $branchesMap[$bid] = $branch;
            }
        }

        $users = array_map(function ($u) use ($branchesMap) {
            $u['branch'] = isset($u['branch_id'], $branchesMap[$u['branch_id']]) ? $branchesMap[$u['branch_id']] : null;

            return $u;
        }, $result['data']);

        return response()->json([
            'users' => $users,
            'total' => (int) $result['count'],
            'page' => $page,
            'per_page' => $perPage,
            'role' => $role,
        ]);
    }

    public function show($userId)
    {
        $user = $this->supabase->find('users', $userId, '*');
        if (! $user || ! in_array($user['role'] ?? '', self::APPROVAL_ROLES, true)) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $user['branch'] = null;
        if (! empty($user['branch_id'])) {
            $branch = $this->supabase->find('branches', $user['branch_id'], 'id,name,address');
            if ($branch) {
                $user['branch'] = $branch;
            }
        }

        return response()->json(['user' => $user]);
    }

    public function approve(Request $request, $userId)
    {
        $user = $this->findEligible($userId);
        if (! $user) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $this->supabase->update('users', ['status' => 'approved'], ['id' => $userId]);
        \App\Models\User::where('email', $user['email'])->update(['status' => 'approved']);

        if (($user['role'] ?? '') === 'branch_admin' && ! empty($user['branch_id'])) {
            $this->supabase->update('branches', ['is_active' => true], ['id' => $user['branch_id']]);
            \App\Models\Branch::where('id', $user['branch_id'])->update(['is_active' => true]);
        }

        $this->supabase->insert('notifications', [
            'user_id' => $user['id'],
            'title' => 'Account Approved',
            'message' => 'Your account has been approved by the Super Admin. You can now log in.',
            'type' => 'approval',
            'is_read' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => "{$user['name']} ({$user['role']}) has been approved.",
        ]);
    }

    public function reject(Request $request, $userId)
    {
        $user = $this->findEligible($userId);
        if (! $user) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $this->supabase->update('users', ['status' => 'rejected'], ['id' => $userId]);
        \App\Models\User::where('email', $user['email'])->update(['status' => 'rejected']);

        if (($user['role'] ?? '') === 'branch_admin' && ! empty($user['branch_id'])) {
            $this->supabase->update('branches', ['is_active' => false], ['id' => $user['branch_id']]);
            \App\Models\Branch::where('id', $user['branch_id'])->update(['is_active' => false]);
        }

        return response()->json([
            'message' => "{$user['name']} ({$user['role']}) has been rejected.",
        ]);
    }

    private function findEligible($userId): ?array
    {
        $user = $this->supabase->find('users', $userId);
        if (! $user || ! in_array($user['role'] ?? '', self::APPROVAL_ROLES, true)) {
            return null;
        }

        return $user;
    }
}
