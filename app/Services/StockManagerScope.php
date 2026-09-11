<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class StockManagerScope
{
    public const HQ_BRANCH_NAME = 'Head Quarters-Mikocheni';
    public const GLOBAL_BRANCH_NAME = 'Kinondoni branch';

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
     * Kinondoni branch stock manager monitors the activities of all branches.
     */
    public function isGlobalStockManager(): bool
    {
        if (!$this->isStockManager()) {
            return false;
        }

        $name = $this->branchName((int) $this->user()->branch_id);

        return $name !== null && $this->matches($name, self::GLOBAL_BRANCH_NAME);
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

    /**
     * Apply the branch filter for a query. The global (Kinondoni) stock manager
     * sees every branch, so the branch filter is stripped for him.
     */
    public function branchParams(array $params, ?int $branchId): array
    {
        if ($this->isGlobalStockManager()) {
            unset($params['branch_id']);
            return $params;
        }

        if ($branchId) {
            $params['branch_id'] = "eq.{$branchId}";
        }

        return $params;
    }
}