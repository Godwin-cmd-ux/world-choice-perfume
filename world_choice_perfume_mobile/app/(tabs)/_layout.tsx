import { Ionicons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { COLORS } from '../../lib/theme';

/**
 * Public (signed-out) navigation for the World Choice Perfume app.
 *
 * The landing page used to be a logo plus six menu buttons — HOME,
 * SHOPPING, TRACK ORDERS, CONTACTS, NEWS, STAFF LOGIN. Those six
 * navigations now live in this bottom tab bar, in the same order, and the
 * app opens straight onto the restyled home page (index — the website's
 * landing blade, home.blade.php, section for section) after the native
 * splash. The tab bar keeps the brand's deep-black + gold treatment; the
 * group folder is transparent, so every screen keeps its old URL
 * (/shop, /track, /contacts, /news, /staff-access).
 */
export default function PublicTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: COLORS.gold,
        tabBarInactiveTintColor: COLORS.textMuted,
        tabBarStyle: {
          backgroundColor: COLORS.bgRaised,
          borderTopColor: COLORS.border,
          borderTopWidth: 1,
          height: 62,
          paddingBottom: 8,
          paddingTop: 6,
        },
        tabBarLabelStyle: { fontSize: 10, fontWeight: '700', letterSpacing: 0.3 },
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="shop"
        options={{
          title: 'Shop',
          tabBarIcon: ({ color, size }) => <Ionicons name="bag-handle-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="track"
        options={{
          title: 'Track',
          tabBarIcon: ({ color, size }) => <Ionicons name="cube-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="contacts"
        options={{
          title: 'Contact',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="chatbubble-ellipses-outline" size={size} color={color} />
          ),
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
        name="staff-access"
        options={{
          title: 'Staff',
          tabBarIcon: ({ color, size }) => <Ionicons name="lock-closed-outline" size={size} color={color} />,
        }}
      />
    </Tabs>
  );
}
