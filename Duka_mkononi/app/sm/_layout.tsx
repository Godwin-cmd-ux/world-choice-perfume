import { Stack, router, usePathname } from 'expo-router';
import { useEffect } from 'react';
import { COLORS } from '../../lib/theme';
import { staffSession } from '../../lib/staffSession';

/**
 * Root of the Stock Manager module.
 *
 * The guard below is convenience, not security — every /api/sm endpoint
 * independently re-verifies the session token and the stock_manager role on
 * the server (EnsureStaffSessionApi), and the products-only / cross-branch
 * branch rules are re-read from the branches table on every call. This only
 * stops a non-Stock-Manager who is already signed in from staring at empty
 * screens; if the server ever says 401/403, each screen kicks the user back
 * to /staff as well.
 */
function useStockManagerGuard() {
  const pathname = usePathname();

  useEffect(() => {
    const identity = staffSession.getIdentity();
    if (!identity || identity.role !== 'stock_manager' || !staffSession.getSessionToken()) {
      router.replace('/staff');
    }
  }, [pathname]);
}

export default function SmLayout() {
  useStockManagerGuard();

  return (
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: COLORS.bg },
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen name="(tabs)" options={{ animation: 'fade' }} />
      <Stack.Screen name="stock-entry" />
      <Stack.Screen name="stock-edit" />
      <Stack.Screen name="movements" />
      <Stack.Screen name="low-stock" />
      <Stack.Screen name="bottle-stock" />
      <Stack.Screen name="oil-fragrance" />
      <Stack.Screen name="accessories" />
      <Stack.Screen name="sales" />
      <Stack.Screen name="sale-new" />
      <Stack.Screen name="orders" />
      <Stack.Screen name="products" />
      <Stack.Screen name="product-form" />
      <Stack.Screen name="transfer-new" />
      <Stack.Screen name="transfer-detail" />
      <Stack.Screen name="incoming" />
      <Stack.Screen name="returns" />
      <Stack.Screen name="returned-stock" />
      <Stack.Screen name="cross-branch" />
      <Stack.Screen name="profile" />
    </Stack>
  );
}
