import { Ionicons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { COLORS } from '../../../lib/theme';

/**
 * Mobile navigation for the Super Admin module (see the capability audit —
 * 13 sidebar sections, far too many for a bottom bar).
 *
 * Strategy: only the four most-used destinations live in the bottom bar —
 * Dashboard, Orders, Approvals, More. "More" is a grouped launcher for every
 * remaining section (Monitor / Records / System), which keeps the bar
 * uncluttered while leaving the whole website sidebar one tap away.
 */
export default function AdminTabsLayout() {
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
        tabBarActiveTintColor: COLORS.gold,
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
        name="orders"
        options={{
          title: 'Orders',
          tabBarIcon: ({ color, size }) => <Ionicons name="bag-handle-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="approvals"
        options={{
          title: 'Approvals',
          tabBarIcon: ({ color, size }) => <Ionicons name="checkmark-done-outline" size={size} color={color} />,
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
