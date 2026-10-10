import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner } from '../../../components/authkit';
import { DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchSmDashboard, type SmDashboardPayload } from '../../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../../lib/theme';
import { SmMenuButton } from '../../../components/smsidebar';

/**
 * Stock Manager dashboard — the mobile twin of stock-manager/dashboard.blade.php:
 * the same product stats, "my sales today", orders summary and low-stock line,
 * plus the bottle / oil sections — which are ONLY rendered when the manager's
 * branch is autonomous (scope.is_products_only === false). The server zeroes
 * those figures for a products_based branch, so both ends agree on what a
 * products-only stock manager sees.
 */
export default function SmDashboard() {
  // Screens draw edge-to-edge, so the page keeps its own header clear of the status bar.
  const insets = useSafeAreaInsets();
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<SmDashboardPayload>(
    () => fetchSmDashboard(),
    [],
  );

  if (loading) return <LoadingView label="Loading dashboard…" />;
  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }
  if (error && !data) return <ErrorView message={error} onRetry={reload} />;
  if (!data) return <EmptyView icon="grid-outline" title="No dashboard data" hint="Pull to refresh." />;

  const scope = data.scope;
  const productsOnly = scope.is_products_only;

  return (
    <View style={[styles.root, { paddingTop: insets.top }]}>
      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={SM_ACCENT.main} />}
      >
        <View>
          <SmMenuButton />
          <Text style={[styles.eyebrow, { color: SM_ACCENT.main }]}>World Choice Perfumes</Text>
          <Text style={styles.h1}>Stock Manager</Text>
          <Text style={styles.branch}>
            {scope.branch_name ?? 'Your branch'}
            {scope.in_cross_branch ? ' · monitoring' : ''}
          </Text>
        </View>

        {error ? <Banner kind="error" message={error} /> : null}

        {/* Branch category — the one flag that decides what is visible. */}
        <View style={[styles.categoryChip, productsOnly && styles.categoryChipProducts]}>
          <Ionicons
            name={productsOnly ? 'cube-outline' : 'git-branch-outline'}
            size={14}
            color={productsOnly ? COLORS.info : SM_ACCENT.main}
          />
          <Text style={[styles.categoryText, { color: productsOnly ? COLORS.info : SM_ACCENT.main }]}>
            {scope.branch_category_label}
          </Text>
          <Text style={styles.categoryHint}>
            {productsOnly ? 'Product stock only' : 'Products, bottles, oils & accessories'}
          </Text>
        </View>

        <StatGrid>
          <StatTile label="Product Units" value={data.totalProductItems} hint={`${data.totalProductTypes} product types`} />
          <StatTile
            label="Low Stock"
            value={data.lowStockProducts}
            tone={data.lowStockProducts > 0 ? 'danger' : 'success'}
            hint={`≤ ${data.lowStockThreshold} units`}
          />
          <StatTile label="My Sales Today" value={data.mySalesTodayCount} tone="success" />
          <StatTile label="Sales Value (Today)" value={formatMoney(data.mySalesTodayTotal)} tone="gold" />
          <StatTile label="Pending Orders" value={data.pendingOrders} tone={data.pendingOrders > 0 ? 'warning' : 'success'} />
          <StatTile label="Open Orders" value={data.openOrders} />
          <StatTile label="Orders Today" value={data.ordersToday} tone="info" />
          {!productsOnly ? <StatTile label="Bottles" value={data.totalBottles} /> : null}
          {!productsOnly ? <StatTile label="Oil Fragrances" value={data.totalOilFragrances} /> : null}
        </StatGrid>

        {/* Quick links (the website's sidebar, phone-sized). */}
        <GroupLabel>Quick actions</GroupLabel>
        <View style={styles.quickRow}>
          <QuickLink icon="cube-outline" label="Product Stock" path="/sm/(tabs)/stock" />
          <QuickLink icon="swap-horizontal-outline" label="Transfers" path="/sm/(tabs)/transfers" />
          <QuickLink icon="receipt-outline" label="Sales" path="/sm/sales" />
          <QuickLink icon="list-outline" label="Orders" path="/sm/orders" />
          <QuickLink icon="alert-circle-outline" label="Low Stock" path="/sm/low-stock" />
          <QuickLink icon="time-outline" label="Movements" path="/sm/movements" />
          {!productsOnly ? <QuickLink icon="cube-outline" label="Bottles" path="/sm/bottle-stock" /> : null}
          {!productsOnly ? <QuickLink icon="flask-outline" label="Oil Fragrance" path="/sm/oil-fragrance" /> : null}
          {!productsOnly ? <QuickLink icon="construct-outline" label="Accessories" path="/sm/accessories" /> : null}
        </View>

        {/* Bottle & oil recent movements — products-only branches skip both. */}
        {!productsOnly ? (
          <>
            <GroupLabel>Recent bottle movements</GroupLabel>
            {data.recentBottleMovements.length === 0 ? (
              <Text style={styles.emptyLine}>No bottle movements yet.</Text>
            ) : (
              data.recentBottleMovements.map((m) => (
                <DataCard
                  key={String(m.id)}
                  title={`${m.volume ?? 'Bottle'} · ${m.type ?? ''}`}
                  lines={[m.reason ?? m.notes ?? null, m.performedBy?.name ? `By ${m.performedBy.name}` : null, formatDate(m.created_at)]}
                  badge={m.quantity != null ? String(m.quantity) : undefined}
                  badgeTone={(m.quantity ?? 0) >= 0 ? 'success' : 'danger'}
                />
              ))
            )}

            <GroupLabel>Recent oil movements</GroupLabel>
            {data.recentOilMovements.length === 0 ? (
              <Text style={styles.emptyLine}>No oil fragrance movements yet.</Text>
            ) : (
              data.recentOilMovements.map((m) => (
                <DataCard
                  key={String(m.id)}
                  title={`${m.name ?? 'Oil'}${m.volume ? ` ${m.volume}ml` : ''} · ${m.type ?? ''}`}
                  lines={[m.reason ?? null, m.performedBy?.name ? `By ${m.performedBy.name}` : null, formatDate(m.created_at)]}
                  badge={m.quantity != null ? String(m.quantity) : undefined}
                  badgeTone={(m.quantity ?? 0) >= 0 ? 'success' : 'danger'}
                />
              ))
            )}
          </>
        ) : (
          <View style={styles.productsOnlyNote}>
            <Ionicons name="information-circle-outline" size={16} color={COLORS.info} />
            <Text style={styles.productsOnlyText}>
              Bottles, oil fragrance and bottle accessories are hidden for products-only branches.
            </Text>
          </View>
        )}
      </ScrollView>
    </View>
  );
}

