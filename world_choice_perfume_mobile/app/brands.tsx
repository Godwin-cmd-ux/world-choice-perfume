import { Image } from 'expo-image';
import { router } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '../components/ScreenHeader';
import { EmptyView, ErrorView, LoadingView, SectionHeading } from '../components/ui';
import { errorMessage, fetchBrands, type Brand, type BrandsPayload } from '../lib/api';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * ALL BRANDS — the website's GET /brands page (customer/brands.blade.php) as
 * a dedicated mobile screen, so the home page's "View All Brands" button
 * opens a real brand listing instead of dropping the user on the shop.
 * Records come from GET /api/brands (the same active-brand query the page
 * runs), and tapping a brand opens the shop already filtered by it — the
 * same destination as the card on the website.
 */
type Status = 'loading' | 'error' | 'ready';

function BrandCard({ brand }: { brand: Brand }) {
  const open = () =>
    router.push({ pathname: '/shop', params: { brand: brand.name } });

  return (
    <Pressable
      onPress={open}
      style={({ pressed }) => [styles.card, pressed && styles.pressed]}
      accessibilityRole="button"
      accessibilityLabel={`View ${brand.name} fragrances`}
    >
      <View style={styles.logoWrap}>
        {brand.logo_url ? (
          <Image
            source={{ uri: brand.logo_url }}
            style={styles.logo}
            contentFit="cover"
            transition={200}
          />
        ) : (
          <View style={styles.initials}>
            <Text style={styles.initialsText}>
              {brand.name.slice(0, 2).toUpperCase()}
            </Text>
          </View>
        )}
      </View>
      <Text style={styles.name} numberOfLines={1}>
        {brand.name}
      </Text>
    </Pressable>
  );
}

export default function BrandsScreen() {
  const [status, setStatus] = useState<Status>('loading');
  const [data, setData] = useState<BrandsPayload | null>(null);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async (mode: 'initial' | 'refresh' = 'initial') => {
    if (mode === 'refresh') setRefreshing(true);
    else setStatus('loading');
    try {
      const payload = await fetchBrands();
      setData(payload);
      setStatus('ready');
    } catch (e) {
      setError(errorMessage(e));
      if (mode !== 'refresh') setStatus('error');
    } finally {
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const body = () => {
    if (status === 'loading') return <LoadingView label="Loading brands…" />;
    if (status === 'error' || !data) return <ErrorView message={error} onRetry={() => load()} />;

    if (data.brands.length === 0) {
      return (
        <EmptyView
          icon="diamond-outline"
          title="Brands are being added. Check back soon!"
          hint="New perfume houses appear here as soon as they land in store."
        />
      );
    }

    return (
      <ScrollView
        contentContainerStyle={styles.content}
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
        <SectionHeading eyebrow="World-Class Houses" title="All" accent="Brands" />
        <Text style={styles.lead}>
          Browse every perfume house we carry. Tap a brand to explore its fragrances.
        </Text>

        <View style={styles.grid}>
          {data.brands.map((brand) => (
            <BrandCard key={String(brand.id)} brand={brand} />
          ))}
        </View>
      </ScrollView>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="All Brands" subtitle="World Choice Perfume" />
      {body()}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  pressed: { opacity: 0.85 },
  content: {
    paddingHorizontal: 18,
    paddingTop: 6,
    paddingBottom: 40,
    gap: 4,
  },
  lead: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 20,
    marginBottom: 14,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    // Two cards per row with a gap that divides the container width, the way
    // the website's grid-cols-2 lays them out on a phone.
    gap: 12,
  },
  card: {
    width: '48%',
    flexGrow: 1,
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
    alignItems: 'center',
    paddingBottom: 16,
  },
  logoWrap: {
    width: '100%',
    height: 96,
    backgroundColor: COLORS.bgRaised,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  logo: {
    width: '100%',
    height: '100%',
  },
  initials: {
    width: 54,
    height: 54,
    borderRadius: 27,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  initialsText: {
    color: COLORS.goldBright,
    fontSize: 17,
    fontWeight: '800',
  },
  name: {
    color: COLORS.text,
    fontSize: 13.5,
    fontWeight: '600',
    paddingHorizontal: 8,
    textAlign: 'center',
  },
});
