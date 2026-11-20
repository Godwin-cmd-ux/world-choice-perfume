import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CartButton } from '../components/CartButton';
import { ScreenHeader } from '../components/ScreenHeader';
import { Card, ErrorView, GoldButton, LoadingView, OutlineButton } from '../components/ui';
import {
  CUSTOMER_ORDER_BRANCH_ID,
  errorMessage,
  fetchProduct,
  type ProductDetailPayload,
} from '../lib/api';
import { useCart } from '../lib/cart';
import { formatMoney } from '../lib/format';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * Product details — GET /api/products/{id} (Customer\ProductController@show):
 * one company-wide Availability answer plus a flat list of the sizes a product
 * is bottled in. Neither the source branch, its stock count nor the packaging
 * behind a size is shown; every order is directed to Mikocheni HQ.
 */
const SEX_LABELS: Record<string, string> = {
  male: "Men's",
  female: "Women's",
  unisex: 'Unisex',
  accessories: 'Accessories',
};

type Status = 'loading' | 'error' | 'ready';

export default function ProductScreen() {
  const params = useLocalSearchParams<{ id?: string }>();
  const id = params.id ?? '';
  const { items, count: cartCount, putItem } = useCart();

  const [status, setStatus] = useState<Status>('loading');
  const [data, setData] = useState<ProductDetailPayload | null>(null);
  const [error, setError] = useState('');

  // Ordering state: which size, and how many. There is no branch choice.
  const [size, setSize] = useState<number | null>(null);
  const [quantity, setQuantity] = useState(1);

  const load = useCallback(async () => {
    setStatus('loading');
    try {
      const payload = await fetchProduct(id);
      setData(payload);
      setStatus('ready');
    } catch (e) {
      setError(errorMessage(e));
      setStatus('error');
    }
  }, [id]);

  useEffect(() => {
    if (id) load();
  }, [load, id]);

  // The detail endpoint answers a flat company-wide size list.
  const sizes = useMemo(() => data?.varieties ?? [], [data]);

  // Keep the size pick valid (defaults to the smallest size in stock).
  useEffect(() => {
    if (sizes.length === 0) {
      if (size !== null) setSize(null);
      return;
    }
    if (size === null || !sizes.some((option) => option.volume === size)) {
      setSize(sizes[0].volume);
    }
  }, [sizes, size]);

  const picked = useMemo(
    () => sizes.find((option) => option.volume === size) ?? null,
    [sizes, size],
  );

  const unitPrice = picked && picked.price > 0 ? picked.price : Number(data?.price ?? 0);
  // Stock is still used to clamp the stepper, never displayed as a quantity.
  const available = picked?.available ?? (data?.is_available ? 99 : 0);
  const orderable = Boolean(data?.is_available)
    && (sizes.length === 0 || (picked?.available ?? 0) > 0);
  const productImage = (data?.product.images ?? []).map((img) => img?.image_url).find(Boolean) ?? null;

  // HQ + product + chosen size is the whole identity of this cart line.
  const cartKey = `${CUSTOMER_ORDER_BRANCH_ID}-${id}-${picked ? picked.volume : 'plain'}`;
  const inCart = useMemo(
    () => items.find((item) => item.key === cartKey) ?? null,
    [items, cartKey],
  );

  // Re-opening a perfume already in the cart shows the quantity it was saved
  // with rather than starting again at 1. Only when the chosen line changes —
  // after that the stepper belongs to the customer.
  const syncedKey = useRef<string | null>(null);
  useEffect(() => {
    if (syncedKey.current === cartKey) return;
    syncedKey.current = cartKey;
    setQuantity(inCart ? inCart.quantity : 1);
  }, [cartKey, inCart]);

  // A size can sell out while the page is open — never leave the stepper above it.
  useEffect(() => {
    if (available > 0 && quantity > available) setQuantity(available);
  }, [available, quantity]);

  /**
   * Park this card's line, then go where the customer asked: `cart` checks out
   * what is in the basket, `shop` keeps browsing for more perfume.
   */
  const parkItem = (after: 'cart' | 'shop') => {
    const product = data?.product;
    if (!orderable || !product) return;

    putItem({
      key: cartKey,
      productId: id,
      productName: product.name ?? 'Unnamed product',
      brand: product.brand ?? null,
      image: productImage,
      branchId: CUSTOMER_ORDER_BRANCH_ID,
      branchName: 'Mikocheni HQ',
      quantity,
      unitPrice,
      available,
      volume: picked?.volume,
      varietyLabel: picked?.label,
    });

    router.push(after === 'cart' ? '/cart' : '/shop');
  };

  const body = () => {
    if (status === 'loading') return <LoadingView label="Loading product…" />;
    if (status === 'error' || !data) return <ErrorView message={error} onRetry={() => load()} />;

    const product = data.product;
    const images = (product.images ?? []).map((img) => img?.image_url).filter(Boolean);
    const image = images[0];
    const sexLabel = product.sex_category ? SEX_LABELS[product.sex_category] : null;

    return (
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <View style={styles.heroImageWrap}>
          {image ? (
            <Image source={{ uri: image }} style={styles.heroImage} contentFit="cover" transition={250} />
          ) : (
            <View style={[styles.heroImage, styles.heroFallback]}>
              <Ionicons name="sparkles-outline" size={40} color={COLORS.textMuted} />
            </View>
          )}
        </View>

        <Text style={styles.name}>{product.name ?? 'Unnamed product'}</Text>
        {product.brand ? <Text style={styles.brand}>{product.brand}</Text> : null}

        <View style={styles.tags}>
          {sexLabel ? (
            <View style={styles.tag}>
              <Text style={styles.tagText}>{sexLabel}</Text>
            </View>
          ) : null}
          {product.category ? (
            <View style={styles.tag}>
              <Text style={styles.tagText}>{product.category}</Text>
            </View>
          ) : null}
          {product.fundamental_ingredient ? (
            <View style={styles.tag}>
              <Text style={styles.tagText}>{product.fundamental_ingredient}</Text>
            </View>
          ) : null}
        </View>

        <View style={styles.priceCard}>
          <View>
            <Text style={styles.priceLabel}>Availability</Text>
            <Text style={styles.price}>{orderable ? 'Available' : 'Currently out of stock'}</Text>
          </View>
          <Ionicons
            name={orderable ? 'checkmark-circle-outline' : 'alert-circle-outline'}
            size={22}
            color={COLORS.gold}
          />
        </View>

        {/* Order controls — the same choices the website's order form asks for. */}
        <Card style={styles.block}>
          <Text style={styles.blockTitle}>Order this item</Text>

          {orderable ? (
            <>
              {sizes.length > 0 ? (
                <>
                  <Text style={styles.fieldLabel}>Size</Text>
                  <View style={styles.chipWrap}>
                    {sizes.map((option) => {
                      const active = option.volume === size;
                      return (
                        <Pressable
                          key={option.volume}
                          onPress={() => setSize(option.volume)}
                          style={[styles.chip, styles.varietyChip, active && styles.chipActive]}
                          accessibilityRole="button"
                        >
                          <Text style={[styles.chipText, active && styles.chipTextActive]}>
                            {option.label}
                          </Text>
                          <Text style={[styles.chipSub, active && styles.chipTextActive]}>
                            {option.price > 0 ? formatMoney(option.price) : 'See total'}
                          </Text>
                        </Pressable>
                      );
                    })}
                  </View>
                </>
              ) : null}

              <View style={styles.orderFoot}>
                <View>
                  <Text style={styles.fieldLabel}>Quantity</Text>
                  <View style={styles.stepper}>
                    <Pressable
                      onPress={() => setQuantity((q) => Math.max(1, q - 1))}
                      disabled={quantity <= 1}
                      style={({ pressed }) => [
                        styles.stepButton,
                        quantity <= 1 && styles.stepDisabled,
                        pressed && styles.pressed,
                      ]}
                      accessibilityRole="button"
                      accessibilityLabel="Decrease quantity"
                    >
                      <Ionicons name="remove" size={18} color={COLORS.gold} />
                    </Pressable>
                    <Text style={styles.stepValue}>{quantity}</Text>
                    <Pressable
                      onPress={() => setQuantity((q) => Math.min(available, q + 1))}
                      disabled={quantity >= available}
                      style={({ pressed }) => [
                        styles.stepButton,
                        quantity >= available && styles.stepDisabled,
                        pressed && styles.pressed,
                      ]}
                      accessibilityRole="button"
                      accessibilityLabel="Increase quantity"
                    >
                      <Ionicons name="add" size={18} color={COLORS.gold} />
                    </Pressable>
                  </View>
                </View>
                <View style={styles.totalWrap}>
                  <Text style={styles.fieldLabel}>Total</Text>
                  <Text style={styles.total}>{formatMoney(unitPrice * quantity)}</Text>
                </View>
              </View>
            </>
          ) : (
            <Text style={styles.paragraph}>
              This item is currently out of stock. Please check back soon.
            </Text>
          )}
        </Card>

        {product.description ? (
          <Card style={styles.block}>
            <Text style={styles.blockTitle}>Description</Text>
            <Text style={styles.paragraph}>{product.description}</Text>
          </Card>
        ) : null}
      </ScrollView>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="Product" subtitle="Shopping" right={<CartButton />} />
      {body()}
      {data?.is_available && orderable ? (
        <View style={styles.footerBar}>
          <GoldButton
            label={unitPrice > 0 ? `Place Order · ${formatMoney(unitPrice * quantity)}` : 'Place Order'}
            icon="checkmark-circle-outline"
            onPress={() => parkItem('cart')}
            style={styles.footerButton}
          />
          <OutlineButton
            label={cartCount > 0 ? `Choose More Products · ${cartCount} in cart` : 'Choose More Products'}
            icon="bag-add-outline"
            onPress={() => parkItem('shop')}
            style={styles.footerButton}
          />
        </View>
      ) : null}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  content: {
    paddingHorizontal: 18,
    paddingBottom: 24,
    gap: 12,
  },
  heroImageWrap: {
    width: '100%',
    height: 280,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  heroImage: {
    width: '100%',
    height: '100%',
  },
  heroFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  name: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '800',
    marginTop: 4,
  },
  brand: {
    color: COLORS.gold,
    fontSize: 14.5,
    fontWeight: '700',
    letterSpacing: 0.4,
  },
  tags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  tag: {
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 12,
    paddingVertical: 5,
  },
  tagText: {
    color: COLORS.goldBright,
    fontSize: 11.5,
    fontWeight: '600',
  },
  priceCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: COLORS.bgRaised,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.lg,
    padding: 16,
  },
  priceLabel: {
    color: COLORS.textMuted,
    fontSize: 11.5,
    letterSpacing: 0.6,
    textTransform: 'uppercase',
    marginBottom: 3,
  },
  price: {
    color: COLORS.gold,
    fontSize: 21,
    fontWeight: '800',
  },
  block: {
    gap: 8,
  },
  blockTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
    marginBottom: 2,
  },
  paragraph: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 21,
  },
  fieldLabel: {
    color: COLORS.textMuted,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 1,
    textTransform: 'uppercase',
    marginTop: 6,
  },
  chipWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 4,
  },
  chip: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: RADIUS.pill,
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    maxWidth: '100%',
  },
  varietyChip: {
    alignItems: 'flex-start',
    borderRadius: RADIUS.md,
  },
  chipActive: {
    backgroundColor: COLORS.goldSoft,
    borderColor: COLORS.goldBorder,
  },
  chipText: {
    color: COLORS.textSecondary,
    fontSize: 12.5,
    fontWeight: '600',
  },
  chipSub: {
    color: COLORS.textMuted,
    fontSize: 11,
    marginTop: 2,
  },
  chipTextActive: {
    color: COLORS.goldBright,
  },
  orderFoot: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginTop: 10,
    gap: 12,
  },
  stepper: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 4,
  },
  stepButton: {
    width: 38,
    height: 38,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  stepDisabled: { opacity: 0.4 },
  stepValue: {
    minWidth: 34,
    textAlign: 'center',
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },
  totalWrap: { alignItems: 'flex-end' },
  total: {
    color: COLORS.gold,
    fontSize: 18,
    fontWeight: '800',
    marginTop: 4,
  },
  pressed: { opacity: 0.8 },
  footerBar: {
    paddingHorizontal: 18,
    paddingTop: 10,
    paddingBottom: 14,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    backgroundColor: COLORS.bg,
    gap: 8,
  },
  footerButton: {
    width: '100%',
  },
});
