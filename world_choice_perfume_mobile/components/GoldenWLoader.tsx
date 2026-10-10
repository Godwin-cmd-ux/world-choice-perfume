import { AccessibilityInfo, Animated, Easing, Image, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import type { StyleProp, ViewStyle } from 'react-native';
import { useEffect, useMemo, useRef, useState } from 'react';
import { COLORS } from '../lib/theme';

/**
 * World Choice Perfume — Golden Signature W loader.
 *
 * The app's one loading indicator: the real brand mark
 * (assets/images/golden-w-mark.png, keyed off its black plate by
 * scripts/make-loader-mark.py) with a band of champagne light travelling across
 * it. Every screen that used to show a bare ActivityIndicator — LoadingView,
 * BusyOverlay, the gold button, the confirm dialog — renders this instead.
 *
 * HOW THE LIGHT MOVES (no new dependency):
 * React Native has no CSS masks and this project ships no SVG or gradient
 * library, so the mark cannot be "clipped" to a moving band. Instead the mark
 * sits still, and a second copy of it — tinted champagne — is drawn as a row of
 * narrow vertical slices of the SAME cached image, each faded in and out
 * independently. Because the slices reproduce the mark's exact shape, the
 * staggered fade reads as one highlight travelling across the symbol, and
 * because only opacity changes (native driver) the whole thing stays cheap.
 *
 * The wave layer is a LIGHT tint rather than a lower-opacity mark on purpose: on
 * the light theme a translucent gold mark over ivory goes darker as it fades in,
 * which would read as a shadow moving across the W instead of light.
 *
 * Timing (matches the website's CSS): one 2.0s cycle — the wave crosses over
 * ~1.1s, then the light settles and rests ~0.4s. Every slice's loop is exactly
 * CYCLE long, so the repeat is seamless with no jump at the wrap point.
 *
 * prefers-reduced-motion: the sweep is never started and the still mark is shown.
 */

type Variant = 'inline' | 'compact' | 'page';
/** gold = the brand artwork; ink/light = flat silhouettes for tight surfaces. */
type Tone = 'gold' | 'ink' | 'light';

const MARK = require('../assets/images/golden-w-mark.png');

const CYCLE = 2000; // one full sweep cycle, ms
const TRAVERSE = 1150; // how long the wave takes to cross the mark, ms
const RISE = 220; // a single slice lighting up
const FALL = 280; // …and fading again
const PEAK = 0.55; // how bright the travelling light gets

const SIZES: Record<Variant, number> = { inline: 20, compact: 40, page: 120 };
const SLICES: Record<Variant, number> = { inline: 8, compact: 14, page: 20 };

/** The resting mark: undefined keeps the artwork's own champagne gold. */
const BASE_TINT: Record<Tone, string | undefined> = {
  gold: undefined,
  ink: '#1A1400', // on the gold primary button
  light: '#FFFFFF', // on the dark danger button
};

/** The travelling light: a warm white sheen that stays visible on every surface. */
const WAVE_TINT: Record<Tone, string> = {
  gold: '#FFF6DE',
  ink: '#FFF6DE',
  light: '#FFD98A',
};

export function GoldenWLoader({
  variant = 'compact',
  tone = 'gold',
  brand = false,
  label,
  style,
}: {
  variant?: Variant;
  tone?: Tone;
  /** Show the WORLD CHOICE PERFUME wordmark under the mark. */
  brand?: boolean;
  /** Optional caption under the mark (e.g. "Loading dashboard…"). */
  label?: string | null;
  style?: StyleProp<ViewStyle>;
}) {
  const { width } = useWindowDimensions();
  const [reduceMotion, setReduceMotion] = useState(false);

  // Follow the system's reduced-motion switch, and drop the subscription on
  // unmount so a dismissed loader leaves nothing behind.
  useEffect(() => {
    let alive = true;
    AccessibilityInfo.isReduceMotionEnabled().then((enabled) => {
      if (alive) setReduceMotion(enabled);
    });
    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', (enabled) => {
      if (alive) setReduceMotion(enabled);
    });
    return () => {
      alive = false;
      subscription.remove();
    };
  }, []);

  // The page variant scales with the viewport instead of being desktop-sized.
  const size = variant === 'page' ? Math.round(Math.min(132, Math.max(96, width * 0.24))) : SIZES[variant];
  const count = SLICES[variant];
  const sliceWidth = size / count;

  const appear = useRef(new Animated.Value(0)).current;
  const progress = useMemo(() => Array.from({ length: count }, () => new Animated.Value(0)), [count]);

  // PHASE 1: the W settles into place (fade with a whisper of scale).
  useEffect(() => {
    if (reduceMotion) {
      appear.setValue(1);
      return;
    }
    const animation = Animated.timing(appear, {
      toValue: 1,
      duration: 320,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true,
    });
    animation.start();
    return () => animation.stop();
  }, [appear, reduceMotion]);

  // PHASE 2/3/4: the light crosses the mark, settles, then repeats.
  useEffect(() => {
    if (reduceMotion) return;
    const loops = progress.map((value, index) => {
      const phase = Math.round((index / count) * TRAVERSE);
      const rest = Math.max(0, CYCLE - RISE - FALL - phase);
      const loop = Animated.loop(
        Animated.sequence([
          Animated.delay(phase),
          Animated.timing(value, { toValue: 1, duration: RISE, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
          Animated.timing(value, { toValue: 0, duration: FALL, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
          Animated.delay(rest),
        ]),
      );
      loop.start();
      return loop;
    });
    // A dismissed loader must not leave twenty native loops animating.
    return () => loops.forEach((loop) => loop.stop());
  }, [progress, count, reduceMotion]);

  const baseTint = BASE_TINT[tone];
  const waveTint = WAVE_TINT[tone];

  return (
    <View
      style={[styles.root, style]}
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={label || 'Loading'}
    >
      <Animated.View
        style={{
          opacity: appear,
          transform: [{ scale: appear.interpolate({ inputRange: [0, 1], outputRange: [0.94, 1] }) }],
        }}
        // The mark is decoration; the label above carries the meaning.
        importantForAccessibility="no-hide-descendants"
        accessibilityElementsHidden
      >
        <View style={{ width: size, height: size }}>
          {/* The resting mark — always fully visible, never dimmed. */}
          <Image
            source={MARK}
            resizeMode="contain"
            style={[styles.layer, { width: size, height: size }, baseTint ? { tintColor: baseTint } : null]}
          />

          {/* PHASE 2: the travelling band of light over it. */}
          {progress.map((value, index) => (
            <Animated.View
              key={index}
              style={{
                position: 'absolute',
                left: index * sliceWidth,
                top: 0,
                width: Math.ceil(sliceWidth) + 1,
                height: size,
                overflow: 'hidden',
                opacity: value.interpolate({ inputRange: [0, 1], outputRange: [0, PEAK] }),
              }}
            >
              <Image
                source={MARK}
                resizeMode="contain"
                style={[styles.layer, { left: -index * sliceWidth, width: size, height: size, tintColor: waveTint }]}
              />
            </Animated.View>
          ))}
        </View>
      </Animated.View>

      {brand ? <Text style={styles.brand}>World Choice Perfume</Text> : null}
      {label ? <Text style={styles.label}>{label}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { alignItems: 'center', justifyContent: 'center', gap: 8 },
  layer: { position: 'absolute', top: 0 },
  brand: {
    color: COLORS.gold,
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 3,
    textTransform: 'uppercase',
  },
  label: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    fontWeight: '600',
    textAlign: 'center',
  },
});
