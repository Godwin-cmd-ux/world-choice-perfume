import { Ionicons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { COLORS, CC_ACCENT } from '../../../lib/theme';

/**
 * Mobile navigation for the Customer Care module. The website sidebar runs
 * to seven entries (Dashboard, Clients, Sales, Orders, and — at Head
 * Quarters — Inquiries, News, Mails), so squeezing them into the bottom bar
 * would make it a wall of icons. The bar keeps the four daily destinations —
 * Dashboard, Customers, Orders, More — and the More tab is a hub that folds
 * the rest into grouped tiles, with the three Head Quarters sections shown
 * only to the Head Quarters member. Active tint is the sky blue of the
 * website's customer care section (CC_ACCENT), not the admin gold.
 */
export default function CareTabsLayout() {
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
        tabBarActiveTintColor: CC_ACCENT.main,
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
        name="customers"
        options={{
          title: 'Customers',
          tabBarIcon: ({ color, size }) => <Ionicons name="people-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="orders"
        options={{
          title: 'Orders',
          tabBarIcon: ({ color, size }) => <Ionicons name="clipboard-outline" size={size} color={color} />,
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
