import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { CartProvider } from '../lib/cart';
import { COLORS } from '../lib/theme';

// World Choice Perfume — root layout.
// Minimal shell: a single stack with the header hidden. The native splash
// (expo-splash-screen, configured in app.json) covers app launch; the stack
// below it starts at the landing page (index). Add screens under `app/` and
// expo-router picks them up automatically.
export default function RootLayout() {
  return (
    <CartProvider>
      <StatusBar style="light" />
      <Stack
        screenOptions={{
          headerShown: false,
          contentStyle: { backgroundColor: COLORS.bg },
          animation: 'slide_from_right',
        }}
      >
        <Stack.Screen name="index" options={{ animation: 'fade' }} />
        <Stack.Screen
          name="staff-access"
          options={{ presentation: 'modal', animation: 'slide_from_bottom' }}
        />
      </Stack>
    </CartProvider>
  );
}
