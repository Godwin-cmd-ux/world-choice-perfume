import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '../components/ScreenHeader';
import { Card, EmptyView, GoldButton, LoadingView, OutlineButton } from '../components/ui';
import { errorMessage, fetchProduct, placeOrder, type PlacedOrder } from '../lib/api';
import { useCart } from '../lib/cart';
import { formatMoney } from '../lib/format';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * Cart + checkout.
 *
 * The whole basket is posted to POST /api/orders — the very controller the
 * website's checkout uses — with the customer's name and phone. The server
 * re-checks stock and prices and returns the order number, so what the app
 * shows is always what the branch received.
 */
export default function CartScreen() {
  const { items, branchId, branchName, count, subtotal, ready, setQuantity, removeItem, clear } =
    useCart();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [notes, setNotes] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [placed, setPlaced] = useState<PlacedOrder | null>(null);

  // A line saved without a size (added by an older build, before the picker
  // rendered) is refused by the server for any product sold in sizes. Ask the
  // product endpoint once per such line so the cart can offer the missing
  // choice here instead of dead-ending at Place Order.
  const [needsVariety, setNeedsVariety] = useState<Record<string, boolean>>({});

  useEffect(() => {
    const unchecked = items.filter(
      (item) => item.volume == null && needsVariety[item.key] === undefined,
    );
    if (unchecked.length === 0) return;

    let active = true;
    (async () => {
      for (const item of unchecked) {
        let needs = false;
        try {
          const detail = await fetchProduct(String(item.productId), String(item.branchId));
          needs = (detail.varieties?.[String(item.branchId)] ?? []).length > 0;
        } catch {
          // Unknown from here — leave the line alone and let the server decide.
        }
        if (active) setNeedsVariety((map) => ({ ...map, [item.key]: needs }));
      }
    })();
    return () => {
      active = false;
    };
  }, [items, needsVariety]);

  /** Drop the incomplete line and open the product so its size picker can be
   *  used — the only place that choice can be made. */
  const chooseVariety = (item: (typeof items)[number]) => {
    removeItem(item.key);
    router.push({
      pathname: '/product',
      params: { id: String(item.productId), branch_id: String(item.branchId) },
    });
  };

  const canSubmit = items.length > 0 && name.trim() !== '' && phone.trim() !== '' && !submitting;

  const submit = async () => {
    if (!branchId) {
      setError('Your cart is missing a branch. Please add the item again.');
      return;
    }
    const trimmedName = name.trim();
    const trimmedPhone = phone.trim();
    if (!trimmedName) {
      setError('Please enter your name.');
      return;
    }
    if (!trimmedPhone) {
      setError('Please enter your phone number.');
      return;
    }

    const unpicked = items.find((item) => item.volume == null && needsVariety[item.key]);
    if (unpicked) {
      setError(`Choose the size for ${unpicked.productName} before placing your order.`);
      return;
    }

    setSubmitting(true);
    setError('');
    try {
      const payload = await placeOrder({
        branch_id: branchId,
        customer_name: trimmedName,
        customer_phone: trimmedPhone,
        customer_email: email.trim() || undefined,
        delivery_notes: notes.trim() || undefined,
        // Only the SIZE is sent: the server resolves which packaging bucket
        // to pack it from (Customer\OrderController@store).
        items: items.map((item) => ({
          product_id: item.productId,
          quantity: item.quantity,
          ...(item.volume ? { volume: item.volume } : {}),
        })),
      });
      setPlaced(payload.order);
      clear();
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setSubmitting(false);
    }
  };

  // --- Success -------------------------------------------------------
  if (placed) {
    return (
      <SafeAreaView style={styles.safe} edges={['top']}>
        <ScreenHeader title="Order Placed" subtitle="Shopping" />
        <ScrollView contentContainerStyle={styles.content}>
          <View style={styles.successIcon}>
            <Ionicons name="checkmark-circle" size={44} color={COLORS.success} />
          </View>
          <Text style={styles.successTitle}>Order placed successfully!</Text>
          <Text style={styles.successText}>
            Your order has been received and is being processed. Payment will be settled at the
            branch.
          </Text>
          <Card style={styles.summaryCard}>
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Order number</Text>
              <Text style={styles.summaryValue}>{placed.order_number}</Text>
            </View>
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Total</Text>
              <Text style={styles.summaryValue}>{formatMoney(placed.total)}</Text>
            </View>
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Status</Text>
              <Text style={styles.summaryValue}>{placed.status ?? 'pending'}</Text>
            </View>
          </Card>
          <GoldButton
            label="Track Your Order"
            icon="cube-outline"
            onPress={() => router.push('/track')}
            style={styles.full}
          />
          <OutlineButton
            label="Continue Shopping"
            icon="bag-handle-outline"
            onPress={() => router.replace('/shop')}
            style={styles.full}
          />
        </ScrollView>
      </SafeAreaView>
    );
  }

  // --- Empty ---------------------------------------------------------
  if (!ready) {
    return (
      <SafeAreaView style={styles.safe} edges={['top']}>
        <ScreenHeader title="Your Cart" subtitle="Shopping" />
        <LoadingView label="Loading your cart…" />
      </SafeAreaView>
    );
  }

  if (items.length === 0) {
    return (
      <SafeAreaView style={styles.safe} edges={['top']}>
        <ScreenHeader title="Your Cart" subtitle="Shopping" />
        <EmptyView
          icon="cart-outline"
          title="Your cart is empty"
          hint="Browse the collection and add a fragrance to place an order."
        />
        <View style={styles.emptyActions}>
          <GoldButton
            label="Start Shopping"
            icon="bag-handle-outline"
            onPress={() => router.push('/shop')}
            style={styles.full}
          />
        </View>
      </SafeAreaView>
    );
  }

  // --- Checkout ------------------------------------------------------
  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="Your Cart" subtitle="Shopping" />
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.flex}
      >
        <ScrollView
          contentContainerStyle={styles.content}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          <Card style={styles.card}>
            <View style={styles.cardHeader}>
              <Text style={styles.cardTitle}>Branch</Text>
              <Ionicons name="storefront-outline" size={16} color={COLORS.gold} />
            </View>
            <Text style={styles.branchName}>{branchName ?? 'Branch'}</Text>
            <Text style={styles.hint}>
              An order is placed with one branch. Adding items from another branch starts a new
              cart.
            </Text>
          </Card>

          <Card style={styles.card}>
            <View style={styles.cardHeader}>
              <Text style={styles.cardTitle}>
                Items ({count} {count === 1 ? 'item' : 'items'})
              </Text>
              <Pressable onPress={clear} hitSlop={8} accessibilityRole="button">
                <Text style={styles.clearText}>Clear</Text>
              </Pressable>
            </View>

            {items.map((item) => (
              <View key={item.key} style={styles.itemRow}>
                {item.image ? (
                  <Image source={{ uri: item.image }} style={styles.itemImage} contentFit="cover" />
                ) : (
                  <View style={[styles.itemImage, styles.itemImageFallback]}>
                    <Ionicons name="sparkles-outline" size={20} color={COLORS.textMuted} />
                  </View>
                )}

                <View style={styles.itemInfo}>
                  <Text style={styles.itemName} numberOfLines={2}>
                    {item.productName}
                  </Text>
                  {item.brand ? <Text style={styles.itemMeta}>{item.brand}</Text> : null}
                  {item.varietyLabel ? (
                    <Text style={styles.itemMeta}>{item.varietyLabel}</Text>
                  ) : null}
                  <Text style={styles.itemPrice}>{formatMoney(item.unitPrice)}</Text>
                  {item.volume == null && needsVariety[item.key] ? (
                    <Pressable
                      onPress={() => chooseVariety(item)}
                      style={styles.fixVariety}
                      accessibilityRole="button"
                      accessibilityLabel={`Choose a size for ${item.productName}`}
                    >
                      <Ionicons name="options-outline" size={13} color={COLORS.goldBright} />
                      <Text style={styles.fixVarietyText}>Choose a size</Text>
                    </Pressable>
                  ) : null}
                </View>

                <View style={styles.itemRight}>
                  <View style={styles.stepper}>
                    <Pressable
                      onPress={() => setQuantity(item.key, item.quantity - 1)}
                      disabled={item.quantity <= 1}
                      style={({ pressed }) => [
                        styles.stepButton,
                        item.quantity <= 1 && styles.stepDisabled,
                        pressed && styles.pressed,
                      ]}
                      accessibilityRole="button"
                      accessibilityLabel="Decrease quantity"
                    >
                      <Ionicons name="remove" size={16} color={COLORS.gold} />
                    </Pressable>
                    <Text style={styles.stepValue}>{item.quantity}</Text>
                    <Pressable
                      onPress={() => setQuantity(item.key, item.quantity + 1)}
                      disabled={item.available != null && item.quantity >= item.available}
                      style={({ pressed }) => [
                        styles.stepButton,
                        item.available != null && item.quantity >= item.available && styles.stepDisabled,
                        pressed && styles.pressed,
                      ]}
                      accessibilityRole="button"
                      accessibilityLabel="Increase quantity"
                    >
                      <Ionicons name="add" size={16} color={COLORS.gold} />
                    </Pressable>
                  </View>
                  <Pressable
                    onPress={() => removeItem(item.key)}
                    hitSlop={8}
                    style={({ pressed }) => [styles.removeButton, pressed && styles.pressed]}
                    accessibilityRole="button"
                    accessibilityLabel={`Remove ${item.productName}`}
                  >
                    <Ionicons name="trash-outline" size={17} color={COLORS.danger} />
                  </Pressable>
                </View>
              </View>
            ))}

            <View style={styles.subtotalRow}>
              <Text style={styles.subtotalLabel}>Subtotal</Text>
              <Text style={styles.subtotal}>{formatMoney(subtotal)}</Text>
            </View>
          </Card>

          <Card style={styles.card}>
            <Text style={styles.cardTitle}>Your details</Text>
            <TextInput
              value={name}
              onChangeText={setName}
              placeholder="Your name *"
              placeholderTextColor={COLORS.textMuted}
              style={styles.input}
              autoCorrect={false}
            />
            <TextInput
              value={phone}
              onChangeText={setPhone}
              placeholder="Phone / WhatsApp number *"
              placeholderTextColor={COLORS.textMuted}
              style={styles.input}
              keyboardType="phone-pad"
            />
            <TextInput
              value={email}
              onChangeText={setEmail}
              placeholder="Email (optional)"
              placeholderTextColor={COLORS.textMuted}
              style={styles.input}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TextInput
              value={notes}
              onChangeText={setNotes}
              placeholder="Delivery notes (optional)"
              placeholderTextColor={COLORS.textMuted}
              style={[styles.input, styles.inputMultiline]}
              multiline
            />
            <Text style={styles.hint}>
              We use your phone number to reach you about this order — you can track it any time
              from Track Orders.
            </Text>
          </Card>

          {error ? (
            <View style={styles.errorBox}>
              <Ionicons name="alert-circle-outline" size={16} color={COLORS.danger} />
              <Text style={styles.errorText}>{error}</Text>
            </View>
          ) : null}
        </ScrollView>

        <View style={styles.footerBar}>
          <GoldButton
            label={`Place Order · ${formatMoney(subtotal)}`}
            icon="checkmark-circle-outline"
            onPress={submit}
            loading={submitting}
            disabled={!canSubmit}
            style={styles.full}
          />
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  flex: { flex: 1 },
  content: {
    paddingHorizontal: 18,
    paddingBottom: 24,
    gap: 14,
  },
  card: { gap: 10 },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  cardTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  clearText: {
    color: COLORS.danger,
    fontSize: 12.5,
    fontWeight: '700',
  },
  branchName: {
    color: COLORS.gold,
    fontSize: 15,
    fontWeight: '700',
  },
  hint: {
    color: COLORS.textMuted,
    fontSize: 12,
    lineHeight: 18,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 10,
  },
  itemImage: {
    width: 54,
    height: 54,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.surfaceHigh,
  },
  itemImageFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  itemInfo: { flex: 1, gap: 2 },
  itemName: {
    color: COLORS.text,
    fontSize: 13.5,
    fontWeight: '700',
  },
  itemMeta: {
    color: COLORS.textMuted,
    fontSize: 11.5,
  },
  itemPrice: {
    color: COLORS.gold,
    fontSize: 13.5,
    fontWeight: '700',
    marginTop: 2,
  },
  itemRight: {
    alignItems: 'flex-end',
    gap: 8,
  },
  stepper: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
  },
  stepButton: {
    width: 32,
    height: 32,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  stepDisabled: { opacity: 0.4 },
  stepValue: {
    minWidth: 26,
    textAlign: 'center',
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  removeButton: {
    padding: 2,
  },
  fixVariety: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    alignSelf: 'flex-start',
    marginTop: 4,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  fixVarietyText: {
    color: COLORS.goldBright,
    fontSize: 11.5,
    fontWeight: '700',
  },
  subtotalRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 10,
    marginTop: 2,
  },
  subtotalLabel: {
    color: COLORS.textSecondary,
    fontSize: 14,
    fontWeight: '700',
  },
  subtotal: {
    color: COLORS.gold,
    fontSize: 18,
    fontWeight: '800',
  },
  input: {
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    paddingVertical: 12,
    color: COLORS.text,
    fontSize: 14,
  },
  inputMultiline: {
    minHeight: 72,
    textAlignVertical: 'top',
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.dangerBg,
    borderWidth: 1,
    borderColor: COLORS.dangerBorder,
    borderRadius: RADIUS.md,
    padding: 12,
  },
  errorText: {
    color: COLORS.danger,
    fontSize: 13,
    flex: 1,
  },
  footerBar: {
    paddingHorizontal: 18,
    paddingTop: 10,
    paddingBottom: 14,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    backgroundColor: COLORS.bg,
  },
  full: { width: '100%' },
  emptyActions: {
    paddingHorizontal: 18,
    paddingBottom: 24,
  },
  successIcon: {
    alignSelf: 'center',
    width: 84,
    height: 84,
    borderRadius: 42,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.successBg,
    borderWidth: 1,
    borderColor: COLORS.successBorder,
    marginTop: 12,
  },
  successTitle: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '800',
    textAlign: 'center',
  },
  successText: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 20,
    textAlign: 'center',
  },
  summaryCard: { gap: 8 },
  summaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  summaryLabel: {
    color: COLORS.textMuted,
    fontSize: 13,
  },
  summaryValue: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
    flexShrink: 1,
    textAlign: 'right',
  },
  pressed: { opacity: 0.8 },
});
