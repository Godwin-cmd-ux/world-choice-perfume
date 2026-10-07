import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CartButton } from '../components/CartButton';
import { ScreenHeader } from '../components/ScreenHeader';
import { EmptyView, ErrorView, LoadingView } from '../components/ui';
import { errorMessage, fetchProducts, type ProductsPayload, type StockItem } from '../lib/api';
import { formatMoney } from '../lib/format';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * SHOPPING — the mobile shop, wired to the same GET /api/products endpoint
 * the website shop uses (Customer\ProductController@index), so search,
 * category, brand and branch filters behave exactly like the website.
 */
const SEX_OPTIONS = [
  { value: 'all', label: 'All' },
  { value: 'male', label: "Men's" },
  { value: 'female', label: "Women's" },
  { value: 'unisex', label: 'Unisex' },
  { value: 'accessories', label: 'Accessories' },
];

type Status = 'loading' | 'error' | 'ready';

function ChipRow({
  options,
  value,
  onChange,
}: {
  options: { value: string; label: string }[];
  value: string;
  onChange: (next: string) => void;
}) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
      {options.map((option) => {
        const active = option.value === value;
        return (
          <Pressable
            key={option.value}
            onPress={() => onChange(option.value)}
            style={[styles.chip, active && styles.chipActive]}
            accessibilityRole="button"
          >
            <Text style={[styles.chipText, active && styles.chipTextActive]} numberOfLines={1}>
              {option.label}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

function ProductCard({
  item,
  multiBranch,
  onPress,
}: {
  item: StockItem;
  multiBranch: boolean;
  onPress: () => void;
}) {
  const image = (item.product.images ?? []).map((img) => img?.image_url).find(Boolean);
  const outOfStock = (item.quantity ?? 0) <= 0;
  const price = item.selling_price;

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [styles.card, pressed && styles.pressed]}
      accessibilityRole="button"
      accessibilityLabel={item.product.name ?? 'Product'}
    >
      <View style={styles.cardImageWrap}>
        {image ? (
          <Image source={{ uri: image }} style={styles.cardImage} contentFit="cover" transition={200} />
        ) : (
          <View style={[styles.cardImage, styles.cardImageFallback]}>
            <Ionicons name="sparkles-outline" size={26} color={COLORS.textMuted} />
          </View>
        )}
        {outOfStock ? (
          <View style={styles.stockBadge}>
            <Text style={styles.stockBadgeText}>Out of Stock</Text>
          </View>
        ) : null}
      </View>
      <View style={styles.cardBody}>
        <Text style={styles.cardName} numberOfLines={2}>
          {item.product.name ?? 'Unnamed product'}
        </Text>
        {item.product.brand ? (
          <Text style={styles.cardBrand} numberOfLines={1}>
            {item.product.brand}
          </Text>
        ) : null}
        {multiBranch ? (
          <Text style={styles.cardBranches}>Available at multiple branches</Text>
        ) : price != null && price !== '' ? (
          <Text style={styles.cardPrice}>{formatMoney(price)}</Text>
        ) : (
          <Text style={styles.cardHint}>View for details</Text>
        )}
      </View>
    </Pressable>
  );
}

export default function ShopScreen() {
  const params = useLocalSearchParams<{ sex_category?: string; brand?: string }>();

  const [status, setStatus] = useState<Status>('loading');
  const [data, setData] = useState<ProductsPayload | null>(null);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [branchId, setBranchId] = useState('all');
  const [sex, setSex] = useState(() => params.sex_category ?? 'all');
  const [brand, setBrand] = useState(() => params.brand ?? 'all');

  // Debounce typing so the shared endpoint is not hit per keystroke.
  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search.trim()), 400);
    return () => clearTimeout(timer);
  }, [search]);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' = 'initial') => {
      if (mode === 'refresh') setRefreshing(true);
      else setStatus('loading');
      try {
        const payload = await fetchProducts({
          search: debouncedSearch || undefined,
          sex_category: sex !== 'all' ? sex : undefined,
          branch_id: branchId !== 'all' ? branchId : undefined,
          brand: brand !== 'all' ? brand : undefined,
        });
        setData(payload);
        setStatus('ready');
      } catch (e) {
        setError(errorMessage(e));
        if (mode !== 'refresh') setStatus('error');
      } finally {
        setRefreshing(false);
      }
    },
    [debouncedSearch, sex, branchId, brand],
  );

  useEffect(() => {
    load();
  }, [load]);

  const branchOptions = [
    { value: 'all', label: 'All Branches' },
    ...(data?.branches ?? []).map((b) => ({ value: String(b.id), label: b.name })),
  ];
  const brandOptions = [
    { value: 'all', label: 'All Brands' },
    ...(data?.brands ?? []).map((b) => ({ value: b, label: b })),
  ];
  if (brand !== 'all' && !brandOptions.some((o) => o.value === brand)) {
    brandOptions.splice(1, 0, { value: brand, label: brand });
  }

  const body = () => {
    if (status === 'loading') return <LoadingView label="Loading products…" />;
    if (status === 'error' || !data) return <ErrorView message={error} onRetry={() => load()} />;

    const items = data.products.filter((item) => item.product_id != null);

    if (items.length === 0) {
      return (
        <EmptyView
          title={
            debouncedSearch
              ? `No products match “${debouncedSearch}”`
              : 'No products are currently available'
          }
          hint={
            debouncedSearch || sex !== 'all' || brand !== 'all' || branchId !== 'all'
              ? 'Try a different search or clear some filters.'
              : 'Please check back soon.'
          }
        />
      );
    }

    return (
      <ScrollView
        contentContainerStyle={styles.listContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={COLORS.gold}
            colors={[COLORS.gold]}
          />
        }
      >
        <Text style={styles.count}>
          <Text style={styles.countStrong}>{items.length}</Text>{' '}
          {items.length === 1 ? 'product' : 'products'}
          {data.selected_branch ? ` at ${data.selected_branch.name}` : ''}
        </Text>
        <View style={styles.grid}>
          {items.map((item, index) => {
            const count = data.branch_counts[String(item.product_id)] ?? 0;
            const multiBranch = branchId === 'all' && count > 1;
            return (
              <ProductCard
                key={`${String(item.product_id)}-${String(item.id ?? index)}`}
                item={item}
                multiBranch={multiBranch}
                onPress={() =>
                  router.push({
                    pathname: '/product',
                    params: {
                      id: String(item.product_id),
                      ...(branchId !== 'all' ? { branch_id: branchId } : {}),
                    },
                  })
                }
              />
            );
          })}
        </View>
      </ScrollView>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="Shopping" subtitle="World Choice Perfume" right={<CartButton />} />

      <View style={styles.filters}>
        <View style={styles.searchWrap}>
          <Ionicons name="search-outline" size={17} color={COLORS.textMuted} />
          <TextInput
            value={search}
            onChangeText={setSearch}
            placeholder="Search by name, brand, or category..."
            placeholderTextColor={COLORS.textMuted}
            style={styles.searchInput}
            autoCorrect={false}
            returnKeyType="search"
            accessibilityLabel="Search products"
          />
          {search ? (
            <Pressable onPress={() => setSearch('')} hitSlop={8} accessibility-label="Clear search">
              <Ionicons name="close-circle" size={17} color={COLORS.textMuted} />
            </Pressable>
          ) : null}
        </View>

        <ChipRow options={branchOptions} value={branchId} onChange={setBranchId} />
        <ChipRow options={SEX_OPTIONS} value={sex} onChange={setSex} />
        <ChipRow options={brandOptions} value={brand} onChange={setBrand} />
      </View>

      {body()}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  pressed: { opacity: 0.88 },

  filters: {
    paddingHorizontal: 16,
    paddingBottom: 10,
    gap: 8,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    height: 44,
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 14,
    paddingVertical: 0,
  },
  chipRow: {
    gap: 8,
    paddingRight: 16,
  },
  chip: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: RADIUS.pill,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    maxWidth: 190,
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
  chipTextActive: {
    color: COLORS.goldBright,
  },

  listContent: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 40,
  },
  count: {
    color: COLORS.textMuted,
    fontSize: 12.5,
    marginBottom: 10,
  },
  countStrong: {
    color: COLORS.textSecondary,
    fontWeight: '700',
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  card: {
    width: '47.5%',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
  },
  cardImageWrap: {
    width: '100%',
    height: 150,
    backgroundColor: COLORS.surfaceHigh,
  },
  cardImage: {
    width: '100%',
    height: '100%',
  },
  cardImageFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  stockBadge: {
    position: 'absolute',
    top: 8,
    left: 8,
    backgroundColor: 'rgba(17, 17, 17, 0.85)',
    borderWidth: 1,
    borderColor: COLORS.dangerBorder,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  stockBadgeText: {
    color: COLORS.danger,
    fontSize: 10,
    fontWeight: '700',
  },
  cardBody: {
    padding: 10,
    gap: 3,
  },
  cardName: {
    color: COLORS.text,
    fontSize: 13.5,
    fontWeight: '700',
    lineHeight: 18,
  },
  cardBrand: {
    color: COLORS.textMuted,
    fontSize: 11.5,
  },
  cardPrice: {
    color: COLORS.gold,
    fontSize: 14.5,
    fontWeight: '800',
    marginTop: 2,
  },
  cardBranches: {
    color: COLORS.gold,
    fontSize: 11.5,
    fontWeight: '600',
    marginTop: 2,
  },
  cardHint: {
    color: COLORS.textMuted,
    fontSize: 11.5,
    marginTop: 2,
  },
});
