<?php

namespace App\Http\Controllers\Api\BranchAdmin;

use App\Support\BusinessDay;
use Illuminate\Http\Request;

/**
 * JSON twin of the branch-admin Expenses screens — view-only, exactly as
 * the website: cashiers commit the expenses, the admin reads them.
 *
 * The total is a running figure for the current business day: with no date
 * filter applied the window is today only, so it starts at zero after
 * midnight and climbs as expenses are recorded. Picking a date range
 * replaces the window with that range.
 */
class BaExpenseController extends BaBaseController
{
    public function index(Request $request)
    {
        $branchId = $this->ownBranchId($request);

        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $window = BusinessDay::localRangeFilter($dateFrom, $dateTo);

        $params = [
            'branch_id' => "eq.{$branchId}",
            'created_at' => $window,
        ];

        if ($request->query('category')) {
            $params['category'] = 'eq.'.$request->query('category');
        }

        // Total over every expense in the window — not just the page of rows
        // below it, which is capped for display.
        $windowRows = $this->supabase->query('expenses', $params + ['select' => 'amount']);
        $totalExpenses = array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $windowRows));

        $expenses = $this->supabase->query('expenses', $params + [
            'select' => '*, user:users(id,name)',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        return response()->json([
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
            'rangeLabel' => BusinessDay::localRangeLabel($dateFrom, $dateTo),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** View a single expense committed by a cashier of this branch. */
    public function show(Request $request, int $expenseId)
    {
        $expense = $this->supabase->find('expenses', $expenseId, '*, user:users(id,name), branch:branches(id,name)');
        if (! $expense || ($expense['branch_id'] ?? null) != $this->ownBranchId($request)) {
            abort(404, 'Expense not found.');
        }

        return response()->json(['expense' => $expense]);
    }
}
