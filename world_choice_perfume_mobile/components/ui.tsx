import { Ionicons } from '@expo/vector-icons';
import type { ReactNode } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { COLORS, GOLD_SHADOW, RADIUS } from '../lib/theme';

/** Full-screen loading state in brand colours. */
export function LoadingView({ label = 'Loading…' }: { label?: string }) {
  return (
    <View style={styles.center}>
      <ActivityIndicator size="large" color={COLORS.gold} />
      <Text style={styles.centerMuted}>{label}</Text>
    </View>
  );
}

/** Error state with a retry action — never shows raw server internals. */
export function ErrorView({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <View style={styles.center}>
      <View style={styles.errorIcon}>
        <Ionicons name="alert-circle-outline" size={30} color={COLORS.danger} />
      </View>
      <Text style={styles.errorText}>{message}</Text>
      {onRetry ? (
        <Pressable
          onPress={onRetry}
          style={({ pressed }) => [styles.outlineButton, pressed && styles.pressed]}
          accessibilityRole="button"
        >
          <Ionicons name="refresh-outline" size={16} color={COLORS.gold} />
          <Text style={styles.outlineButtonText}>Try Again</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

/** Friendly empty state (e.g. no orders / no products matched). */
export function EmptyView({
  icon = 'search-outline',
  title,
  hint,
}: {
  icon?: keyof typeof Ionicons.glyphMap;
  title: string;
  hint?: string;
}) {
  return (
    <View style={styles.center}>
      <View style={styles.emptyIcon}>
        <Ionicons name={icon} size={28} color={COLORS.textMuted} />
      </View>
      <Text style={styles.emptyTitle}>{title}</Text>
      {hint ? <Text style={styles.centerMuted}>{hint}</Text> : null}
    </View>
  );
}

/** Primary gold button — the website's `from-gold-500 to-gold-600` CTA. */
export function GoldButton({
  label,
  onPress,
  disabled = false,
  loading = false,
  icon,
  style,
}: {
  label: string;
  onPress: () => void;
  disabled?: boolean;
  loading?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  style?: object;
}) {
  const off = disabled || loading;
  return (
    <Pressable
      onPress={onPress}
      disabled={off}
      style={({ pressed }) => [styles.goldButton, GOLD_SHADOW, style, pressed && styles.pressed, off && styles.disabled]}
      accessibilityRole="button"
    >
      {loading ? (
        <ActivityIndicator color="#1A1400" />
      ) : (
        <>
          {icon ? <Ionicons name={icon} size={18} color="#1A1400" /> : null}
          <Text style={styles.goldButtonText}>{label}</Text>
        </>
      )}
    </Pressable>
  );
}

/** Secondary (outlined gold) button. */
export function OutlineButton({
  label,
  onPress,
  icon,
  style,
}: {
  label: string;
  onPress: () => void;
  icon?: keyof typeof Ionicons.glyphMap;
  style?: object;
}) {
  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [styles.outlineButton, style, pressed && styles.pressed]}
      accessibilityRole="button"
    >
      {icon ? <Ionicons name={icon} size={16} color={COLORS.gold} /> : null}
      <Text style={styles.outlineButtonText}>{label}</Text>
    </Pressable>
  );
}

/** Eyebrow + heading used by every section (matches website section headers). */
export function SectionHeading({ eyebrow, title, accent }: { eyebrow: string; title: string; accent?: string }) {
  return (
    <View style={styles.heading}>
      <Text style={styles.eyebrow}>{eyebrow}</Text>
      <Text style={styles.headingTitle}>
        {title}
        {accent ? <Text style={styles.headingAccent}> {accent}</Text> : null}
      </Text>
    </View>
  );
}

/** Card container shared by all screens. */
export function Card({ children, style }: { children: ReactNode; style?: object }) {
  return <View style={[styles.card, style]}>{children}</View>;
}

const styles = StyleSheet.create({
  center: {
    flexGrow: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
    paddingVertical: 48,
    gap: 12,
  },
  centerMuted: {
    color: COLORS.textSecondary,
    fontSize: 14,
    textAlign: 'center',
    lineHeight: 20,
  },
  errorIcon: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: COLORS.dangerBg,
    borderWidth: 1,
    borderColor: COLORS.dangerBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  errorText: {
    color: COLORS.text,
    fontSize: 14,
    lineHeight: 21,
    textAlign: 'center',
  },
  emptyIcon: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
    textAlign: 'center',
  },
  goldButton: {
    minHeight: 50,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.gold,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingHorizontal: 20,
    paddingVertical: 14,
  },
  goldButtonText: {
    color: '#1A1400',
    fontSize: 15,
    fontWeight: '800',
    letterSpacing: 0.4,
  },
  outlineButton: {
    minHeight: 46,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    backgroundColor: 'transparent',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingHorizontal: 18,
    paddingVertical: 12,
  },
  outlineButtonText: {
    color: COLORS.gold,
    fontSize: 14,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  pressed: { opacity: 0.82 },
  disabled: { opacity: 0.55 },
  heading: { marginBottom: 14 },
  eyebrow: {
    color: COLORS.goldDark,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 3,
    textTransform: 'uppercase',
    marginBottom: 6,
  },
  headingTitle: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '800',
  },
  headingAccent: { color: COLORS.gold },
  card: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },
});
