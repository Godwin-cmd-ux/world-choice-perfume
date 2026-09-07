<?php

namespace App\Http\Controllers\Cashier;

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

    /**
     * Cashier sees all expenses for their own branch.
     */
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

        $expenses = collect($expenses)->map(function ($e) {
            if (isset($e['user']) && is_array($e['user'])) $e['user'] = (object) $e['user'];
            return (object) $e;
        });

        return view('cashier.expenses.index', compact('expenses', 'totalExpenses'));
    }

    /**
     * Cashier can record a new expense for their branch.
     */
    public function create()
    {
        return view('cashier.expenses.create');
    }

    /**
     * Cashier commits a new expense for their branch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:electricity,water,transport,cleaning,packaging,other',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:10',
        ]);

        $branchId = auth()->user()->branch_id;
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

        $this->supabase->insert('expenses', [
            'branch_id' => $branchId,
            'user_id' => $supabaseUserId,
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'date' => now()->toDateString(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->supabase->insert('audit_logs', [
            'user_id' => $supabaseUserId,
            'action' => 'expense_created',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('cashier.expenses.index')->with('success', 'Expense recorded successfully.');
    }
}
