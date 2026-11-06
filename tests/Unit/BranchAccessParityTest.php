<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CashierScope;
use App\Services\CustomerCareScope;
use App\Services\StockManagerScope;
use App\Services\SupabaseService;
use App\Support\BranchCategory;
use Tests\TestCase;

/**
 * Branch access parity.
 *
 * The remaining capability gates are a positive allowlist naming only two
 * branches: Kinondoni (cross-branch stock monitoring) and the Head Quarters
 * branch (cross-branch cashier monitoring and the company mailbox). The depot
 * rules match on the branch NAME; the Head Quarters capabilities match on the
 * branch ID (BranchAccess::HEAD_QUARTERS_ID), so the branch keeps them when it
 * is renamed — as it has been, to "MIKOCHENI BRANCH HQ". Dodoma is named by
 * none of them, which makes it the baseline.
 *
 * The products-only stock rule is NOT in that list any more. It is decided by
 * the branch's stored CATEGORY, because a branch created as products-based must
 * behave like Head Quarters-Mikocheni without being called that — see
 * BranchCategoryTest for that side of it.
 *
 * Both are load-bearing assumptions, so both are pinned here. If someone later
 * keys a new gate on a branch name, or adds a third named exception, the parity
 * assertion below is what notices.
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

    /** Only Head Quarters-Mikocheni is products-based; the rest are baseline. */
    private const CATEGORIES = [
        self::KINONDONI => BranchCategory::AUTONOMOUS,
        self::DODOMA => BranchCategory::AUTONOMOUS,
        self::HEAD_QUARTERS => BranchCategory::PRODUCTS_BASED,
        self::FUTURE_BRANCH => BranchCategory::AUTONOMOUS,
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

    public function test_only_the_products_based_branch_is_without_bottles(): void
    {
        $method = 'isProductsOnlyStockManager';

        $this->assertTrue($this->flag(self::HEAD_QUARTERS, 'stock_manager', $method));
        $this->assertFalse($this->flag(self::KINONDONI, 'stock_manager', $method));
        $this->assertFalse($this->flag(self::DODOMA, 'stock_manager', $method));
        $this->assertFalse($this->flag(self::FUTURE_BRANCH, 'stock_manager', $method));

        // And an autonomous branch is never products-only, whatever it is named.
        $this->assertFalse($this->flagAtCategory(self::FUTURE_BRANCH, 'stock_manager', $method, BranchCategory::AUTONOMOUS));
    }

    /**
     * The category, not the name, decides the products-only rule. A branch
     * called anything at all must get it once it is marked products-based.
     */
    public function test_a_branch_is_products_only_by_category_not_by_name(): void
    {
        $method = 'isProductsOnlyStockManager';

        $this->assertTrue(
            $this->flagAtCategory(self::FUTURE_BRANCH, 'stock_manager', $method, BranchCategory::PRODUCTS_BASED),
            'A products-based branch must behave like Head Quarters-Mikocheni.'
        );

        $this->assertFalse(
            $this->flagAtCategory(self::HEAD_QUARTERS, 'stock_manager', $method, BranchCategory::AUTONOMOUS),
            'Head Quarters-Mikocheni must lose the rule when its category changes.'
        );
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
     * The Head Quarters capabilities follow the branch ROW, not its name: the
     * branch now called "MIKOCHENI BRANCH HQ" keeps them, and another branch
     * that merely carries the old name gets nothing.
     */
    public function test_the_head_quarters_capabilities_follow_the_branch_id_not_the_name(): void
    {
        $supabase = $this->fakeBranchRows([
            [
                'id' => self::HEAD_QUARTERS,
                'name' => 'MIKOCHENI BRANCH HQ',
                'category' => BranchCategory::PRODUCTS_BASED,
            ],
            [
                'id' => 40,
                'name' => 'Head Quarters-Mikocheni',
                'category' => BranchCategory::AUTONOMOUS,
            ],
        ]);

        $this->loginAs('cashier', self::HEAD_QUARTERS);
        $this->assertTrue(
            (new CashierScope($supabase))->isCrossBranchMonitor(),
            'The renamed Head Quarters cashier must still monitor other branches.'
        );

        $this->loginAs('customer_care', self::HEAD_QUARTERS);
        $this->assertTrue(
            (new CustomerCareScope($supabase))->isHqCustomerCare(),
            'The renamed Head Quarters member must still hold the mailbox tiers.'
        );

        $this->loginAs('cashier', 40);
        $this->assertFalse(
            (new CashierScope($supabase))->isCrossBranchMonitor(),
            'A different branch must not inherit the capability from the old name.'
        );

        $this->loginAs('customer_care', 40);
        $this->assertFalse(
            (new CustomerCareScope($supabase))->isHqCustomerCare(),
            'A different branch must not inherit the mailbox from the old name.'
        );
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
            'products_only_stock_manager' => $scope->isProductsOnlyStockManager(),
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

    /**
     * The same read, but with one branch carrying a chosen category.
     *
     * This is what pins the products-only rule to the category rather than to
     * the name: the branch is Arusha, a name that has never carried anything.
     */
    private function flagAtCategory(int $branchId, string $role, string $method, string $category): bool
    {
        $scopes = [
            'stock_manager' => StockManagerScope::class,
            'cashier' => CashierScope::class,
            'customer_care' => CustomerCareScope::class,
        ];

        $scope = new $scopes[$role]($this->fakeBranches([$branchId => $category]));

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

    /**
     * Serves the branch lookup the scopes make, honouring the eq. filter.
     *
     * @param array<int, string> $categoryOverrides branch id => category
     */
    private function fakeBranches(array $categoryOverrides = []): SupabaseService
    {
        $rows = [];
        foreach (self::BRANCHES as $id => $name) {
            $rows[] = [
                'id' => $id,
                'name' => $name,
                'category' => $categoryOverrides[$id] ?? self::CATEGORIES[$id],
            ];
        }

        return $this->fakeBranchRows($rows);
    }

    /**
     * The same fake over arbitrary branch rows, for a test that needs a branch
     * outside the live set.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function fakeBranchRows(array $rows): SupabaseService
    {
        return new class($rows) extends SupabaseService
        {
            /** @param array<int, array<string, mixed>> $rows */
            public function __construct(private array $rows)
            {
            }

            public function query(string $table, array $params = []): array
            {
                if ($table !== 'branches') {
                    return [];
                }

                $filter = $params['id'] ?? null;
                $wanted = is_string($filter) && str_starts_with($filter, 'eq.')
                    ? (int) substr($filter, 3)
                    : null;

                if ($wanted === null) {
                    return $this->rows;
                }

                return array_values(array_filter($this->rows, fn ($b) => (int) $b['id'] === $wanted));
            }
        };
    }
}
