import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, GOLD_SHADOW, RADIUS } from '../lib/theme';

/**
 * World Choice Perfumes — main landing / menu page.
 *
 * Shown right after the splash screen: the official logo as a small brand
 * header, then the section buttons. Every button opens its own dedicated
 * page — CONTACTS is the website's Contact Us portal and NEWS the website's
 * /news page; STAFF LOGIN opens the secret-code prompt (like the website's
 * Staff Login modal) instead of going anywhere directly.
 */
const MENU = [
  { key: 'home', label: 'HOME', icon: 'home-outline', href: '/home' },
  { key: 'shopping', label: 'SHOPPING', icon: 'bag-handle-outline', href: '/shop' },
  { key: 'track', label: 'TRACK ORDERS', icon: 'cube-outline', href: '/track' },
  { key: 'contacts', label: 'CONTACTS', icon: 'chatbubble-ellipses-outline', href: '/contacts' },
  { key: 'news', label: 'NEWS', icon: 'newspaper-outline', href: '/news' },
  { key: 'staff', label: 'STAFF LOGIN', icon: 'lock-closed-outline', href: '/staff-access' },
] as const;

export default function LandingScreen() {
  return (
    <SafeAreaView style={styles.safe}>
      {/* Faint gold halos — the website's hero ambience, no extra deps. */}
      <View pointerEvents="none" style={styles.haloTop} />
      <View pointerEvents="none" style={styles.haloBottom} />

      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
      >
        {/* Brand header: official logo, small, centred, original aspect. */}
        <View style={styles.brand}>
          <View style={styles.logoRing}>
            <Image
              source={require('../assets/images/logo.jpeg')}
              style={styles.logo}
              resizeMode="contain"
              accessibilityLabel="World Choice Perfume logo"
            />
          </View>
          <Text style={styles.title}>
            World Choice <Text style={styles.titleGold}>Perfume</Text>
          </Text>
          <Text style={styles.tagline}>BE SMART, NUKIA KIJANJA</Text>
        </View>

        {/* The section buttons — full width minus comfortable margins. */}
        <View style={styles.menu}>
          {MENU.map((item) => (
            <Pressable
              key={item.key}
              onPress={() => router.push(item.href)}
              style={({ pressed }) => [styles.button, GOLD_SHADOW, pressed && styles.buttonPressed]}
              accessibilityRole="button"
              accessibilityLabel={item.label}
            >
              <Ionicons name={item.icon} size={19} color="#1A1400" />
              <Text style={styles.buttonLabel}>{item.label}</Text>
            </Pressable>
          ))}
        </View>

        <Text style={styles.footer}>Tanzania’s #1 Fragrance Store</Text>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  haloTop: {
    position: 'absolute',
    top: -80,
    right: -60,
    width: 240,
    height: 240,
    borderRadius: 120,
    backgroundColor: COLORS.goldHalo,
  },
  haloBottom: {
    position: 'absolute',
    bottom: -100,
    left: -80,
    width: 300,
    height: 300,
    borderRadius: 150,
    backgroundColor: 'rgba(66, 52, 14, 0.18)',
  },
  content: {
    flexGrow: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 26,
    paddingVertical: 32,
  },
  brand: {
    alignItems: 'center',
    marginBottom: 38,
  },
  logoRing: {
    width: 92,
    height: 92,
    borderRadius: 46,
    borderWidth: 1.5,
    borderColor: COLORS.goldBorder,
    backgroundColor: COLORS.surface,
    padding: 3,
    marginBottom: 18,
  },
  logo: {
    width: '100%',
    height: '100%',
    borderRadius: 43,
  },
  title: {
    color: COLORS.text,
    fontSize: 27,
    fontWeight: '800',
    textAlign: 'center',
    letterSpacing: 0.4,
  },
  titleGold: {
    color: COLORS.gold,
  },
  tagline: {
    color: 'rgba(255, 193, 7, 0.65)',
    fontSize: 11,
    letterSpacing: 3.5,
    fontWeight: '600',
    marginTop: 8,
  },
  menu: {
    width: '100%',
    maxWidth: 420,
    gap: 14,
  },
  button: {
    minHeight: 56,
    borderRadius: RADIUS.lg,
    backgroundColor: COLORS.gold,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    paddingHorizontal: 18,
  },
  buttonPressed: {
    backgroundColor: COLORS.goldDark,
    opacity: 0.92,
  },
  buttonLabel: {
    color: '#1A1400',
    fontSize: 16,
    fontWeight: '800',
    letterSpacing: 1.6,
  },
  footer: {
    color: COLORS.textMuted,
    fontSize: 12,
    letterSpacing: 1.2,
    marginTop: 34,
  },
});
