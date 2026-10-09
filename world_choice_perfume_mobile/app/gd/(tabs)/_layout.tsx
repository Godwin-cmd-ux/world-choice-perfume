import { Tabs } from 'expo-router';
import { COLORS, GD_ACCENT } from '../../../lib/theme';

/**
 * The Graphic Designer screens live behind a Tabs navigator purely as
 * routes. The module's navigation is the sidebar (components/gdsidebar.tsx),
 * a slide-in copy of the website's graphic-designer sidebar, so the bottom
 * tab bar is hidden: the hamburger on each screen opens the sidebar, and
 * the sidebar's entries — Dashboard · News · Brands, then Tools → QR Code,
 * then Account → Profile · Logout — are the only navigation this module
 * has.
 *
 * "More" used to be a hub holding QR Code, Profile and Sign Out; the
 * sidebar links straight to everything now, so it is unlinked
 * (`href: null`).
 */
export default function GdTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: { display: 'none' },
        tabBarActiveTintColor: GD_ACCENT.main,
        tabBarInactiveTintColor: COLORS.textMuted,
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Dashboard' }} />
      <Tabs.Screen name="news" options={{ title: 'News' }} />
      <Tabs.Screen name="brands" options={{ title: 'Brands' }} />
      <Tabs.Screen name="more" options={{ title: 'More', href: null }} />
    </Tabs>
  );
}
