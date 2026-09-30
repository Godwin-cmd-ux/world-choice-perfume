<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Branch admin views expenses committed by cashiers in their branch.
     *
     * The total is a running figure for the current business day: with no date
     * filter applied the window is today only, so it starts at zero after
     * midnight and climbs as expenses are recorded. Picking a date range
     * replaces the window with that range.
     */
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

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

        return view('branch-admin.expenses.index', compact('expenses', 'totalExpenses') + [
            'rangeLabel' => BusinessDay::localRangeLabel($request->date_from, $request->date_to),
        ]);
    }

    /**
     * View a single expense committed by a cashier.
     */
    public function show($expenseId)
    {
        $expense = $this->supabase->find('expenses', $expenseId, '*, user:users(id,name), branch:branches(id,name)');
        if (!$expense) abort(404);

        if (isset($expense['user']) && is_array($expense['user'])) $expense['user'] = (object) $expense['user'];
        if (isset($expense['branch']) && is_array($expense['branch'])) $expense['branch'] = (object) $expense['branch'];

        return view('branch-admin.expenses.show', ['expense' => (object) $expense]);
    }
}
