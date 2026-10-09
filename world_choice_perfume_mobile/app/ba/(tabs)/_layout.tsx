import { Tabs } from 'expo-router';
import { BA_ACCENT, COLORS } from '../../../lib/theme';

/**
 * The Branch Admin screens live behind a Tabs navigator purely as routes.
 * The module's navigation is the sidebar (components/basidebar.tsx), a
 * slide-in copy of the website's branch-admin sidebar, so the bottom tab
 * bar is hidden: the hamburger on each screen opens the sidebar, and the
 * sidebar's entries — Dashboard · Sales · Orders · Staffs · Expenses, then
 * Account → Profile · Logout — are the only navigation this module has.
 *
 * "More" used to be the hub holding Staffs, Expenses and the account
 * actions; the sidebar links straight to them now, so it is unlinked
 * (`href: null`).
 */
export default function BaTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: { display: 'none' },
        tabBarActiveTintColor: BA_ACCENT.main,
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
