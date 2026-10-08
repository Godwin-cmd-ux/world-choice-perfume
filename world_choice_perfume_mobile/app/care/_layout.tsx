import { Stack, router, usePathname } from 'expo-router';
import { useEffect } from 'react';
import { COLORS } from '../../lib/theme';
import { staffSession } from '../../lib/staffSession';

/**
 * Root of the Customer Care module.
 *
 * The guard below is convenience, not security — every /api/care endpoint
 * independently re-verifies the session token and the customer_care role on
 * the server (EnsureStaffSessionApi), and the Head Quarters gate for
 * inquiries, news and the info@ mailbox is re-read from the branch name on
 * every call (assertHq). This only stops a non-Customer-Care member who is
 * already signed in from staring at empty screens; every screen also kicks
 * the user back to /staff when the server answers 401/403.
 */
function useCustomerCareGuard() {
  const pathname = usePathname();

  useEffect(() => {
    const identity = staffSession.getIdentity();
    if (!identity || identity.role !== 'customer_care' || !staffSession.getSessionToken()) {
      router.replace('/staff');
    }
  }, [pathname]);
}

export default function CareLayout() {
  useCustomerCareGuard();

  return (
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: COLORS.bg },
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen name="(tabs)" options={{ animation: 'fade' }} />
      <Stack.Screen name="customer-new" />
      <Stack.Screen name="customer-detail" />
      <Stack.Screen name="sale-new" />
      <Stack.Screen name="sale-detail" />
      <Stack.Screen name="inquiries" />
      <Stack.Screen name="news" />
      <Stack.Screen name="news-form" />
      <Stack.Screen name="mails" />
      <Stack.Screen name="mail-detail" />
    </Stack>
  );
}
