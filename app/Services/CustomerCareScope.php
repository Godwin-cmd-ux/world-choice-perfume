<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class CustomerCareScope
{
    public const HQ_BRANCH_NAME = 'Head Quarters-Mikocheni';

    private SupabaseService $supabase;

    public function __construct(?SupabaseService $supabase = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
    }

    public function user()
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

    public function isCustomerCare(): bool
    {
        return ($this->user()->role ?? null) === 'customer_care';
    }

    public function isHqCustomerCare(): bool
    {
        if (!$this->isCustomerCare()) {
            return false;
        }

        $name = $this->branchName((int) $this->user()->branch_id);

        return $name !== null && mb_strtolower(trim($name)) === mb_strtolower(trim(self::HQ_BRANCH_NAME));
    }
}