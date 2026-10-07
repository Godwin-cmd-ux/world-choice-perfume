import { Ionicons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { COLORS, GD_ACCENT } from '../../../lib/theme';

/**
 * Mobile navigation for the Graphic Designer module. The website sidebar has
 * four destinations (Dashboard, News, Brands, QR Code) plus the shared
 * Account block — the bottom bar mirrors them one-to-one, with QR Code and
 * Account folded into "More" so the bar stays uncluttered. Active tint is
 * the purple the website's /graphic-designer sidebar uses (GD_ACCENT), not
 * the Super Admin gold.
 */
export default function GdTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          backgroundColor: COLORS.bgRaised,
          borderTopColor: COLORS.border,
          borderTopWidth: 1,
          height: 62,
          paddingBottom: 8,
          paddingTop: 6,
        },
        tabBarActiveTintColor: GD_ACCENT.main,
        tabBarInactiveTintColor: COLORS.textMuted,
        tabBarLabelStyle: { fontSize: 11, fontWeight: '700' },
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Dashboard',
          tabBarIcon: ({ color, size }) => <Ionicons name="grid-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="news"
        options={{
          title: 'News',
          tabBarIcon: ({ color, size }) => <Ionicons name="newspaper-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="brands"
        options={{
          title: 'Brands',
          tabBarIcon: ({ color, size }) => <Ionicons name="pricetag-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="more"
        options={{
          title: 'More',
          tabBarIcon: ({ color, size }) => <Ionicons name="apps-outline" size={size} color={color} />,
        }}
      />
    </Tabs>
  );
}
