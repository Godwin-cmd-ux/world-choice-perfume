/**
 * Light/dark mode switch.
 *
 * Light is the app's default mode; the choice is persisted by lib/theme.ts
 * and restored on launch. The button shows the mode you would switch TO
 * (moon while light, sun while dark) and reflects its state for screen
 * readers via accessibilityRole="switch".
 */
import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text } from 'react-native';
import { COLORS, useTheme } from '../lib/theme';

export function ThemeToggle({ showLabel = false }: { showLabel?: boolean }) {
  const { isDark, toggle } = useTheme();

  return (
    <Pressable
      onPress={toggle}
      style={({ pressed }) => [styles.button, pressed && styles.pressed]}
      accessibilityRole="switch"
      accessibilityState={{ checked: isDark }}
      accessibilityLabel={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
      hitSlop={8}
    >
      <Ionicons name={isDark ? 'sunny-outline' : 'moon-outline'} size={18} color={COLORS.gold} />
      {showLabel ? <Text style={styles.label}>{isDark ? 'Light' : 'Dark'}</Text> : null}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 6,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  pressed: { opacity: 0.7 },
  label: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '700',
  },
});
