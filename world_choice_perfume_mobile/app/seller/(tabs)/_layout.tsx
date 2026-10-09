import { Tabs } from 'expo-router';
import { COLORS, SELLER_ACCENT } from '../../../lib/theme';

/**
 * The Seller screens live behind a Tabs navigator purely as routes. The
 * module's navigation is the sidebar (components/sellersidebar.tsx), a
 * slide-in copy of the website's seller sidebar, so the bottom tab bar is
 * hidden: the hamburger on each screen opens the sidebar, and the
 * sidebar's entries — Dashboard · My Sales · Orders, then Account →
 * Profile (this module's Account tab) · Logout — are the only navigation
 * this module has. Unlike the other modules there is no More hub: the
 * seller sidebar is exactly these destinations, so all four stay routable
 * as tabs.
 */
export default function SellerTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: { display: 'none' },
        tabBarActiveTintColor: SELLER_ACCENT.main,
        tabBarInactiveTintColor: COLORS.textMuted,
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Dashboard' }} />
      <Tabs.Screen name="sales" options={{ title: 'My Sales' }} />
      <Tabs.Screen name="orders" options={{ title: 'Orders' }} />
      <Tabs.Screen name="account" options={{ title: 'Account' }} />
    </Tabs>
  );
}
