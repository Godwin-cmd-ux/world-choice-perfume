import { Stack, router, usePathname } from 'expo-router';
import { useEffect } from 'react';
import { View } from 'react-native';
import { GdSidebar } from '../../components/gdsidebar';
import { COLORS } from '../../lib/theme';
import { staffSession } from '../../lib/staffSession';

/**
 * Root of the Graphic Designer module.
 *
 * The guard below is convenience, not security — every /api/gd endpoint
 * independently re-verifies the session token and the graphic_designer role
 * on the server (EnsureStaffSessionApi). This only stops a non-Designer who
 * is already signed in from staring at empty screens; if the server ever says
 * 401/403, each screen kicks the user back to /staff as well.
 */
function useGraphicDesignerGuard() {
  const pathname = usePathname();

  useEffect(() => {
    const identity = staffSession.getIdentity();
    if (!identity || identity.role !== 'graphic_designer' || !staffSession.getSessionToken()) {
      router.replace('/staff');
    }
  }, [pathname]);
}

export default function GdLayout() {
  useGraphicDesignerGuard();

  return (
    // The sidebar is the module's navigation (it mirrors the website's
    // graphic-designer sidebar), so it lives above the whole stack and every
    // screen can open it — the AdminPage menu button or GdMenuButton.
    <View style={{ flex: 1, backgroundColor: COLORS.bg }}>
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: COLORS.bg },
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen name="(tabs)" options={{ animation: 'fade' }} />
      <Stack.Screen name="news-form" />
      <Stack.Screen name="brand-form" />
      <Stack.Screen name="qr-code" />
      <Stack.Screen name="profile" />
    </Stack>
      <GdSidebar />
    </View>
  );
}
