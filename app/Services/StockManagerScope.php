<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class StockManagerScope
{
    public const HQ_BRANCH_NAME = 'Head Quarters-Mikocheni';
    public const KINONDONI_BRANCH_NAME = 'Kinondoni branch';

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

    private function isStockManager(): bool
    {
        return ($this->user()->role ?? null) === 'stock_manager';
    }

    private function matches(string $branchName, string $target): bool
    {
        return mb_strtolower(trim($branchName)) === mb_strtolower(trim($target));
    }

    /**
     * The Kinondoni branch stock manager is the only one allowed to monitor
     * other branches.
     */
    public function isKinondoniStockManager(): bool
    {
        if (!$this->isStockManager()) {
            return false;
        }

        $name = $this->branchName((int) $this->user()->branch_id);

        return $name !== null && $this->matches($name, self::KINONDONI_BRANCH_NAME);
    }

    /**
     * Head Quarters-Mikocheni stock manager no longer handles bottles, oil
     * fragrance or bottle accessories.
     */
    public function isHQStockManager(): bool
    {
        if (!$this->isStockManager()) {
            return false;
        }

        $name = $this->branchName((int) $this->user()->branch_id);

        return $name !== null && $this->matches($name, self::HQ_BRANCH_NAME);
    }

    // ========================
    // Cross-branch context
    // ========================

    public function enterBranch(int $branchId): void
    {
        if ($this->isKinondoniStockManager() && $branchId !== (int) ($this->user()->branch_id ?? 0)) {
            session(['cross_branch_id' => $branchId]);
        }
    }

    public function exitBranch(): void
    {
        session()->forget('cross_branch_id');
    }

    /**
     * Branch id in use by the current pages. Defaults to the manager's own
     * branch unless he has entered a cross-branch monitoring session.
     */
    public function activeBranchId(): int
    {
        if ($this->isKinondoniStockManager() && !empty(session('cross_branch_id'))) {
            return (int) session('cross_branch_id');
        }

        return (int) ($this->user()->branch_id ?? 0);
    }

    public function activeBranchName(): ?string
    {
        return $this->branchName($this->activeBranchId());
    }

    /**
     * True while the Kinondoni stock manager is actively monitoring another
     * branch. All writes are blocked during this state.
     */
    public function inCrossBranchMode(): bool
    {
        return $this->isKinondoniStockManager() && !empty(session('cross_branch_id'));
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