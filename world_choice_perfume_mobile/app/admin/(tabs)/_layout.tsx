import { Tabs } from 'expo-router';
import { COLORS } from '../../../lib/theme';

/**
 * The Super Admin screens live behind a Tabs navigator purely as routes.
 * The module's navigation is the sidebar (components/adminsidebar.tsx), a
 * slide-in copy of the website's super-admin sidebar, so the bottom tab bar
 * is hidden: the hamburger on each screen opens the sidebar, and the
 * sidebar's entries — Dashboard · Daily Sales Overview · Branches ·
 * Cross-Branch Stock · Cross-Branch Sales · Approvals · Orders · Emails ·
 * Returned Stock · Notifications · Reports · Staff · Settings, then
 * Account → Profile · Logout — are the only navigation this module has.
 *
 * "More" used to be the grouped launcher for the sections that did not fit
 * in the bar; the sidebar links straight to everything now, so it is
 * unlinked (`href: null`).
 */
export default function AdminTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: { display: 'none' },
        tabBarActiveTintColor: COLORS.gold,
        tabBarInactiveTintColor: COLORS.textMuted,
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Dashboard' }} />
      <Tabs.Screen name="orders" options={{ title: 'Orders' }} />
      <Tabs.Screen name="approvals" options={{ title: 'Approvals' }} />
      <Tabs.Screen name="more" options={{ title: 'More', href: null }} />
    </Tabs>
  );
}
