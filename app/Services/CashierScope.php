<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class CashierScope
{
    public const HQ_BRANCH_NAME = 'Head Quarters-Mikocheni';

    private SupabaseService $supabase;

    public function __construct(?SupabaseService $supabase = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
    }

    public function user(): ?Authenticatable
    {
        return Auth::user();
    }

    public function branchName(?int $branchId): ?string
    {
        if (!$branchId) {
            return null;
        }

        $rows = $this->supabase->query('branches', [
            'select' => 'id,name',
            'id' => "eq.{$branchId}",
            'limit' => 1,
        ]);

        return $rows[0]['name'] ?? null;
    }

    /**
     * Resolve a map of branch id => name for a set of branch ids.
     */
    public function branchNameMap(array $ids): array
    {
        $map = [];
        $ids = array_values(array_unique(array_filter(array_map(fn ($id) => (int) $id, $ids), fn ($id) => $id > 0)));

        if (empty($ids)) {
            return $map;
        }

        $rows = $this->supabase->query('branches', [
            'select' => 'id,name',
            'id' => 'in.(' . implode(',', $ids) . ')',
        ]);

        foreach ($rows as $b) {
            $map[(int) $b['id']] = $b['name'];
        }

        return $map;
    }

    private function isCashier(): bool
    {
        return ($this->user()->role ?? null) === 'cashier';
    }

    private function matches(string $branchName, string $target): bool
    {
        return mb_strtolower(trim($branchName)) === mb_strtolower(trim($target));
    }

    /**
     * The HQ cashier is the only one allowed to monitor other branches.
     */
    public function isHqCashier(): bool
    {
        if (!$this->isCashier()) {
            return false;
        }

        $name = $this->branchName((int) $this->user()->branch_id);

        return $name !== null && $this->matches($name, self::HQ_BRANCH_NAME);
    }

    public function isSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    /**
     * Users who may monitor other branches: the HQ cashier and the Super Admin.
     */
    public function isCrossBranchMonitor(): bool
    {
        return $this->isHqCashier() || $this->isSuperAdmin();
    }

    // ========================
    // Cross-branch context
    // ========================

    public function enterBranch(int $branchId): void
    {
        if ($this->isSuperAdmin()) {
            session(['cashier_cross_branch_id' => $branchId]);
        } elseif ($this->isHqCashier() && $branchId !== (int) ($this->user()->branch_id ?? 0)) {
            session(['cashier_cross_branch_id' => $branchId]);
        }
    }

    public function exitBranch(): void
    {
        session()->forget('cashier_cross_branch_id');
    }

    /**
     * Branch id in use by the current pages. Defaults to the cashier's own
     * branch unless they have entered a cross-branch monitoring session.
     */
    public function activeBranchId(): int
    {
        if ($this->isCrossBranchMonitor() && !empty(session('cashier_cross_branch_id'))) {
            return (int) session('cashier_cross_branch_id');
        }

        return (int) ($this->user()->branch_id ?? 0);
    }

    public function activeBranchName(): ?string
    {
        return $this->branchName($this->activeBranchId());
    }

    /**
     * True while the HQ cashier is actively monitoring another branch.
     * All writes are blocked during this state.
     */
    public function inCrossBranchMode(): bool
    {
        return $this->isCrossBranchMonitor() && !empty(session('cashier_cross_branch_id'));
    }

    /**
     * Apply the active branch filter for a query.
     */
    public function branchParams(array $params): array
    {
        $params['branch_id'] = 'eq.' . $this->activeBranchId();

        return $params;
    }
}
