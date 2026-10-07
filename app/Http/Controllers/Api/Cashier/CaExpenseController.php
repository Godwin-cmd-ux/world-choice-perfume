<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Support\BusinessDay;
use Illuminate\Http\Request;

/**
 * JSON twin of the cashier Expenses screens — the only role that COMMITS
 * expenses (other roles only view them). The listing follows the active
 * branch: the cashier's own branch, or the monitored branch while an HQ
 * monitor watches it. The total is a running figure for the current
 * business day unless a date range replaces the window, exactly like the
 * website.
 *
 * Recording an expense while monitoring another branch is refused — the
 * website's readonly middleware blocks it, and assertNotMonitoring() does
 * the same here.
 */
class CaExpenseController extends CaBaseController
{
    /** The categories the website's store accepts (hardcoded in the rule). */
    private const CATEGORIES = ['electricity', 'water', 'transport', 'cleaning', 'packaging', 'other'];

    public function index(Request $request)
    {
        $branchId = $this->activeBranchId($request);

        $window = BusinessDay::localRangeFilter(
            $request->query('date_from'),
            $request->query('date_to')
        );

        $params = [
            'branch_id' => "eq.{$branchId}",
            'created_at' => $window,
        ];

        if ($request->query('category')) {
            $params['category'] = 'eq.'.$request->query('category');
        }

        // Total over every expense in the window — not just the page of rows
        // below it, which is capped for display.
        $windowTotal = $this->supabase->query('expenses', $params + ['select' => 'amount']);
        $totalExpenses = array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $windowTotal));

        $expenses = $this->supabase->query('expenses', $params + [
            'select' => '*, user:users(id,name)',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        return response()->json([
            'expenses' => array_values($expenses),
            'totalExpenses' => $totalExpenses,
            'rangeLabel' => BusinessDay::localRangeLabel(
                $request->query('date_from'),
                $request->query('date_to')
            ),
            'categories' => self::CATEGORIES,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** Cashier commits a new expense for their own branch. */
    public function store(Request $request)
    {
        $this->assertNotMonitoring($request);

        $validated = $request->validate([
            'category' => 'required|in:electricity,water,transport,cleaning,packaging,other',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:10',
        ]);

        $branchId = $this->ownBranchId($request);
        $supabaseUserId = $this->performingUserId($request);

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

        return response()->json(['message' => 'Expense recorded successfully.']);
    }
}
