import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { CartProvider } from '../lib/cart';
import { COLORS } from '../lib/theme';

// World Choice Perfume — root layout.
// Minimal shell: a single stack with the header hidden. The native splash
// (expo-splash-screen, configured in app.json) covers app launch; the stack
// below it starts at the (tabs) group — the restyled home page with the
// bottom tab bar that holds the six navigations the old landing menu had
// as buttons. Add screens under `app/` and expo-router picks them up
// automatically.
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
        <Stack.Screen name="(tabs)" options={{ animation: 'fade' }} />
      </Stack>
    </CartProvider>
  );
}
