import { Tabs } from 'expo-router';
import { CC_ACCENT, COLORS } from '../../../lib/theme';

/**
 * The Customer Care screens live behind a Tabs navigator purely as routes.
 * The module's navigation is the sidebar (components/caresidebar.tsx), a
 * slide-in copy of the website's customer-care sidebar, so the bottom tab
 * bar is hidden: the hamburger on each screen opens the sidebar, and the
 * sidebar's entries — Dashboard · Clients · Sales · Orders, then News ·
 * Inquiries · Mails for Head Quarters, then Account → Profile · Logout —
 * are the only navigation this module has.
 *
 * "More" used to be a hub holding the Head Quarters entries; the sidebar
 * links straight to them now, so it is unlinked (`href: null`).
 */
export default function CareTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: { display: 'none' },
        tabBarActiveTintColor: CC_ACCENT.main,
        tabBarInactiveTintColor: COLORS.textMuted,
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Dashboard' }} />
      <Tabs.Screen name="customers" options={{ title: 'Clients' }} />
      <Tabs.Screen name="sales" options={{ title: 'Sales' }} />
      <Tabs.Screen name="orders" options={{ title: 'Orders' }} />
      <Tabs.Screen name="profile" options={{ title: 'Profile' }} />
      <Tabs.Screen name="more" options={{ title: 'More', href: null }} />
    </Tabs>
  );
}
