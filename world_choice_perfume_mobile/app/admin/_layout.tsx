import { Stack, router, usePathname } from 'expo-router';
import { useEffect } from 'react';
import { COLORS } from '../../lib/theme';
import { staffSession } from '../../lib/staffSession';

/**
 * Root of the Super Admin module.
 *
 * The guard below is convenience, not security — every /api/admin endpoint
 * independently re-verifies the session token and the super_admin role on the
 * server (EnsureStaffSessionApi). This only stops a non-Super-Admin who is
 * already signed in from staring at empty screens; if the server ever says
 * 401/403, each screen kicks the user back to /staff as well.
 */
function useSuperAdminGuard() {
  const pathname = usePathname();

  useEffect(() => {
    const identity = staffSession.getIdentity();
    if (!identity || identity.role !== 'super_admin' || !staffSession.getSessionToken()) {
      router.replace('/staff');
    }
  }, [pathname]);
}

export default function AdminLayout() {
  useSuperAdminGuard();

  return (
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: COLORS.bg },
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen name="(tabs)" options={{ animation: 'fade' }} />
      <Stack.Screen name="order-detail" />
      <Stack.Screen name="approval-detail" />
      <Stack.Screen name="branches" />
      <Stack.Screen name="branch-form" />
      <Stack.Screen name="staff" />
      <Stack.Screen name="staff-form" />
      <Stack.Screen name="staff-detail" />
      <Stack.Screen name="emails" />
      <Stack.Screen name="email-detail" options={{ animation: 'slide_from_bottom' }} />
      <Stack.Screen name="notifications" />
      <Stack.Screen name="returned-stock" />
      <Stack.Screen name="daily-sales" />
      <Stack.Screen name="cross-stock" />
      <Stack.Screen name="cross-sales" />
      <Stack.Screen name="reports" />
      <Stack.Screen name="report-sales" />
      <Stack.Screen name="report-expenses" />
      <Stack.Screen name="report-stock" />
      <Stack.Screen name="report-staff" />
      <Stack.Screen name="report-products" />
      <Stack.Screen name="settings" />
      <Stack.Screen name="profile" />
    </Stack>
  );
}
