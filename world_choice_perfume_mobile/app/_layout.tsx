import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { CartProvider } from '../lib/cart';
import { COLORS, hydrateTheme, useTheme } from '../lib/theme';

// World Choice Perfume — root layout.
// Minimal shell: a single stack with the header hidden. The native splash
// (expo-splash-screen, configured in app.json) covers app launch; the stack
// below it starts at the (tabs) group — the restyled home page with the
// bottom tab bar that holds the six navigations the old landing menu had
// as buttons. Add screens under `app/` and expo-router picks them up
// automatically.
//
// GestureHandlerRootView wraps the whole tree because the tab bar answers
// swipes now (components/swipetabs.tsx). Without it a gesture handler on
// Android has no native root to attach to and the swipe silently does nothing
// — mounted here, once, above the navigator.
export default function RootLayout() {
  // The current mode: light (default) or dark.
  const { mode, isDark } = useTheme();

  // Restore the saved mode (light is the default until the user toggles on a
  // device that has never run the app).
  useEffect(() => {
    hydrateTheme();
  }, []);

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <CartProvider>
        <StatusBar style={isDark ? 'light' : 'dark'} />
        {/*
          Keyed by the mode, and that key is what makes a toggle visible.

          lib/theme.ts swaps the palette in place (COLORS plus every registered
          StyleSheet entry), but a screen only paints the swapped values when it
          re-renders — and React Navigation keeps the mounted screens' elements,
          so subscribing to the theme here re-rendered the navigator's own chrome
          and left every screen showing the old colours until a reload.
          Remounting the navigator re-applies the whole tree at once: registered
          style sheets, inline COLORS reads and the StatusBar alike.

          Safe to remount: the cart lives above this key (it survives), a saved
          mode is restored before the first paint through hydrateTheme(), and on
          web the URL keeps the screen the customer was on.
        */}
        <Stack
          key={mode}
          screenOptions={{
            headerShown: false,
            contentStyle: { backgroundColor: COLORS.bg },
            animation: 'slide_from_right',
          }}
        >
          <Stack.Screen name="(tabs)" options={{ animation: 'fade' }} />
        </Stack>
      </CartProvider>
    </GestureHandlerRootView>
  );
}
