import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CartButton } from '../components/CartButton';
import { ScreenHeader } from '../components/ScreenHeader';
import { Card, ErrorView, GoldButton, LoadingView, OutlineButton } from '../components/ui';
import { errorMessage, fetchProduct, type ProductDetailPayload } from '../lib/api';
import { useCart } from '../lib/cart';
import { formatMoney } from '../lib/format';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * Product details — GET /api/products/{id} (Customer\ProductController@show):
 * description plus the real per-branch stock, prices and sizes the website
 * shows, so a customer knows where to find the fragrance — and, at the bottom,
 * the same "add to cart" choice the website's order form offers (branch, size,
 * quantity) feeding the in-app cart.
 *
 * A perfume stocked at more than one branch is ordered from exactly one of
 * them, so the branch options carry that branch's price and stock and adding to
 * the cart from another branch starts a fresh cart (lib/cart).
 *
 * Only sizes are ever offered: box/logo/colour is the branch's to pack and
 * never changes the price.
 */
const SEX_LABELS: Record<string, string> = {
  male: "Men's",
  female: "Women's",
  unisex: 'Unisex',
  accessories: 'Accessories',
};

type Status = 'loading' | 'error' | 'ready';

export default function ProductScreen() {
  const params = useLocalSearchParams<{ id?: string; branch_id?: string }>();
  const id = params.id ?? '';
  const { addItem } = useCart();

  const [status, setStatus] = useState<Status>('loading');
  const [data, setData] = useState<ProductDetailPayload | null>(null);
  const [error, setError] = useState('');

  // Ordering state: which branch, which size, how many.
  const [branchId, setBranchId] = useState<string>(params.branch_id ?? '');
  const [size, setSize] = useState<number | null>(null);
  const [quantity, setQuantity] = useState(1);

  const load = useCallback(async () => {
    setStatus('loading');
    try {
      const payload = await fetchProduct(id, params.branch_id);
      setData(payload);
      setStatus('ready');
    } catch (e) {
      setError(errorMessage(e));
      setStatus('error');
    }
  }, [id, params.branch_id]);

  useEffect(() => {
    if (id) load();
  }, [load, id]);

  // Branches that actually have this product on the shelf.
  const stockedBranches = useMemo(
    () => (data?.branch_stocks ?? []).filter((stock) => (stock.quantity ?? 0) > 0),
    [data],
  );

  // Default to the branch the catalogue came from, else the first stocked one.
  useEffect(() => {
    if (stockedBranches.length === 0) return;
    const ids = stockedBranches.map((stock) => String(stock.branch_id));
    if (!ids.includes(branchId)) setBranchId(ids[0]);
  }, [stockedBranches, branchId]);

  const selectedStock =
    stockedBranches.find((stock) => String(stock.branch_id) === branchId) ?? null;

  // Sizes in stock at the chosen branch (empty for plain products).
  // GET /api/products/{id} answers `varieties[branchId]` with THIS product's
  // own size list — the endpoint loads one product at a time — so the branch
  // level is already the array. (It used to be read as
  // varieties[branchId][productId], which always resolved to undefined: the
  // picker never rendered and checkout then rejected the order.)
  const sizes = useMemo(() => data?.varieties?.[branchId] ?? [], [data, branchId]);

  // Keep the size pick valid (defaults to the smallest one in stock).
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

  const branchPrice = Number(selectedStock?.selling_price ?? 0);
  const unitPrice = picked && picked.price > 0 ? picked.price : branchPrice;
  const available = picked ? picked.available : (selectedStock?.quantity ?? 0);
  const orderable = selectedStock != null && available > 0;
  const productImage = (data?.product.images ?? []).map((img) => img?.image_url).find(Boolean) ?? null;

  // Switching branch or bottling can lower what is available — never leave the
  // stepper above it.
  useEffect(() => {
    if (available > 0 && quantity > available) setQuantity(available);
  }, [available, quantity]);

  const onAddToCart = () => {
    const product = data?.product;
    if (!orderable || !product || !selectedStock) return;

    const item = {
      key: `${branchId}-${id}-${picked ? picked.volume : 'plain'}`,
      productId: id,
      productName: product.name ?? 'Unnamed product',
      brand: product.brand ?? null,
      image: productImage,
      branchId: selectedStock.branch_id ?? branchId,
      branchName: selectedStock.branch?.name ?? 'Branch',
      quantity,
      unitPrice,
      volume: picked?.volume,
      varietyLabel: picked?.label,
      available,
    };

    const result = addItem(item);
    if (result === 'branch-conflict') {
      Alert.alert(
        'Start a new cart?',
        'Your cart already has items from another branch. An order can only be for one branch.',
        [
          { text: 'Keep my cart', style: 'cancel' },
          {
            text: 'Start new',
            style: 'destructive',
            onPress: () => {
              addItem(item, { replace: true });
              router.push('/cart');
            },
          },
        ],
      );
      return;
    }

    Alert.alert('Added to cart', `${item.productName} is in your cart.`, [
      { text: 'Keep shopping', style: 'cancel' },
      { text: 'View cart', onPress: () => router.push('/cart') },
    ]);
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

        {orderable ? (
          <View style={styles.priceCard}>
            <View>
              <Text style={styles.priceLabel}>
                {selectedStock?.branch?.name ? `Price at ${selectedStock.branch.name}` : 'Price'}
              </Text>
              <Text style={styles.price}>
                {unitPrice > 0 ? formatMoney(unitPrice) : 'See options below'}
              </Text>
            </View>
            <Ionicons name="pricetag-outline" size={22} color={COLORS.gold} />
          </View>
        ) : (
          <View style={styles.priceCard}>
            <View>
              <Text style={styles.priceLabel}>Availability</Text>
              <Text style={styles.price}>
                {data.in_stock_branch_count > 0
                  ? `In ${data.in_stock_branch_count} branch${data.in_stock_branch_count === 1 ? '' : 'es'}`
                  : 'Currently out of stock'}
              </Text>
            </View>
            <Ionicons name="storefront-outline" size={22} color={COLORS.gold} />
          </View>
        )}

        {/* Order controls — the same choices the website's order form asks for. */}
        <Card style={styles.block}>
          <Text style={styles.blockTitle}>Order this item</Text>

          {stockedBranches.length === 0 ? (
            <Text style={styles.paragraph}>
              No branch currently has this in stock. Please check back soon or find a branch below.
            </Text>
          ) : (
            <>
              {stockedBranches.length > 1 ? (
                <>
                  <Text style={styles.fieldLabel}>
                    Branch · {stockedBranches.length} stock this
                  </Text>
                  <View style={styles.chipWrap}>
                    {stockedBranches.map((stock, index) => {
                      const value = String(stock.branch_id);
                      const active = value === branchId;
                      const sizesHere = data.varieties?.[value] ?? [];
                      // Cheapest size at this branch, so each option carries
                      // its own price as well as its own stock.
                      const fromPrice = sizesHere
                        .map((option) =>
                          option.price > 0 ? option.price : Number(stock.selling_price ?? 0),
                        )
                        .filter((price) => price > 0)
                        .sort((a, b) => a - b)[0];
                      return (
                        <Pressable
                          key={String(stock.id ?? `${value}-${index}`)}
                          onPress={() => setBranchId(value)}
                          style={[styles.chip, styles.branchChip, active && styles.chipActive]}
                          accessibilityRole="button"
                        >
                          <Text style={[styles.chipText, active && styles.chipTextActive]} numberOfLines={1}>
                            {stock.branch?.name ?? 'Branch'}
                          </Text>
                          <Text style={[styles.chipSub, active && styles.chipTextActive]}>
                            {fromPrice ? `from ${formatMoney(fromPrice)} · ` : ''}
                            {stock.quantity ?? 0} in stock
                          </Text>
                        </Pressable>
                      );
                    })}
                  </View>
                </>
              ) : (
                <Text style={styles.branchHint}>
                  At {selectedStock?.branch?.name ?? 'this branch'} ·{' '}
                  {selectedStock?.quantity ?? 0} in stock
                </Text>
              )}

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
                            {option.price > 0 ? formatMoney(option.price) : 'Price at branch'} ·{' '}
                            {option.available} left
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
          )}
        </Card>

        {product.description ? (
          <Card style={styles.block}>
            <Text style={styles.blockTitle}>Description</Text>
            <Text style={styles.paragraph}>{product.description}</Text>
          </Card>
        ) : null}

        {/* Per-branch stock, prices and sizes — the website's data. */}
        <Card style={styles.block}>
          <Text style={styles.blockTitle}>Where to find it</Text>
          {stockedBranches.length === 0 ? (
            <Text style={styles.paragraph}>No branch currently has this in stock.</Text>
          ) : (
            stockedBranches.map((stock, index) => {
              const sizesHere = data.varieties?.[String(stock.branch_id)] ?? [];
              return (
                <View
                  key={String(stock.id ?? `${String(stock.branch_id)}-${index}`)}
                  style={styles.branchBlock}
                >
                  <View style={styles.branchRow}>
                    <View style={styles.branchInfo}>
                      <Text style={styles.branchName}>{stock.branch?.name ?? 'Branch'}</Text>
                      {stock.selling_price != null && stock.selling_price !== '' ? (
                        <Text style={styles.branchPrice}>{formatMoney(stock.selling_price)}</Text>
                      ) : null}
                    </View>
                    <View style={styles.stockPill}>
                      <Text style={styles.stockPillText}>{stock.quantity} in stock</Text>
                    </View>
                  </View>

                  {sizesHere.map((option) => (
                    <View key={option.volume} style={styles.varietyRow}>
                      <Text style={styles.varietyLabel}>{option.label}</Text>
                      <Text style={styles.varietyPrice}>
                        {option.price > 0 ? formatMoney(option.price) : '—'}
                        {option.available > 0 ? ` · ${option.available} left` : ''}
                      </Text>
                    </View>
                  ))}
                </View>
              );
            })
          )}
        </Card>
      </ScrollView>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="Product" subtitle="Shopping" right={<CartButton />} />
      {body()}
      <View style={styles.footerBar}>
        {orderable ? (
          <GoldButton
            label={quantity > 1 ? `Add ${quantity} to Cart` : 'Add to Cart'}
            icon="cart-outline"
            onPress={onAddToCart}
            style={styles.footerButton}
          />
        ) : (
          <OutlineButton
            label="Find a Branch"
            icon="navigate-outline"
            onPress={() => router.push('/branches')}
            style={styles.footerButton}
          />
        )}
      </View>
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
  branchHint: {
    color: COLORS.textSecondary,
    fontSize: 13,
    marginTop: 4,
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
  branchChip: {
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
  branchBlock: {
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 10,
    marginTop: 6,
    gap: 6,
  },
  branchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  branchInfo: {
    flex: 1,
    gap: 2,
  },
  branchName: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  branchPrice: {
    color: COLORS.gold,
    fontSize: 13.5,
    fontWeight: '700',
  },
  stockPill: {
    backgroundColor: COLORS.goldSoft,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  stockPillText: {
    color: COLORS.goldBright,
    fontSize: 11,
    fontWeight: '600',
  },
  varietyRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 10,
    paddingVertical: 3,
  },
  varietyLabel: {
    color: COLORS.textSecondary,
    fontSize: 13,
    flexShrink: 1,
  },
  varietyPrice: {
    color: COLORS.text,
    fontSize: 12.5,
    fontWeight: '600',
  },
  footerBar: {
    paddingHorizontal: 18,
    paddingTop: 10,
    paddingBottom: 14,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    backgroundColor: COLORS.bg,
  },
  footerButton: {
    width: '100%',
  },
});
