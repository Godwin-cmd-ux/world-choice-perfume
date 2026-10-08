import { Ionicons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { useAsyncData } from '../../../components/adminkit';
import { fetchCcScope } from '../../../lib/careApi';
import { CC_ACCENT, COLORS } from '../../../lib/theme';

/**
 * Mobile navigation for the Customer Care module — the website's
 * customer-care sidebar, in the sidebar's own order:
 *
 *   Dashboard · Clients · Sales · Orders · Profile
 *
 * (Profile sits under the sidebar's Account group; Logout lives on that
 * screen, next to the account forms.) The sidebar's three Head
 * Quarters-only entries — News, Inquiries and the info@ Mails — keep their
 * own hub behind the sixth "More" tab, which only appears for a Head
 * Quarters-Mikocheni member, mirroring the sidebar's isHqCustomerCare
 * block (the server re-checks the same gate on every call).
 *
 * Active tint is the sky blue of the website's customer care section
 * (CC_ACCENT), not the admin gold.
 */
export default function CareTabsLayout() {
  const { data: scope } = useAsyncData(() => fetchCcScope(), []);
  const isHq = scope?.is_hq ?? false;

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
          title: 'Clients',
          tabBarIcon: ({ color, size }) => <Ionicons name="people-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="sales"
        options={{
          title: 'Sales',
          tabBarIcon: ({ color, size }) => <Ionicons name="cart-outline" size={size} color={color} />,
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
        name="profile"
        options={{
          title: 'Profile',
          tabBarIcon: ({ color, size }) => <Ionicons name="person-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="more"
        options={{
          title: 'More',
          // Head Quarters only — hidden (href: null) for branch members,
          // exactly like the website sidebar hides News/Inquiries/Mails.
          href: isHq ? '/care/more' : null,
          tabBarIcon: ({ color, size }) => <Ionicons name="apps-outline" size={size} color={color} />,
        }}
      />
    </Tabs>
  );
}
