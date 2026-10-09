import { Stack, router, usePathname } from 'expo-router';
import { useEffect } from 'react';
import { View } from 'react-native';
import { BaSidebar } from '../../components/basidebar';
import { COLORS } from '../../lib/theme';
import { staffSession } from '../../lib/staffSession';

/**
 * Root of the Branch Admin module.
 *
 * The guard below is convenience, not security — every /api/ba endpoint
 * independently re-verifies the session token and the branch_admin role on
 * the server (EnsureStaffSessionApi), and every query is scoped to the
 * member's own branch inside the controllers. This only stops a
 * non-Branch-Admin member who is already signed in from staring at empty
 * screens; every screen also kicks the user back to /staff when the server
 * answers 401/403.
 */
function useBranchAdminGuard() {
  const pathname = usePathname();

  useEffect(() => {
    const identity = staffSession.getIdentity();
    if (!identity || identity.role !== 'branch_admin' || !staffSession.getSessionToken()) {
      router.replace('/staff');
    }
  }, [pathname]);
}

export default function BaLayout() {
  useBranchAdminGuard();

  return (
    // The sidebar is the module's navigation (it mirrors the website's
    // branch-admin sidebar), so it lives above the whole stack and every
    // screen can open it through the AdminPage menu button.
    <View style={{ flex: 1, backgroundColor: COLORS.bg }}>
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
        <Stack.Screen name="staff" />
        <Stack.Screen name="staff-new" />
        <Stack.Screen name="expenses" />
        <Stack.Screen name="profile" />
      </Stack>
      <BaSidebar />
    </View>
  );
}
