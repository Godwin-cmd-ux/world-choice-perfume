<?php

namespace Tests\Unit;

use App\Services\NavigationCountsService;
use Tests\TestCase;

class NavigationCountsRouteTest extends TestCase
{
    public function test_data_pages_map_to_their_sidebar_counts(): void
    {
        $counts = new NavigationCountsService();

        $this->assertSame(['orders'], $counts->keysForRoute('customer-care.orders.index'));
        $this->assertSame(['inquiries'], $counts->keysForRoute('customer-care.inquiries.index'));
        $this->assertSame(['mails'], $counts->keysForRoute('customer-care.mails.show'));
        $this->assertSame(['notifications'], $counts->keysForRoute('super-admin.notifications.index'));
        $this->assertSame(['approvals'], $counts->keysForRoute('super-admin.cashiers.index'));
        $this->assertSame(['staff'], $counts->keysForRoute('branch-admin.staffs.index'));
        $this->assertSame(['transfers'], $counts->keysForRoute('stock-manager.stock-transfers.incoming'));
        $this->assertSame(['returns'], $counts->keysForRoute('stock-manager.stock-transfers.returns'));
        $this->assertSame(['returns'], $counts->keysForRoute('stock-manager.returned-stock.index'));
        $this->assertSame(['news'], $counts->keysForRoute('graphic-designer.news.index'));
        $this->assertSame([], $counts->keysForRoute('customer-care.customers.index'));
        $this->assertSame([], $counts->keysForRoute('customer-care.customers.loyal'));
    }

    public function test_dashboards_watch_every_panel_they_show(): void
    {
        $counts = new NavigationCountsService();

        $this->assertSame(
            ['orders', 'approvals', 'notifications', 'mails', 'returns'],
            $counts->keysForRoute('super-admin.dashboard')
        );

        $this->assertSame(
            ['orders', 'inquiries', 'mails', 'news', 'sales'],
            $counts->keysForRoute('customer-care.dashboard')
        );

        $this->assertSame(['sales'], $counts->keysForRoute('super-admin.daily-sales'));

        $this->assertSame([], $counts->keysForRoute('profile.edit'));
    }

    public function test_only_list_and_dashboard_screens_auto_refresh(): void
    {
        $counts = new NavigationCountsService();

        $this->assertTrue($counts->shouldAutoRefresh('customer-care.inquiries.index'));
        $this->assertTrue($counts->shouldAutoRefresh('customer-care.dashboard'));
        $this->assertTrue($counts->shouldAutoRefresh('stock-manager.stock-transfers.incoming'));
        $this->assertTrue($counts->shouldAutoRefresh('stock-manager.stock-transfers.returns'));

        $this->assertFalse($counts->shouldAutoRefresh('customer-care.inquiries.show'));
        $this->assertFalse($counts->shouldAutoRefresh('customer-care.mails.show'));
        $this->assertFalse($counts->shouldAutoRefresh('graphic-designer.news.edit'));
        $this->assertFalse($counts->shouldAutoRefresh('profile.edit'));
    }
}
