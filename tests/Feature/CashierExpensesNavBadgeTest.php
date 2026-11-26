<?php

namespace Tests\Feature;

use App\Services\NavigationCountsService;
use Tests\TestCase;

/**
 * The cashier's Expenses link is navigation only — it no longer carries a
 * "new expenses today" notification badge, and the dashboard no longer polls
 * for one.
 */
class CashierExpensesNavBadgeTest extends TestCase
{
    public function test_cashier_dashboard_does_not_watch_expense_counts(): void
    {
        $counts = new NavigationCountsService();

        $this->assertSame(['orders', 'sales'], $counts->keysForRoute('cashier.dashboard'));
        $this->assertSame([], $counts->keysForRoute('cashier.expenses.index'));
    }

    public function test_cashier_sidebar_has_no_expenses_badge(): void
    {
        $sidebar = (string) file_get_contents(resource_path('views/cashier/partials/sidebar.blade.php'));

        $this->assertStringNotContainsString("'key' => 'expenses'", $sidebar);
    }
}
