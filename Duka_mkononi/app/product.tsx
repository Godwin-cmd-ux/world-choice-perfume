import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '../components/ScreenHeader';
import { Card, ErrorView, LoadingView, OutlineButton } from '../components/ui';
import { errorMessage, fetchProduct, type ProductDetailPayload } from '../lib/api';
import { formatMoney } from '../lib/format';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * Product details — GET /api/products/{id} (Customer\ProductController@show):
 * description plus the real per-branch stock, prices and varieties the
 * website shows, so a customer knows where to find the fragrance.
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

  const [status, setStatus] = useState<Status>('loading');
  const [data, setData] = useState<ProductDetailPayload | null>(null);
  const [error, setError] = useState('');

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

  const body = () => {
    if (status === 'loading') return <LoadingView label="Loading product…" />;
    if (status === 'error' || !data) return <ErrorView message={error} onRetry={() => load()} />;

    const product = data.product;
    const images = (product.images ?? []).map((img) => img?.image_url).filter(Boolean);
    const image = images[0];
    const sexLabel = product.sex_category ? SEX_LABELS[product.sex_category] : null;

    const stockedBranches = data.branch_stocks.filter((stock) => (stock.quantity ?? 0) > 0);
    const hasPrice = data.price != null && data.price !== '';

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

        {hasPrice ? (
          <View style={styles.priceCard}>
            <View>
              <Text style={styles.priceLabel}>
                {data.selected_branch ? `Price at ${data.selected_branch.name}` : 'Price'}
              </Text>
              <Text style={styles.price}>{formatMoney(data.price)}</Text>
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

        {product.description ? (
          <Card style={styles.block}>
            <Text style={styles.blockTitle}>Description</Text>
            <Text style={styles.paragraph}>{product.description}</Text>
          </Card>
        ) : null}

        {/* Per-branch stock, prices and varieties — the website's data. */}
        <Card style={styles.block}>
          <Text style={styles.blockTitle}>Where to find it</Text>
          {stockedBranches.length === 0 ? (
            <Text style={styles.paragraph}>No branch currently has this in stock.</Text>
          ) : (
            stockedBranches.map((stock, index) => {
              const varieties =
                data.varieties?.[String(stock.branch_id)]?.[String(id)] ?? [];
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

                  {varieties.map((volume) => (
                    <View key={volume.volume} style={styles.varietyGroup}>
                      <Text style={styles.varietyVolume}>{volume.label}</Text>
                      {volume.variants.map((variant) => (
                        <View key={variant.key} style={styles.varietyRow}>
                          <Text style={styles.varietyLabel}>{variant.label}</Text>
                          <Text style={styles.varietyPrice}>
                            {variant.price > 0 ? formatMoney(variant.price) : '—'}
                            {variant.available > 0 ? ` · ${variant.available} left` : ''}
                          </Text>
                        </View>
                      ))}
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
      <ScreenHeader title="Product" subtitle="Shopping" />
      {body()}
      <View style={styles.footerBar}>
        <OutlineButton
          label="Find a Branch"
          icon="navigate-outline"
          onPress={() => router.push('/branches')}
          style={styles.footerButton}
        />
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
  varietyGroup: {
    marginTop: 4,
    gap: 4,
  },
  varietyVolume: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
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
