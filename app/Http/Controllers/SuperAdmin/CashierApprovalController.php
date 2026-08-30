<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class CashierApprovalController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Show pending approvals for both cashiers and branch admins
     */
    public function index(Request $request)
    {
        $role = $request->get('role', 'cashier');
        $status = $request->get('status', 'pending');
        $page = (int) $request->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Build params carefully - PostgREST needs string values for limit/offset
        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => (string) $perPage,
            'offset' => (string) $offset,
            'role' => "eq.{$role}",
        ];

        if ($status !== 'all') {
            $params['status'] = "eq.{$status}";
        }

        $result = $this->supabase->queryWithCount('users', $params);

        // Now fetch branch names separately if needed (avoid join issues)
        $branchIds = array_unique(array_filter(array_map(fn($u) => $u['branch_id'] ?? null, $result['data'])));
        $branchesMap = [];
        foreach ($branchIds as $bid) {
            $branch = $this->supabase->find('branches', $bid, 'id,name');
            if ($branch) {
                $branchesMap[$bid] = $branch;
            }
        }

        // Cast each user to objects and attach branch info
        $users = collect($result['data'])->map(function ($u) use ($branchesMap) {
            $u['branch'] = isset($u['branch_id']) && isset($branchesMap[$u['branch_id']])
                ? (object) $branchesMap[$u['branch_id']]
                : null;
            return (object) $u;
        });

        // Create a simple paginator
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $users,
            $result['count'],
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('super-admin.cashiers.index', ['users' => $paginator, 'role' => $role]);
    }

    /**
     * Show a single user's details
     */
    public function show($userId)
    {
        $user = $this->supabase->find('users', $userId, '*');
        if (!$user || !in_array($user['role'] ?? '', ['cashier', 'branch_admin'])) {
            abort(404);
        }

        // Fetch branch separately
        $user['branch'] = null;
        if (!empty($user['branch_id'])) {
            $branch = $this->supabase->find('branches', $user['branch_id'], 'id,name,address');
            if ($branch) $user['branch'] = (object) $branch;
        }

        return view('super-admin.cashiers.show', ['cashier' => (object) $user]);
    }

    /**
     * Approve a user
     */
    public function approve(Request $request, $userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user || !in_array($user['role'] ?? '', ['cashier', 'branch_admin'])) {
            abort(404);
        }

        // Update status in Supabase
        $this->supabase->update('users', ['status' => 'approved'], ['id' => $userId]);

        // Also update SQLite local record
        \App\Models\User::where('email', $user['email'])->update(['status' => 'approved']);

        // If branch admin, also activate their branch
        if (($user['role'] ?? '') === 'branch_admin' && !empty($user['branch_id'])) {
            $this->supabase->update('branches', ['is_active' => true], ['id' => $user['branch_id']]);
            \App\Models\Branch::where('id', $user['branch_id'])->update(['is_active' => true]);
        }

        // Create notification in Supabase
        $this->supabase->insert('notifications', [
            'user_id' => $user['id'],
            'title' => 'Account Approved',
            'message' => "Your account has been approved by the Super Admin. You can now log in.",
            'type' => 'approval',
            'is_read' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return back()->with('success', "{$user['name']} ({$user['role']}) has been approved.");
    }

    /**
     * Reject a user
     */
    public function reject(Request $request, $userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user || !in_array($user['role'] ?? '', ['cashier', 'branch_admin'])) {
            abort(404);
        }

        $this->supabase->update('users', ['status' => 'rejected'], ['id' => $userId]);
        \App\Models\User::where('email', $user['email'])->update(['status' => 'rejected']);

        if (($user['role'] ?? '') === 'branch_admin' && !empty($user['branch_id'])) {
            $this->supabase->update('branches', ['is_active' => false], ['id' => $user['branch_id']]);
            \App\Models\Branch::where('id', $user['branch_id'])->update(['is_active' => false]);
        }

        return back()->with('success', "{$user['name']} ({$user['role']}) has been rejected.");
    }
}
