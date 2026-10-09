import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../../components/authkit';
import { baMenu } from '../../../components/basidebar';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchBaSales } from '../../../lib/baApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { BA_ACCENT } from '../../../lib/theme';

/**
 * Sales — the website's branch-admin/sales index: the branch's latest 50
 * sales with the same filters the Blade page offers (a cashier chip row and
 * a date range), the window total, and the approved cashiers you can filter
 * by. Tap opens the receipt; the header button starts a new sale.
 */
export default function BaSales() {
  const [cashierId, setCashierId] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchBaSales({ cashier_id: cashierId || undefined, date_from: dateFrom || undefined, date_to: dateTo || undefined }),
    [cashierId, dateFrom, dateTo],
  );
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const hasFilters = Boolean(cashierId || dateFrom || dateTo);

  return (
    <AdminPage
      title="Sales"
      eyebrow="Branch Admin"
      accent={BA_ACCENT.main}
      onMenu={baMenu.open}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        <Pressable
          onPress={() => router.push('/ba/sale-new')}
          style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
          accessibilityRole="button"
          accessibilityLabel="New sale"
        >
          <Text style={styles.newBtnText}>+ New</Text>
        </Pressable>
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

      <ChipRow>
        <Chip label="All cashiers" active={!cashierId} onPress={() => setCashierId('')} />
        {(data?.cashiers ?? []).map((c) => (
          <Chip
            key={String(c.id)}
            label={c.name}
            active={cashierId === String(c.id)}
            onPress={() => setCashierId(cashierId === String(c.id) ? '' : String(c.id))}
          />
        ))}
      </ChipRow>

      <ChipRow>
        <Chip label="Today" active={!dateFrom && !dateTo} onPress={() => { setDateFrom(''); setDateTo(''); }} />
        <Chip
          label="This week"
          active={dateFrom === weekAgo() && !dateTo}
          onPress={() => { setDateFrom(weekAgo()); setDateTo(''); }}
        />
        <Chip
          label="This month"
          active={dateFrom === monthAgo() && !dateTo}
          onPress={() => { setDateFrom(monthAgo()); setDateTo(''); }}
        />
      </ChipRow>

      {loading && !data ? (
        <LoadingView label="Loading sales…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.sales.length === 0 ? (
        <EmptyView
          icon="cart-outline"
          title={hasFilters ? 'No sales match these filters' : 'No sales yet'}
          hint={hasFilters ? 'Widen the dates or clear the cashier filter.' : 'Ring up the first sale from the New button above.'}
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
              lines={[formatDateTime(sale.created_at), sale.cashier?.name ? `Cashier: ${sale.cashier.name}` : null, sale.payment_summary ?? null].filter(Boolean) as string[]}
              onPress={() => router.push({ pathname: '/ba/sale-detail', params: { id: String(sale.id) } })}
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

function weekAgo() {
  return isoDay(-7);
}

function monthAgo() {
  return isoDay(-30);
}

const styles = StyleSheet.create({
  newBtn: {
    backgroundColor: BA_ACCENT.soft,
    borderColor: BA_ACCENT.border,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  newBtnText: { color: BA_ACCENT.light, fontWeight: '800', fontSize: 12 },
});