function QuickLink({ icon, label, path }: { icon: keyof typeof Ionicons.glyphMap; label: string; path: string }) {
  return (
    <Pressable
      onPress={() => router.push(path as never)}
      style={({ pressed }) => [styles.quickLink, pressed && styles.pressed]}
      accessibilityRole="button"
    >
      <Ionicons name={icon} size={18} color={SM_ACCENT.main} />
      <Text style={styles.quickLinkText}>{label}</Text>
    </Pressable>
  );
}

function formatMoney(value: number): string {
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
}

function formatDate(value?: string | null): string {
  if (!value) return '';
  const d = new Date(value);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleDateString('en-US', { timeZone: 'Africa/Dar_es_Salaam', month: 'short', day: 'numeric' });
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  scroll: { padding: 16, paddingBottom: 40, gap: 12 },
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  eyebrow: { fontSize: 11, fontWeight: '700', letterSpacing: 3, textTransform: 'uppercase' },
  h1: { color: COLORS.text, fontSize: 25, fontWeight: '800', marginTop: 4 },
  branch: { color: COLORS.textSecondary, fontSize: 13, marginTop: 3 },
  categoryChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  categoryChipProducts: { backgroundColor: 'rgba(96, 165, 250, 0.10)', borderColor: 'rgba(96, 165, 250, 0.30)' },
  categoryText: { fontSize: 12, fontWeight: '800', letterSpacing: 1, textTransform: 'uppercase' },
  categoryHint: { color: COLORS.textMuted, fontSize: 12, flex: 1, textAlign: 'right' },
  quickRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  quickLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  quickLinkText: { color: COLORS.text, fontSize: 13, fontWeight: '700' },
  pressed: { opacity: 0.7 },
  emptyLine: { color: COLORS.textMuted, fontSize: 13 },
  productsOnlyNote: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: 'rgba(96, 165, 250, 0.08)',
    borderWidth: 1,
    borderColor: 'rgba(96, 165, 250, 0.25)',
    borderRadius: RADIUS.md,
    padding: 12,
  },
  productsOnlyText: { color: COLORS.textSecondary, fontSize: 12.5, flex: 1, lineHeight: 17 },
});
