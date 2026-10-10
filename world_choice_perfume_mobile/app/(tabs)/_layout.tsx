import { Ionicons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { SwipeTabScreen } from '../../components/swipetabs';
import { COLORS, useTheme } from '../../lib/theme';

/**
 * Public (signed-out) navigation for the World Choice Perfume app.
 *
 * The landing page used to be a logo plus six menu buttons — HOME,
 * SHOPPING, TRACK ORDERS, CONTACTS, NEWS, STAFF LOGIN. Those six
 * navigations now live in this bottom tab bar, in the same order, and the
 * app opens straight onto the restyled home page (index — the website's
 * landing blade, home.blade.php, section for section) after the native
 * splash. The tab bar keeps the brand's deep-black + gold treatment; the
 * group folder is transparent, so every screen keeps its old URL
 * (/shop, /track, /contacts, /news, /staff-access).
 *
 * Tapping the bar is not the only way across: `screenLayout` wraps every
 * screen in the swipe layer (components/swipetabs.tsx), so a left/right swipe
 * moves to the next/previous tab. `animation: 'shift'` makes the incoming tab
 * slide in the direction of the swipe, so the screen follows the finger
 * rather than appearing without it.
 */
export default function PublicTabsLayout() {
  // The bar sits flush with the bottom edge, so its labels have to clear the
  // home indicator (iOS) and the gesture/navigation bar (Android edge-to-edge)
  // or they render behind the system UI and the taps land on the system bar.
  // tabBarStyle is merged last, which also replaces the library's own
  // `paddingBottom: insets.bottom` — the inset has to be added back here.
  const insets = useSafeAreaInsets();
  const bottomInset = Math.max(insets.bottom, 8);
  // Subscribe so screenOptions (inline colours like the bar background)
  // re-evaluate the moment the user toggles the theme.
  useTheme();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        // Keep the bar from hovering squeezed above the keyboard while a
        // form field is focused — hide it instead.
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: COLORS.gold,
        tabBarInactiveTintColor: COLORS.textMuted,
        tabBarStyle: {
          backgroundColor: COLORS.bgRaised,
          borderTopColor: COLORS.border,
          borderTopWidth: 1,
          height: 54 + bottomInset,
          paddingBottom: bottomInset,
          paddingTop: 6,
        },
        tabBarLabelStyle: { fontSize: 10, fontWeight: '700', letterSpacing: 0.3 },
        // The swipe needs the tab to arrive the way the finger went, or the
        // gesture reads as a jump cut.
        animation: 'shift',
        sceneStyle: { backgroundColor: COLORS.bg },
      }}
      // One swipe for every tab, applied by the navigator rather than screen by
      // screen — 'shift' above makes the incoming tab slide the way the finger
      // went. (Not inside screenOptions: screenLayout is a navigator prop.)
      screenLayout={({ children, navigation }) => (
        <SwipeTabScreen navigation={navigation}>{children}</SwipeTabScreen>
      )}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="shop"
        options={{
          title: 'Shop',
          tabBarIcon: ({ color, size }) => <Ionicons name="bag-handle-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="track"
        options={{
          title: 'Track',
          tabBarIcon: ({ color, size }) => <Ionicons name="cube-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="contacts"
        options={{
          title: 'Contact',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="chatbubble-ellipses-outline" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="news"
        options={{
          title: 'News',
          tabBarIcon: ({ color, size }) => <Ionicons name="newspaper-outline" size={size} color={color} />,
        }}
      />
      <Tabs.Screen
        name="staff-access"
        options={{
          title: 'Staff',
          tabBarIcon: ({ color, size }) => <Ionicons name="lock-closed-outline" size={size} color={color} />,
        }}
      />
    </Tabs>
  );
}
