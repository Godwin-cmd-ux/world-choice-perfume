<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CashierScope;
use App\Services\CustomerCareScope;
use App\Services\StockManagerScope;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * Branch access parity.
 *
 * Every capability gate in the app is a positive allowlist keyed on the branch
 * NAME, and every one of them names only two branches: Kinondoni and Head
 * Quarters-Mikocheni. Dodoma is named by none of them, which makes it the
 * baseline — so a branch created tomorrow inherits Dodoma's access simply by
 * existing, with nothing to configure.
 *
 * That is a load-bearing assumption spread over a handful of string literals,
 * so it is pinned here. If someone later keys a new gate on a branch name, or
 * adds a third named exception, the parity assertion below is what notices.
 */
class BranchAccessParityTest extends TestCase
{
    /** The live branch ids, and one standing in for a branch created later. */
    private const KINONDONI = 8;
    private const DODOMA = 9;
    private const HEAD_QUARTERS = 10;
    private const FUTURE_BRANCH = 11;

    private const BRANCHES = [
        self::KINONDONI => 'Kinondoni branch',
        self::DODOMA => 'Dodoma branch',
        self::HEAD_QUARTERS => 'Head Quarters-Mikocheni',
        self::FUTURE_BRANCH => 'Arusha branch',
    ];

    public function test_a_new_branch_gets_the_same_access_as_dodoma_for_a_stock_manager(): void
    {
        $dodoma = $this->stockManagerAccess(self::DODOMA);
        $future = $this->stockManagerAccess(self::FUTURE_BRANCH);

        $this->assertSame($dodoma, $future, 'A new branch must match Dodoma for the stock manager.');
    }

    public function test_a_new_branch_gets_the_same_access_as_dodoma_for_a_cashier(): void
    {
        $dodoma = $this->cashierAccess(self::DODOMA);
        $future = $this->cashierAccess(self::FUTURE_BRANCH);

        $this->assertSame($dodoma, $future, 'A new branch must match Dodoma for the cashier.');
    }

    public function test_a_new_branch_gets_the_same_access_as_dodoma_for_customer_care(): void
    {
        $dodoma = $this->customerCareAccess(self::DODOMA);
        $future = $this->customerCareAccess(self::FUTURE_BRANCH);

        $this->assertSame($dodoma, $future, 'A new branch must match Dodoma for customer care.');
    }

    // ========================
    // The two branches that ARE exceptions
    // ========================

    public function test_kinondoni_stock_manager_is_the_only_one_who_monitors_other_branches(): void
    {
        $this->assertTrue($this->flag(self::KINONDONI, 'stock_manager', 'isCrossBranchMonitor'));
        $this->assertFalse($this->flag(self::DODOMA, 'stock_manager', 'isCrossBranchMonitor'));
        $this->assertFalse($this->flag(self::HEAD_QUARTERS, 'stock_manager', 'isCrossBranchMonitor'));
        $this->assertFalse($this->flag(self::FUTURE_BRANCH, 'stock_manager', 'isCrossBranchMonitor'));
    }

    public function test_head_quarters_stock_manager_is_the_only_one_without_bottles(): void
    {
        $this->assertTrue($this->flag(self::HEAD_QUARTERS, 'stock_manager', 'isHQStockManager'));
        $this->assertFalse($this->flag(self::KINONDONI, 'stock_manager', 'isHQStockManager'));
        $this->assertFalse($this->flag(self::DODOMA, 'stock_manager', 'isHQStockManager'));
        $this->assertFalse($this->flag(self::FUTURE_BRANCH, 'stock_manager', 'isHQStockManager'));
    }

    public function test_head_quarters_cashier_is_the_only_one_who_monitors_other_branches(): void
    {
        $this->assertTrue($this->flag(self::HEAD_QUARTERS, 'cashier', 'isCrossBranchMonitor'));
        $this->assertFalse($this->flag(self::KINONDONI, 'cashier', 'isCrossBranchMonitor'));
        $this->assertFalse($this->flag(self::DODOMA, 'cashier', 'isCrossBranchMonitor'));
        $this->assertFalse($this->flag(self::FUTURE_BRANCH, 'cashier', 'isCrossBranchMonitor'));
    }

