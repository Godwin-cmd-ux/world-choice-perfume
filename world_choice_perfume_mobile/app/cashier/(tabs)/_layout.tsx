import { Tabs } from 'expo-router';
import { CASHIER_ACCENT, COLORS } from '../../../lib/theme';

/**
 * The Cashier screens live behind a Tabs navigator purely as routes. The
 * module's navigation is the sidebar (components/cashiersidebar.tsx), a
 * slide-in copy of the website's cashier sidebar, so the bottom tab bar is
 * hidden: the hamburger on each screen opens the sidebar, and the
 * sidebar's entries — Dashboard · Sales · Orders · Expenses, then Daily
 * Sales — All Branches · Cross-Branch Monitoring for Head Quarters
 * monitors, then Account → Profile · Logout — are the only navigation this
 * module has.
 *
 * "More" used to be the hub holding Expenses, the monitoring screens and
 * the account actions; the sidebar links straight to everything now, so it
 * is unlinked (`href: null`).
 */
export default function CashierTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: { display: 'none' },
        tabBarActiveTintColor: CASHIER_ACCENT.main,
        tabBarInactiveTintColor: COLORS.textMuted,
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Dashboard' }} />
      <Tabs.Screen name="sales" options={{ title: 'Sales' }} />
      <Tabs.Screen name="orders" options={{ title: 'Orders' }} />
      <Tabs.Screen name="more" options={{ title: 'More', href: null }} />
    </Tabs>
  );
}
