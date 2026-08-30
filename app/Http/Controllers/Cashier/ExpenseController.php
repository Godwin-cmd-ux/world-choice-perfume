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

    public function index(Request $request)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $params = [
            'select' => '*, branch:branches(id,name)',
            'user_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

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

        $expenses = collect($expenses)->map(function ($e) {
            if (isset($e['branch']) && is_array($e['branch'])) $e['branch'] = (object) $e['branch'];
            return (object) $e;
        });

        return view('cashier.expenses.index', compact('expenses', 'totalExpenses'));
    }

    public function create()
    {
        return view('cashier.expenses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:electricity,water,transport,cleaning,packaging,other',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:10',
        ]);

        $expense = $this->supabase->insert('expenses', [
            'branch_id' => auth()->user()->branch_id,
            'user_id' => auth()->user()->supabase_id ?? auth()->id(),
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'date' => now()->toDateString(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Audit log
        $this->supabase->insert('audit_logs', [
            'user_id' => auth()->user()->supabase_id ?? auth()->id(),
            'action' => 'expense_created',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('cashier.expenses.index')->with('success', 'Expense recorded successfully.');
    }
}
