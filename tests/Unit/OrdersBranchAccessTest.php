<?php

namespace Tests\Unit;

use App\Support\BranchAccess;
use Tests\TestCase;

/** The Orders module is an allowlist: Mikocheni HQ (id 10) and Super Admin. */
class OrdersBranchAccessTest extends TestCase
{
    public function test_the_head_quarters_branch_can_use_orders_in_every_staff_role(): void
    {
        foreach (['branch_admin', 'cashier', 'seller', 'customer_care', 'stock_manager'] as $role) {
            $this->assertTrue(
                BranchAccess::canAccessOrders(BranchAccess::HEAD_QUARTERS_ID, $role),
                "{$role} at HQ should keep Orders"
            );
        }
    }

    public function test_other_branches_do_not_get_orders(): void
    {
        foreach ([3, 9, 11, null] as $branchId) {
            $this->assertFalse(
                BranchAccess::canAccessOrders($branchId, 'cashier'),
                'Orders must stay off branch ' . var_export($branchId, true)
            );
        }
    }

    public function test_super_admin_keeps_the_company_wide_view_from_every_branch(): void
    {
        foreach ([3, 9, 10, null] as $branchId) {
            $this->assertTrue(
                BranchAccess::canAccessOrders($branchId, 'super_admin'),
                'Super Admin should keep Orders from ' . var_export($branchId, true)
            );
        }
    }
}
