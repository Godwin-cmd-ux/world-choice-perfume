import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchBaDashboard } from '../../../lib/baApi';
import { formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { BA_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * Dashboard — the website's branch-admin.dashboard: today's paid sales and
 * transactions, the pending queue, low stock and stock value, the daily
 * sales/expenses/actual summary and the month's financials — all scoped to
 * this admin's own branch, exactly like the Blade page.
 */
export default function BaDashboard() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchBaDashboard(), []);

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

  const financials = data?.financials ?? {};

  return (
    <AdminPage
      title="Branch Admin"
      eyebrow={scope?.branch_name ?? 'Branch Admin'}
      accent={BA_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {!error && !loading ? (
        <Banner kind="success" message={`${scope?.branch_name ?? 'Your branch'} — supervising this branch's sales, orders, team and expenses.`} />
      ) : null}

      {loading && !data ? (
        <LoadingView label="Loading dashboard…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : data ? (
        <>
          <StatGrid>
            <StatTile label="Today's Sales" value={formatMoney(data.todaySales)} tone="gold" />
            <StatTile label="Transactions" value={data.todayTransactions} tone="info" />
            <StatTile
              label="Pending Orders"
              value={data.pendingOrders}
              tone={data.pendingOrders > 0 ? 'warning' : 'success'}
            />
            <StatTile label="Low Stock" value={data.lowStock} tone={data.lowStock > 0 ? 'danger' : 'success'} hint="≤ 5 units" />
            <StatTile label="Stock Value" value={formatMoney(data.totalStockValue)} tone="info" />
            <StatTile label="Month Revenue" value={formatMoney(financials.revenue ?? 0)} tone="success" />
          </StatGrid>

          <GroupLabel right={<Text style={styles.muted}>{formatMoney(financials.expenses ?? 0)} spent</Text>}>Quick actions</GroupLabel>
          <View style={styles.quickRow}>
            <QuickAction icon="cart-outline" label="New Sale" onPress={() => router.push('/ba/sale-new')} />
            <QuickAction icon="clipboard-outline" label="Order Queue" onPress={() => router.push('/ba/(tabs)/orders')} />
            <QuickAction icon="people-outline" label="Staffs" onPress={() => router.push('/ba/staff')} />
            <QuickAction icon="cash-outline" label="Expenses" onPress={() => router.push('/ba/expenses')} />
          </View>

          {dailyLines.length > 0 ? (
            <>
              <GroupLabel>Branch today</GroupLabel>
              <DataCard title="Daily summary" lines={dailyLines} />
            </>
          ) : null}

          <GroupLabel>Month to date</GroupLabel>
          <DataCard
            title={`${financials.transaction_count ?? 0} transactions`}
            subtitle="Revenue, expenses and net for this month"
            lines={[
              `Revenue: ${formatMoney(financials.revenue ?? 0)}`,
              `Expenses: ${formatMoney(financials.expenses ?? 0)}`,
            ]}
            onPress={() => router.push('/ba/(tabs)/sales')}
          />

          <GroupLabel right={<Text style={styles.muted}>{data.pendingOrders} pending</Text>}>Order queue</GroupLabel>
          <DataCard
            title={`${data.pendingOrders} waiting to be claimed`}
            subtitle="Supervisory view — who picked what, how long each order waits"
            onPress={() => router.push('/ba/(tabs)/orders')}
          />

          {data.lowStock > 0 ? (
            <>
              <GroupLabel>Stock watch</GroupLabel>
              <DataCard
                title={`${data.lowStock} products at or below 5 units`}
                subtitle="Reorder before the shelf runs dry"
                badge="Low"
                badgeTone="danger"
              />
            </>
          ) : (
            <EmptyView icon="cube-outline" title="Stock looks healthy" hint="No product at the branch is at or below 5 units." />
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
        <Ionicons name={icon} size={20} color={BA_ACCENT.light} />
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
    borderColor: BA_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
    paddingHorizontal: 12,
  },
  quickIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: BA_ACCENT.soft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickLabel: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 13, flexShrink: 1 },
});
