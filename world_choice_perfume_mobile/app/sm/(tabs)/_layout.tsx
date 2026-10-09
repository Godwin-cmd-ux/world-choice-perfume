import { Tabs } from 'expo-router';
import { COLORS, SM_ACCENT } from '../../../lib/theme';

/**
 * The Stock Manager screens live behind a Tabs navigator purely as routes.
 * The module's navigation is the sidebar (components/smsidebar.tsx), a
 * slide-in copy of the website's stock-manager sidebar, so the bottom tab
 * bar is hidden: the hamburger on each screen opens the sidebar, and the
 * sidebar's entries — Dashboard; Sales · Orders · Products · Product Stock
 * (plus Cross-Branch for the Kinondoni monitor, plus Bottle Stock · Oil
 * Fragrance · Bottle Accessories for autonomous branches); Stock Transfers
 * · Pending Incoming Stock (plus Returned Stock for Kinondoni) · Returned
 * Items; Account → Profile · Logout — are the only navigation this module
 * has.
 *
 * "More" used to be a hub holding the sidebar's rest; the sidebar links
 * straight to everything now, so it is unlinked (`href: null`).
 */
export default function SmTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: { display: 'none' },
        tabBarActiveTintColor: SM_ACCENT.main,
        tabBarInactiveTintColor: COLORS.textMuted,
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Dashboard' }} />
      <Tabs.Screen name="stock" options={{ title: 'Product Stock' }} />
      <Tabs.Screen name="transfers" options={{ title: 'Transfers' }} />
      <Tabs.Screen name="more" options={{ title: 'More', href: null }} />
    </Tabs>
  );
}
