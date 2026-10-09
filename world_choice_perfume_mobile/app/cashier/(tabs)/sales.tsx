import { router } from 'expo-router';
import { useSyncExternalStore, useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { cashierMonitor } from '../../../lib/cashierMonitor';
import { fetchCashierSales } from '../../../lib/cashierApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CASHIER_ACCENT } from '../../../lib/theme';
import { cashierMenu } from '../../../components/cashiersidebar';

/**
 * Sales — the website's cashier/sales index: the member's OWN sales (the
 * whole branch's while an HQ monitor watches it, read-only) with the same
 * date range the Blade page offers, the window total, and the receipt
 * behind each row. The New button starts a sale on the own branch only —
 * the server refuses checkout while monitoring.
 */
export default function CashierSales() {
  const monitor = useSyncExternalStore(cashierMonitor.subscribe, cashierMonitor.get, cashierMonitor.get);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () =>
      fetchCashierSales({
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        monitor_branch: monitor?.id,
      }),
    [dateFrom, dateTo, monitor?.id],
  );
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const hasFilters = Boolean(dateFrom || dateTo);
  const monitoring = Boolean(data?.scope?.in_cross_branch);

  return (
    <AdminPage
      onMenu={cashierMenu.open}
      title="Sales"
      eyebrow={data?.scope?.branch_name ?? 'Cashier'}
      accent={CASHIER_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        monitoring ? undefined : (
          <Pressable
            onPress={() => router.push('/cashier/sale-new')}
            style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
            accessibilityRole="button"
            accessibilityLabel="New sale"
          >
            <Text style={styles.newBtnText}>+ New</Text>
          </Pressable>
        )
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {monitoring ? (
        <Banner kind="connection" message={`Watching every sale at ${data?.scope?.branch_name ?? 'the monitored branch'} — read-only.`} />
      ) : null}

      <ChipRow>
        <Chip label="All dates" active={!dateFrom && !dateTo} onPress={() => { setDateFrom(''); setDateTo(''); }} />
        <Chip
          label="Today"
          active={dateFrom === isoDay(0) && dateTo === isoDay(0)}
          onPress={() => { setDateFrom(isoDay(0)); setDateTo(isoDay(0)); }}
        />
        <Chip
          label="This week"
          active={dateFrom === isoDay(-7) && !dateTo}
          onPress={() => { setDateFrom(isoDay(-7)); setDateTo(''); }}
        />
        <Chip
          label="This month"
          active={dateFrom === isoDay(-30) && !dateTo}
          onPress={() => { setDateFrom(isoDay(-30)); setDateTo(''); }}
        />
      </ChipRow>

      {loading && !data ? (
        <LoadingView label="Loading sales…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.sales.length === 0 ? (
        <EmptyView
          icon="receipt-outline"
          title={hasFilters ? 'No sales in this range' : 'No sales yet'}
          hint={hasFilters ? 'Widen the dates to see more.' : 'Ring up the first sale from the New button above.'}
        />
      ) : (
        <>
          <GroupLabel>Latest first</GroupLabel>
          <DataCard
            title={hasFilters ? 'Filtered total' : 'Total'}
            subtitle={`${data.sales.length} sales`}
            badge={formatMoney(data.totalSales)}
            badgeTone="success"
          />
          {data.sales.map((sale) => (
            <DataCard
              key={String(sale.id)}
              title={sale.sale_number ?? `Sale #${sale.id}`}
              subtitle={sale.customer?.name ?? 'Walk-in customer'}
              badge={formatMoney(sale.total ?? 0)}
              badgeTone="success"
              lines={[
                formatDateTime(sale.created_at),
                sale.cashier?.name ? `Cashier: ${sale.cashier.name}` : null,
                sale.payment_summary ?? null,
              ].filter(Boolean) as string[]}
              onPress={() => router.push({ pathname: '/cashier/sale-detail', params: { id: String(sale.id) } })}
            />
          ))}
        </>
      )}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

function isoDay(offsetDays: number): string {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  return d.toISOString().slice(0, 10);
}

const styles = StyleSheet.create({
  newBtn: {
    backgroundColor: CASHIER_ACCENT.soft,
    borderColor: CASHIER_ACCENT.border,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  newBtnText: { color: CASHIER_ACCENT.light, fontWeight: '800', fontSize: 12 },
});