    public function test_head_quarters_customer_care_holds_the_company_mailbox_alone(): void
    {
        $this->assertTrue($this->flag(self::HEAD_QUARTERS, 'customer_care', 'isHqCustomerCare'));
        $this->assertFalse($this->flag(self::KINONDONI, 'customer_care', 'isHqCustomerCare'));
        $this->assertFalse($this->flag(self::DODOMA, 'customer_care', 'isHqCustomerCare'));
        $this->assertFalse($this->flag(self::FUTURE_BRANCH, 'customer_care', 'isHqCustomerCare'));
    }

    /**
     * The one way a future branch can break the rule: give it a name that
     * collides with an exception. Matching is case- and space-insensitive, so
     * this has to be checked the same way the scopes do.
     */
    public function test_only_the_two_named_branches_hold_an_exception(): void
    {
        $exceptions = [
            strtolower(trim(StockManagerScope::KINONDONI_BRANCH_NAME)),
            strtolower(trim(StockManagerScope::HQ_BRANCH_NAME)),
        ];

        $carrying = [];
        foreach (self::BRANCHES as $id => $name) {
            if (in_array(strtolower(trim($name)), $exceptions, true)) {
                $carrying[] = $name;
            }
        }

        $this->assertSame(
            ['Kinondoni branch', 'Head Quarters-Mikocheni'],
            $carrying,
            'Only Kinondoni and Head Quarters-Mikocheni may hold an exception.'
        );
    }

    // ========================
    // Helpers
    // ========================

    private function stockManagerAccess(int $branchId): array
    {
        $scope = new StockManagerScope($this->fakeBranches());
        $this->loginAs('stock_manager', $branchId);

        return [
            'kinondoni_stock_manager' => $scope->isKinondoniStockManager(),
            'hq_stock_manager' => $scope->isHQStockManager(),
            'cross_branch_monitor' => $scope->isCrossBranchMonitor(),
        ];
    }

    private function cashierAccess(int $branchId): array
    {
        $scope = new CashierScope($this->fakeBranches());
        $this->loginAs('cashier', $branchId);

        return [
            'hq_cashier' => $scope->isHqCashier(),
            'cross_branch_monitor' => $scope->isCrossBranchMonitor(),
        ];
    }

    private function customerCareAccess(int $branchId): array
    {
        $scope = new CustomerCareScope($this->fakeBranches());
        $this->loginAs('customer_care', $branchId);

        return [
            'hq_customer_care' => $scope->isHqCustomerCare(),
        ];
    }

    /** Reads one capability off the scope that owns the given role. */
    private function flag(int $branchId, string $role, string $method): bool
    {
        $supabase = $this->fakeBranches();

        $scope = match ($role) {
            'stock_manager' => new StockManagerScope($supabase),
            'cashier' => new CashierScope($supabase),
            'customer_care' => new CustomerCareScope($supabase),
        };

        $this->loginAs($role, $branchId);

        return (bool) $scope->{$method}();
    }

    private function loginAs(string $role, int $branchId): void
    {
        $user = new User;
        $user->forceFill([
            'id' => 100 + $branchId,
            'supabase_id' => 200 + $branchId,
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $role . '.' . $branchId . '@example.co.tz',
            'role' => $role,
            'branch_id' => $branchId,
            'status' => 'active',
        ]);

        $this->be($user);
    }

    /** Serves the branch lookup the scopes make, honouring the eq. filter. */
    private function fakeBranches(): SupabaseService
    {
        return new class extends SupabaseService
        {
            public function __construct() {}

            public function query(string $table, array $params = []): array
            {
                if ($table !== 'branches') {
                    return [];
                }

                $filter = $params['id'] ?? null;
                $wanted = is_string($filter) && str_starts_with($filter, 'eq.')
                    ? (int) substr($filter, 3)
                    : null;

                $branches = [
                    ['id' => 8, 'name' => 'Kinondoni branch'],
                    ['id' => 9, 'name' => 'Dodoma branch'],
                    ['id' => 10, 'name' => 'Head Quarters-Mikocheni'],
                    ['id' => 11, 'name' => 'Arusha branch'],
                ];

                if ($wanted === null) {
                    return $branches;
                }

                return array_values(array_filter($branches, fn ($b) => (int) $b['id'] === $wanted));
            }
        };
    }
}
