<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, user:users(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->category) {
            $params['category'] = "eq.{$request->category}";
        }

        $expenses = $this->supabase->query('expenses', $params);

        // Apply date filters in PHP
        if ($request->date_from) {
            $from = $request->date_from;
            $expenses = array_filter($expenses, fn($e) => substr($e['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->date_to) {
            $to = $request->date_to;
            $expenses = array_filter($expenses, fn($e) => substr($e['created_at'] ?? '', 0, 10) <= $to);
        }

        $expenses = array_values($expenses);
        $totalExpenses = array_sum(array_map(fn($e) => $e['amount'] ?? 0, $expenses));

        // Cast for views
        $expenses = collect($expenses)->map(function ($e) {
            if (isset($e['user']) && is_array($e['user'])) $e['user'] = (object) $e['user'];
            return (object) $e;
        });

        return view('branch-admin.expenses.index', compact('expenses', 'totalExpenses'));
    }

    public function show($expenseId)
    {
        $expense = $this->supabase->find('expenses', $expenseId, '*, user:users(id,name), branch:branches(id,name)');
        if (!$expense) abort(404);

        if (isset($expense['user']) && is_array($expense['user'])) $expense['user'] = (object) $expense['user'];
        if (isset($expense['branch']) && is_array($expense['branch'])) $expense['branch'] = (object) $expense['branch'];

        return view('branch-admin.expenses.show', ['expense' => (object) $expense]);
    }
}
