<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashierManagementController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        $branchId = auth()->user()->branch_id;

        $cashiers = $this->supabase->query('users', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'role' => 'eq.cashier',
            'order' => 'created_at.desc',
        ]);

        // Add sales count for each cashier
        foreach ($cashiers as &$cashier) {
            $cashier['total_sales'] = $this->countCashierSales($cashier['id'], $branchId);
            $cashier = (object) $cashier;
        }

        return view('branch-admin.cashiers.index', ['cashiers' => collect($cashiers)]);
    }

    public function show($cashierId)
    {
        $cashier = $this->supabase->find('users', $cashierId);
        if (!$cashier || $cashier['role'] !== 'cashier' || $cashier['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        $branchId = auth()->user()->branch_id;

        // Recent sales
        $recentSales = $this->supabase->query('sales', [
            'select' => '*, items:sale_items(*, product:products(name,brand))',
            'cashier_id' => "eq.{$cashierId}",
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 10,
        ]);

        // Today's summary
        $today = Carbon::today()->toDateString();
        $todaySalesData = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$cashierId}",
            'created_at' => "gte.{$today}T00:00:00",
        ]);
        $todaySales = array_sum(array_map(fn($s) => $s['total'] ?? 0, $todaySalesData));

        $recentSales = collect($recentSales)->map(function ($s) {
            if (isset($s['items']) && is_array($s['items'])) {
                $s['items'] = collect($s['items']);
            }
            return (object) $s;
        });

        return view('branch-admin.cashiers.show', [
            'cashier' => (object) $cashier,
            'recentSales' => $recentSales,
            'todaySales' => $todaySales,
        ]);
    }

    public function approve($cashierId)
    {
        $cashier = $this->supabase->find('users', $cashierId);
        if (!$cashier || $cashier['branch_id'] != auth()->user()->branch_id) {
            abort(403);
        }

        $this->supabase->update('users', ['status' => 'approved'], ['id' => $cashierId]);
        \App\Models\User::where('email', $cashier['email'])->update(['status' => 'approved']);

        return back()->with('success', "{$cashier['name']} has been approved.");
    }

    public function reject($cashierId)
    {
        $cashier = $this->supabase->find('users', $cashierId);
        if (!$cashier || $cashier['branch_id'] != auth()->user()->branch_id) {
            abort(403);
        }

        $this->supabase->update('users', ['status' => 'rejected'], ['id' => $cashierId]);
        \App\Models\User::where('email', $cashier['email'])->update(['status' => 'rejected']);

        return back()->with('success', "{$cashier['name']} has been rejected.");
    }

    public function accountability(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $cashiers = $this->supabase->query('users', [
            'branch_id' => "eq.{$branchId}",
            'role' => 'eq.cashier',
            'status' => 'eq.approved',
        ]);

        $results = [];
        foreach ($cashiers as $cashier) {
            $expectedCash = $this->getCashierExpectedCash($cashier['id'], $branchId, $date);

            // Check cashier_accounts for this date
            $account = $this->supabase->findOne('cashier_accounts', [
                'cashier_id' => $cashier['id'],
                'date' => $date->toDateString(),
            ]);

            // Expenses
            $expenses = $this->supabase->query('expenses', [
                'user_id' => "eq.{$cashier['id']}",
            ]);
            $dayExpenses = array_sum(array_map(function ($e) use ($date) {
                return (substr($e['created_at'] ?? '', 0, 10) === $date->toDateString()) ? ($e['amount'] ?? 0) : 0;
            }, $expenses));

            $actualCash = $account['actual_cash'] ?? null;
            $difference = $actualCash !== null ? $actualCash - $expectedCash : null;

            $results[] = [
                'cashier' => (object) $cashier,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'expenses' => $dayExpenses,
                'status' => match(true) {
                    $actualCash === null => 'pending',
                    $difference == 0 => 'balanced',
                    $difference > 0 => 'surplus',
                    default => 'loss',
                },
            ];
        }

        return view('branch-admin.cashiers.accountability', compact('results', 'date'));
    }

    public function storeAccountability(Request $request)
    {
        $validated = $request->validate([
            'cashier_id' => 'required',
            'date' => 'required|date',
            'actual_cash' => 'required|numeric|min:0',
        ]);

        $branchId = auth()->user()->branch_id;
        $cashier = $this->supabase->find('users', $validated['cashier_id']);
        if (!$cashier) abort(404);

        $expectedCash = $this->getCashierExpectedCash($validated['cashier_id'], $branchId, Carbon::parse($validated['date']));
        $difference = $validated['actual_cash'] - $expectedCash;

        $status = match(true) {
            $difference == 0 => 'balanced',
            $difference > 0 => 'surplus',
            default => 'loss',
        };

        // Upsert in Supabase
        $existing = $this->supabase->findOne('cashier_accounts', [
            'cashier_id' => $validated['cashier_id'],
            'date' => $validated['date'],
        ]);

        $accountData = [
            'cashier_id' => $validated['cashier_id'],
            'branch_id' => $branchId,
            'date' => $validated['date'],
            'expected_cash' => $expectedCash,
            'actual_cash' => $validated['actual_cash'],
            'difference' => $difference,
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($existing) {
            $this->supabase->update('cashier_accounts', $accountData, ['id' => $existing['id']]);
        } else {
            $accountData['created_at'] = now()->toIso8601String();
            $existing = $this->supabase->insert('cashier_accounts', $accountData);
        }

        // Auto-create discrepancy record if there's a difference
        if ($difference != 0) {
            $this->supabase->insert('discrepancies', [
                'cashier_account_id' => $existing['id'] ?? null,
                'branch_id' => $branchId,
                'cashier_id' => $validated['cashier_id'],
                'reason' => $difference < 0 ? 'genuine_shortage' : 'surplus',
                'amount' => abs($difference),
                'description' => $difference < 0 ? "Cash shortage of " . number_format(abs($difference), 2) : "Cash surplus of " . number_format($difference, 2),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Accountability record saved.');
    }

    private function countCashierSales(int $cashierId, int $branchId): float
    {
        $sales = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$cashierId}",
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
        ]);
        return array_sum(array_map(fn($s) => $s['total'] ?? 0, $sales));
    }

    private function getCashierExpectedCash(int $cashierId, int $branchId, Carbon $date): float
    {
        $today = $date->toDateString();
        $sales = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$cashierId}",
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
        ]);

        $daySales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) === $today);
        return array_sum(array_map(fn($s) => $s['total'] ?? 0, $daySales));
    }
}
