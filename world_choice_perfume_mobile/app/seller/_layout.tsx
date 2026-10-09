import { Stack, router, usePathname } from 'expo-router';
import { useEffect } from 'react';
import { View } from 'react-native';
import { SellerSidebar } from '../../components/sellersidebar';
import { COLORS } from '../../lib/theme';
import { staffSession } from '../../lib/staffSession';

/**
 * Root of the Seller module.
 *
 * The guard below is convenience, not security — every /api/seller endpoint
 * independently re-verifies the session token and the seller role on the
 * server (EnsureStaffSessionApi), sales are scoped to the member's own
 * cashier_id and the order tabs run isolated inside the controllers. This
 * only stops a non-Seller member who is already signed in from staring at
 * empty screens; every screen also kicks the user back to /staff when the
 * server answers 401/403.
 */
function useSellerGuard() {
  const pathname = usePathname();

  useEffect(() => {
    const identity = staffSession.getIdentity();
    if (!identity || identity.role !== 'seller' || !staffSession.getSessionToken()) {
      router.replace('/staff');
    }
  }, [pathname]);
}

export default function SellerLayout() {
  useSellerGuard();

  return (
    // The sidebar is the module's navigation (it mirrors the website's
    // seller sidebar), so it lives above the whole stack and every screen
    // can open it through the AdminPage menu button.
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
    </Stack>
      <SellerSidebar />
    </View>
  );
}
