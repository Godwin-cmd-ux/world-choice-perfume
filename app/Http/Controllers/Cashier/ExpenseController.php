<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Services\CashierScope;
use App\Services\SupabaseService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private SupabaseService $supabase;
    private CashierScope $scope;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->scope = new CashierScope($this->supabase);
    }

    /**
     * Cashier sees all expenses for their own branch (or monitored branch in cross-branch mode).
     *
     * The total is a running figure for the current business day: with no date
     * filter applied the window is today only, so it starts at zero after
     * midnight and climbs as expenses are recorded. Picking a date range
     * replaces the window with that range.
     */
    public function index(Request $request)
    {
        $branchId = $this->scope->activeBranchId();
        $inCrossBranch = $this->scope->inCrossBranchMode();

        $window = BusinessDay::localRangeFilter($request->date_from, $request->date_to);

        $params = [
            'branch_id' => "eq.{$branchId}",
            'created_at' => $window,
        ];

        if ($request->category) {
            $params['category'] = "eq.{$request->category}";
        }

        // Total over every expense in the window — not just the page of rows
        // below it, which is capped for display.
        $windowTotal = $this->supabase->query('expenses', $params + ['select' => 'amount']);
        $totalExpenses = array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $windowTotal));

        $listParams = $params + [
            'select' => '*, user:users(id,name)',
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        $expenses = collect($this->supabase->query('expenses', $listParams))
            ->map(function ($e) {
                if (isset($e['user']) && is_array($e['user'])) $e['user'] = (object) $e['user'];
                return (object) $e;
            });

        return view('cashier.expenses.index', compact('expenses', 'totalExpenses') + [
            'rangeLabel' => BusinessDay::localRangeLabel($request->date_from, $request->date_to),
            'inCrossBranch' => $inCrossBranch,
            'activeBranchName' => $this->scope->activeBranchName(),
        ]);
    }

    /**
     * Cashier views the details of an expense from their active branch.
     */
    public function show($expenseId)
    {
        $expense = $this->findBranchExpense(
            $expenseId,
            '*, user:users(id,name), branch:branches(id,name)'
        );
        if (!$expense) {
            abort(404);
        }

        if (isset($expense['user']) && is_array($expense['user'])) $expense['user'] = (object) $expense['user'];
        if (isset($expense['branch']) && is_array($expense['branch'])) $expense['branch'] = (object) $expense['branch'];

        return view('cashier.expenses.show', ['expense' => (object) $expense] + [
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
            'activeBranchName' => $this->scope->activeBranchName(),
        ]);
    }

    /**
     * Cashier can record a new expense for their branch.
     */
    public function create()
    {
        if ($this->scope->inCrossBranchMode()) {
            return redirect()->route('cashier.expenses.index')
                ->with('error', 'Expenses cannot be recorded while monitoring another branch. Exit the branch to make changes.');
        }

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

    public function edit($expenseId)
    {
        if ($this->scope->inCrossBranchMode()) {
            return redirect()->route('cashier.expenses.index')
                ->with('error', 'Expenses cannot be edited while monitoring another branch. Exit the branch to make changes.');
        }

        $expense = $this->findBranchExpense($expenseId);
        if (!$expense) {
            abort(404);
        }

        return view('cashier.expenses.edit', ['expense' => (object) $expense]);
    }

    public function update(Request $request, $expenseId)
    {
        if ($this->scope->inCrossBranchMode()) {
            return redirect()->route('cashier.expenses.index')
                ->with('error', 'Expenses cannot be edited while monitoring another branch. Exit the branch to make changes.');
        }

        $expense = $this->findBranchExpense($expenseId);
        if (!$expense) {
            abort(404);
        }

        $validated = $request->validate([
            'category' => 'required|in:electricity,water,transport,cleaning,packaging,other',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:10',
        ]);

        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

        $this->supabase->update('expenses', [
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $expenseId]);

        $this->supabase->insert('audit_logs', [
            'user_id' => $supabaseUserId,
            'action' => 'expense_updated',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('cashier.expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy($expenseId)
    {
        if ($this->scope->inCrossBranchMode()) {
            return redirect()->route('cashier.expenses.index')
                ->with('error', 'Expenses cannot be deleted while monitoring another branch. Exit the branch to make changes.');
        }

        $expense = $this->findBranchExpense($expenseId);
        if (!$expense) {
            abort(404);
        }

        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

        $this->supabase->delete('expenses', ['id' => $expenseId]);

        $this->supabase->insert('audit_logs', [
            'user_id' => $supabaseUserId,
            'action' => 'expense_deleted',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('cashier.expenses.index')->with('success', 'Expense deleted successfully.');
    }

    private function findBranchExpense($expenseId, string $select = '*'): ?array
    {
        $branchId = $this->scope->activeBranchId();
        $expense = $this->supabase->find('expenses', $expenseId, $select);
        if (!$expense) {
            return null;
        }

        if ((string) ($expense['branch_id'] ?? '') !== (string) $branchId) {
            return null;
        }

        return $expense;
    }
}
