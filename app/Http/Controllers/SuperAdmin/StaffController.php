<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

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
            }
        } catch (\Exception $e) {
            // Audit log failure should not block the action
        }

        return back()->with('success', "User has been {$newStatus}.");
    }

    public function destroy($userId)
    {
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
            }
        } catch (\Exception $e) {
            // Audit log failure should not block the action
        }

        return redirect()->route('super-admin.staff.index')->with('success', 'User deleted successfully.');
    }
}
