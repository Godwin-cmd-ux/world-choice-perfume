import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useCart } from '../lib/cart';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * The shopping screens' cart entry point: a bag icon with a count badge,
 * opening the cart/checkout screen. Hidden count when the cart is empty so
 * the icon stays quiet until there is something to check out.
 */
export function CartButton() {
  const { count } = useCart();

  return (
    <Pressable
      onPress={() => router.push('/cart')}
      style={({ pressed }) => [styles.button, pressed && styles.pressed]}
      hitSlop={8}
      accessibilityRole="button"
      accessibilityLabel={count > 0 ? `Cart, ${count} items` : 'Cart'}
    >
      <Ionicons name="cart-outline" size={20} color={COLORS.gold} />
      {count > 0 ? (
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{count > 99 ? '99+' : count}</Text>
        </View>
      ) : null}
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
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginLeft: 6,
  },
  pressed: { opacity: 0.7 },
  badge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 18,
    height: 18,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 4,
    backgroundColor: COLORS.gold,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    color: '#1A1400',
    fontSize: 10,
    fontWeight: '800',
  },
});
