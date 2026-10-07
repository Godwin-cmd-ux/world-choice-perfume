import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchSellerDashboard } from '../../../lib/sellerApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { COLORS, RADIUS, SELLER_ACCENT } from '../../../lib/theme';

/**
 * Dashboard — the website's seller.dashboard: today's own sales and
 * transactions, the seller's overall totals, the branch's daily
 * sales/expenses/actual summary, the branch's pending-order count and the
 * products in stock — exactly the figures the Blade page computes for the
 * signed-in seller.
 */
export default function SellerDashboard() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchSellerDashboard(), []);

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const scope = data?.scope;
  const daily = (data?.dailySummary ?? {}) as Record<string, unknown>;
  const dailyLines = Object.entries(daily)
    .filter(([, v]) => typeof v === 'number')
    .slice(0, 4)
    .map(([k, v]) => `${k.replace(/_/g, ' ')}: ${formatMoney(v as number)}`);

  const recent = (data?.mySales ?? []).slice(0, 5);

  return (
    <AdminPage
      title="Seller"
      eyebrow={scope?.branch_name ?? 'Seller'}
      accent={SELLER_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {!error && !loading ? (
        <Banner kind="success" message={`${scope?.branch_name ?? 'Your branch'} — ringing up sales and claiming orders from the shared queue.`} />
      ) : null}

      {loading && !data ? (
        <LoadingView label="Loading dashboard…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : data ? (
        <>
          <StatGrid>
            <StatTile label="Today's Sales" value={formatMoney(data.todayTotal)} tone="gold" />
            <StatTile label="Today's Count" value={data.mySales.filter((s) => (s.created_at ?? '').slice(0, 10) === new Date().toISOString().slice(0, 10)).length} tone="info" />
            <StatTile label="My Sales" value={formatMoney(data.totalSales)} tone="success" hint={`${data.totalTransactions} transactions`} />
            <StatTile
              label="Pending Orders"
              value={data.pendingOrders}
              tone={data.pendingOrders > 0 ? 'warning' : 'success'}
            />
            <StatTile label="Products In Stock" value={data.products.length} tone="info" />
            <StatTile label="Branch Day Sales" value={formatMoney((data.dailySummary.daily_sales as number) ?? 0)} tone="gold" />
          </StatGrid>

          <GroupLabel>Quick actions</GroupLabel>
          <View style={styles.quickRow}>
            <QuickAction icon="cart-outline" label="New Sale" onPress={() => router.push('/seller/sale-new')} />
            <QuickAction icon="clipboard-outline" label="Order Queue" onPress={() => router.push('/seller/(tabs)/orders')} />
            <QuickAction icon="receipt-outline" label="My Sales" onPress={() => router.push('/seller/(tabs)/sales')} />
            <QuickAction icon="person-outline" label="Account" onPress={() => router.push('/seller/(tabs)/account')} />
          </View>

          {dailyLines.length > 0 ? (
            <>
              <GroupLabel>Branch today</GroupLabel>
              <DataCard title="Daily summary" lines={dailyLines} />
            </>
          ) : null}

          <GroupLabel right={<Text style={styles.muted}>{data.pendingOrders} pending</Text>}>Order queue</GroupLabel>
          <DataCard
            title={data.pendingOrders > 0 ? `${data.pendingOrders} waiting to be claimed` : 'No orders waiting'}
            subtitle="Pending is shared with your branch — claim it by marking it picked"
            onPress={() => router.push('/seller/(tabs)/orders')}
          />

          <GroupLabel right={<Text style={styles.muted}>{data.products.length} in stock</Text>}>On the shelf</GroupLabel>
          <DataCard
            title={`${data.products.length} products available at ${scope?.branch_name ?? 'your branch'}`}
            subtitle="Ring one up from the New Sale screen"
            onPress={() => router.push('/seller/sale-new')}
          />

          {recent.length > 0 ? (
            <>
              <GroupLabel>My latest sales</GroupLabel>
              {recent.map((sale) => (
                <DataCard
                  key={String(sale.id)}
                  title={formatMoney(sale.total ?? 0)}
                  badge={sale.sale_type ?? undefined}
                  badgeTone="success"
                  lines={[formatDateTime(sale.created_at), sale.payment_method ? `Paid by ${String(sale.payment_method).replace(/_/g, ' ')}` : null].filter(Boolean) as string[]}
                  onPress={() => router.push({ pathname: '/seller/sale-detail', params: { id: String(sale.id) } })}
                />
              ))}
            </>
          ) : (
            <EmptyView icon="cart-outline" title="No sales yet" hint="Your first sale appears here the moment you ring it up." />
          )}
        </>
      ) : null}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

function QuickAction({ icon, label, onPress }: { icon: keyof typeof Ionicons.glyphMap; label: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.quick, pressed && { opacity: 0.7 }]} accessibilityRole="button">
      <View style={styles.quickIcon}>
        <Ionicons name={icon} size={20} color={SELLER_ACCENT.light} />
      </View>
      <Text style={styles.quickLabel} numberOfLines={1}>
        {label}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  muted: { color: COLORS.textMuted, fontSize: 11 },
  quickRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 6 },
  quick: {
    width: '48%',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.bgRaised,
    borderColor: SELLER_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
    paddingHorizontal: 12,
  },
  quickIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: SELLER_ACCENT.soft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickLabel: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 13, flexShrink: 1 },
});
