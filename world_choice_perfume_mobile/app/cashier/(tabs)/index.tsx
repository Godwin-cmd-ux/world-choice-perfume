import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useSyncExternalStore, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { cashierMonitor } from '../../../lib/cashierMonitor';
import { fetchCashierDashboard } from '../../../lib/cashierApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CASHIER_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * Dashboard — the website's cashier.dashboard: today's money and
 * transactions, the pending queue, the orders this cashier is carrying,
 * recent sales and the branch's daily sales/expenses/actual summary.
 * Normal mode counts only this cashier's sales; while an HQ monitor watches
 * another branch the same figures come from the monitored branch, with the
 * website's read-only banner and an Exit action.
 */
export default function CashierDashboard() {
  const monitor = useSyncExternalStore(cashierMonitor.subscribe, cashierMonitor.get, cashierMonitor.get);

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchCashierDashboard({ monitor_branch: monitor?.id }),
    [monitor?.id],
  );

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const scope = data?.scope;
  const monitoring = Boolean(scope?.in_cross_branch);
  const daily = (data?.dailySummary ?? {}) as Record<string, unknown>;
  const dailyLines = Object.entries(daily)
    .filter(([, v]) => typeof v === 'number')
    .slice(0, 4)
    .map(([k, v]) => `${k.replace(/_/g, ' ')}: ${formatMoney(v as number)}`);

  const recent = (data?.recentSales ?? []).slice(0, 5);

  return (
    <AdminPage
      title="Cashier"
      eyebrow={scope?.branch_name ?? 'Cashier'}
      accent={CASHIER_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {monitoring ? (
        <Banner
          kind="connection"
          message={`Monitoring ${scope?.branch_name ?? 'another branch'} — read-only. Exit the branch to make changes.`}
          actionLabel="Exit branch"
          onAction={() => {
            cashierMonitor.clear();
            reload();
          }}
        />
      ) : !error && !loading ? (
        <Banner kind="success" message={`${scope?.branch_name ?? 'Your branch'} — ringing up sales, recording expenses and serving orders.`} />
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
            <StatTile
              label="My Orders"
              value={data.myAssignedOrders}
              tone={data.myAssignedOrders > 0 ? 'warning' : 'success'}
              hint="in progress"
            />
            <StatTile label="Branch Day Sales" value={formatMoney((data.dailySummary.daily_sales as number) ?? 0)} tone="success" />
            <StatTile label="Branch Day Expenses" value={formatMoney((data.dailySummary.daily_expenses as number) ?? 0)} tone="danger" />
          </StatGrid>

          {!monitoring ? (
            <>
              <GroupLabel>Quick actions</GroupLabel>
              <View style={styles.quickRow}>
                <QuickAction icon="cart-outline" label="New Sale" onPress={() => router.push('/cashier/sale-new')} />
                <QuickAction icon="clipboard-outline" label="Order Queue" onPress={() => router.push('/cashier/(tabs)/orders')} />
                <QuickAction icon="cash-outline" label="Record Expense" onPress={() => router.push('/cashier/expense-new')} />
                <QuickAction icon="ellipsis-horizontal" label="More" onPress={() => router.push('/cashier/(tabs)/more')} />
              </View>
            </>
          ) : null}

          {dailyLines.length > 0 ? (
            <>
              <GroupLabel right={<Text style={styles.muted}>{monitoring ? scope?.branch_name ?? '' : 'today'}</Text>}>Branch today</GroupLabel>
              <DataCard title="Daily summary" lines={dailyLines} />
            </>
          ) : null}

          <GroupLabel right={<Text style={styles.muted}>{data.pendingOrders} pending</Text>}>Order queue</GroupLabel>
          <DataCard
            title={data.pendingOrders > 0 ? `${data.pendingOrders} waiting to be claimed` : 'No orders waiting'}
            subtitle={monitoring ? 'Read-only while monitoring — pick and serve stay on your own branch' : 'Pending is shared with your branch — claim it by marking it picked'}
            onPress={() => router.push('/cashier/(tabs)/orders')}
          />

          {recent.length > 0 ? (
            <>
              <GroupLabel>{monitoring ? 'Latest branch sales' : 'My latest sales'}</GroupLabel>
              {recent.map((sale) => (
                <DataCard
                  key={String(sale.id)}
                  title={sale.sale_number ?? `Sale #${sale.id}`}
                  badge={formatMoney(sale.total ?? 0)}
                  badgeTone="success"
                  lines={[
                    formatDateTime(sale.created_at),
                    sale.cashier?.name ? `Cashier: ${sale.cashier.name}` : null,
                    sale.payment_method ? `Paid by ${String(sale.payment_method).replace(/_/g, ' ')}` : null,
                  ].filter(Boolean) as string[]}
                  onPress={() => router.push({ pathname: '/cashier/sale-detail', params: { id: String(sale.id) } })}
                />
              ))}
            </>
          ) : (
            <EmptyView icon="receipt-outline" title="No sales yet" hint="The first sale of the day appears here the moment it is rung up." />
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
        <Ionicons name={icon} size={20} color={CASHIER_ACCENT.light} />
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
    borderColor: CASHIER_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
    paddingHorizontal: 12,
  },
  quickIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: CASHIER_ACCENT.soft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickLabel: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 13, flexShrink: 1 },
});
