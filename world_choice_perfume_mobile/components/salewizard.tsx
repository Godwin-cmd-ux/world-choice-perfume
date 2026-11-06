import { Pressable, StyleSheet, Text, View } from 'react-native';
import { BUTTON, COLORS, RADIUS } from '../lib/theme';
import { GoldButton } from './ui';

/** The sale is answered in one fixed order, on web and in the app alike. */
export const SALE_STEPS = ['Sale type', 'Customer', 'Products', 'Payment'] as const;

/**
 * Numbered progress rail for the new-sale screens. The active step carries the
 * module accent, finished steps stay filled, and later steps are muted until
 * the flow reaches them.
 */
export function SaleSteps({
  step,
  accent,
  onGo,
}: {
  step: number;
  accent: string;
  onGo: (step: number) => void;
}) {
  return (
    <View style={styles.rail}>
      {SALE_STEPS.map((label, index) => {
        const active = index === step;
        const done = index < step;
        return (
          <Pressable
            key={label}
            onPress={() => onGo(index)}
            style={({ pressed }) => [styles.step, pressed && styles.pressed]}
            accessibilityRole="button"
            accessibilityState={{ selected: active }}
            accessibilityLabel={`Step ${index + 1}: ${label}`}
          >
            <View
              style={[
                styles.dot,
                active && { backgroundColor: accent, borderColor: accent },
                !active && done && { borderColor: accent, backgroundColor: COLORS.bgRaised },
                !active && !done && { opacity: 0.55 },
              ]}
            >
              <Text style={[styles.dotText, (active || done) && { color: active ? BUTTON.text : accent }]}>
                {index + 1}
              </Text>
            </View>
            <Text
              style={[styles.stepLabel, active && { color: accent, fontWeight: '800' }]}
              numberOfLines={1}
            >
              {label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

/**
 * Actions under a step: an optional Back, an optional Skip (the customer step
 * is the only one that can be skipped) and the Continue that moves on. The
 * final step passes no Continue and keeps its own Complete button.
 */
export function StepFooter({
  onBack,
  onSkip,
  skipLabel = 'Skip',
  onContinue,
  continueLabel = 'Continue',
  continueDisabled = false,
}: {
  onBack?: () => void;
  onSkip?: () => void;
  skipLabel?: string;
  onContinue?: () => void;
  continueLabel?: string;
  continueDisabled?: boolean;
}) {
  return (
    <View style={styles.footer}>
      {onBack || onSkip ? (
        <View style={styles.links}>
          {onBack ? (
            <Pressable onPress={onBack} hitSlop={8} accessibilityRole="button">
              <Text style={styles.back}>← Back</Text>
            </Pressable>
          ) : null}
          {onSkip ? (
            <Pressable onPress={onSkip} hitSlop={8} accessibilityRole="button">
              <Text style={styles.skip}>{skipLabel}</Text>
            </Pressable>
          ) : null}
        </View>
      ) : null}
      {onContinue ? (
        <GoldButton
          label={continueLabel}
          icon="arrow-forward-outline"
          onPress={onContinue}
          disabled={continueDisabled}
          style={{ marginTop: 4 }}
        />
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  rail: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: 10,
    paddingHorizontal: 6,
    marginBottom: 6,
  },
  step: { flex: 1, alignItems: 'center', gap: 4 },
  dot: {
    width: 26,
    height: 26,
    borderRadius: 13,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dotText: { color: COLORS.textMuted, fontSize: 12, fontWeight: '800' },
  stepLabel: { color: COLORS.textMuted, fontSize: 10, fontWeight: '600' },
  footer: { marginTop: 14, marginBottom: 6 },
  links: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 },
  back: { color: COLORS.textSecondary, fontSize: 13, fontWeight: '700' },
  skip: { color: COLORS.gold, fontSize: 13, fontWeight: '700', textDecorationLine: 'underline' },
  pressed: { opacity: 0.7 },
});
