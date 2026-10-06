import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';

// World Choice Perfume — root layout.
// Minimal shell: a single stack with the header hidden. Add screens under
// `app/` and they are picked up automatically by expo-router.
export default function RootLayout() {
  return (
    <>
      <StatusBar style="light" />
      <Stack
        screenOptions={{
          headerShown: false,
          contentStyle: { backgroundColor: '#0B0B0D' },
        }}
      />
    </>
  );
}
