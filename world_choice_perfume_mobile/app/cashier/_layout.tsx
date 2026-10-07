import { Stack, router, usePathname } from 'expo-router';
import { useEffect } from 'react';
import { COLORS } from '../../lib/theme';
import { staffSession } from '../../lib/staffSession';

/**
 * Root of the Cashier module.
 *
 * The guard below is convenience, not security — every /api/cashier endpoint
 * independently re-verifies the session token and the cashier/super_admin
 * role on the server (EnsureStaffSessionApi, the same two roles the website's
 * `role:cashier,super_admin` group accepts), sales are scoped to the member's
 * own cashier_id and the monitoring branch is re-authorised on every call.
 * This only stops a member with the wrong role from staring at empty
 * screens; every screen also kicks the user back to /staff when the server
 * answers 401/403.
 */
function useCashierGuard() {
  const pathname = usePathname();

  useEffect(() => {
    const identity = staffSession.getIdentity();
    const role = identity?.role;
    if ((role !== 'cashier' && role !== 'super_admin') || !staffSession.getSessionToken()) {
      router.replace('/staff');
    }
  }, [pathname]);
}

export default function CashierLayout() {
  useCashierGuard();

  return (
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: COLORS.bg },
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen name="(tabs)" options={{ animation: 'fade' }} />
      <Stack.Screen name="sale-new" />
      <Stack.Screen name="sale-detail" />
      <Stack.Screen name="expenses" />
      <Stack.Screen name="expense-new" />
      <Stack.Screen name="profile" />
      <Stack.Screen name="cross-branch" />
      <Stack.Screen name="daily-overview" />
    </Stack>
  );
}
