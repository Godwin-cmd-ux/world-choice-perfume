import { Ionicons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { CASHIER_ACCENT, COLORS } from '../../../lib/theme';

/**
 * Mobile navigation for the Cashier module. The website sidebar runs to four
 * entries (Dashboard, Sales, Orders, Expenses) plus Account — and six for an
 * HQ monitor, with "Daily Sales — All Branches" and "Cross-Branch
 * Monitoring". Five icon tabs is a wall, not a menu, so the bar keeps the
 * four daily destinations — Dashboard, Sales, Orders, More — and the More
 * hub folds Expenses, the HQ monitoring screens and the account actions
 * into grouped tiles, the same creative split Branch Admin uses. Active
 * tint is the amber-600 of the website's cashier sidebar, not the admin gold.
 */
export default function CashierTabsLayout() {
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
        tabBarActiveTintColor: CASHIER_ACCENT.main,
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
        name="sales"
        options={{
          title: 'Sales',
          tabBarIcon: ({ color, size }) => <Ionicons name="receipt-outline" size={size} color={color} />,
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
