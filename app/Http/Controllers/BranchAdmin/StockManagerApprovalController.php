<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class StockManagerApprovalController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        $branchId = auth()->user()->branch_id;

        $stockManagers = $this->supabase->query('users', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'role' => 'eq.stock_manager',
            'order' => 'created_at.desc',
        ]);

        return view('branch-admin.stock-managers.index', [
            'stockManagers' => collect($stockManagers)->map(fn($u) => (object) $u)
        ]);
    }

    public function approve($userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user || $user['branch_id'] != auth()->user()->branch_id || $user['role'] !== 'stock_manager') {
            abort(403);
        }

        $this->supabase->update('users', ['status' => 'approved'], ['id' => $userId]);
        \App\Models\User::where('email', $user['email'])->update(['status' => 'approved']);

        return back()->with('success', "{$user['name']} has been approved as Stock Manager.");
    }

    public function reject($userId)
    {
        $user = $this->supabase->find('users', $userId);
        if (!$user || $user['branch_id'] != auth()->user()->branch_id || $user['role'] !== 'stock_manager') {
            abort(403);
        }

        $this->supabase->update('users', ['status' => 'rejected'], ['id' => $userId]);
        \App\Models\User::where('email', $user['email'])->update(['status' => 'rejected']);

        return back()->with('success', "{$user['name']} has been rejected.");
    }
}
