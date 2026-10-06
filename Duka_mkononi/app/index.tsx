import { Ionicons } from '@expo/vector-icons';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

// Brand palette shared with the website (dark base + gold accent).
const COLORS = {
  bg: '#0B0B0D',
  surface: '#16161A',
  border: '#2A2A31',
  gold: '#D4AF37',
  goldSoft: 'rgba(212, 175, 55, 0.14)',
  text: '#F5F5F7',
  muted: '#9A9AA5',
};

const FEATURES = [
  { icon: 'sparkles-outline' as const, label: 'Authentic' },
  { icon: 'diamond-outline' as const, label: 'Premium' },
  { icon: 'car-outline' as const, label: 'Fast delivery' },
];

export default function WelcomeScreen() {
  const comingSoon = (what: string) =>
    Alert.alert('World Choice Perfume', `${what} is coming soon.`);

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.container}>
        <View style={styles.brandMark}>
          <Ionicons name="sparkles" size={34} color={COLORS.gold} />
        </View>

        <Text style={styles.eyebrow}>WORLD CHOICE</Text>
        <Text style={styles.title}>
          World Choice <Text style={styles.titleGold}>Perfume</Text>
        </Text>
        <Text style={styles.tagline}>
          Authentic &amp; premium fragrances, delivered to your door.
        </Text>

        <View style={styles.features}>
          {FEATURES.map((feature) => (
            <View key={feature.label} style={styles.feature}>
              <Ionicons name={feature.icon} size={18} color={COLORS.gold} />
              <Text style={styles.featureLabel}>{feature.label}</Text>
            </View>
          ))}
        </View>

        <View style={styles.actions}>
          <Pressable
            style={({ pressed }) => [styles.primaryButton, pressed && styles.pressed]}
            onPress={() => comingSoon('Shopping')}
          >
            <Ionicons name="bag-handle-outline" size={18} color="#1A1400" />
            <Text style={styles.primaryButtonText}>Start Shopping</Text>
          </Pressable>

          <Pressable
            style={({ pressed }) => [styles.secondaryButton, pressed && styles.pressed]}
            onPress={() => comingSoon('Sign in')}
          >
            <Text style={styles.secondaryButtonText}>Sign In</Text>
          </Pressable>
        </View>

        <Text style={styles.footer}>Fresh start — ready to build.</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 28,
  },
  brandMark: {
    width: 76,
    height: 76,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.goldSoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 26,
  },
  eyebrow: {
    color: COLORS.gold,
    fontSize: 12,
    letterSpacing: 4,
    fontWeight: '700',
    marginBottom: 10,
  },
  title: {
    color: COLORS.text,
    fontSize: 32,
    fontWeight: '800',
    textAlign: 'center',
    lineHeight: 40,
  },
  titleGold: {
    color: COLORS.gold,
  },
  tagline: {
    color: COLORS.muted,
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    marginTop: 14,
    maxWidth: 320,
  },
  features: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 28,
    flexWrap: 'wrap',
    justifyContent: 'center',
  },
  feature: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
  },
  featureLabel: {
    color: COLORS.text,
    fontSize: 13,
  },
  actions: {
    width: '100%',
    maxWidth: 340,
    marginTop: 40,
    gap: 12,
  },
  primaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: COLORS.gold,
    borderRadius: 14,
    paddingVertical: 16,
  },
  primaryButtonText: {
    color: '#1A1400',
    fontSize: 16,
    fontWeight: '700',
  },
  secondaryButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 14,
    paddingVertical: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  secondaryButtonText: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '600',
  },
  pressed: {
    opacity: 0.8,
  },
  footer: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 26,
  },
});
