import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchSellerSales } from '../../../lib/sellerApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { SELLER_ACCENT } from '../../../lib/theme';

/**
 * My Sales — the website's seller/sales index: the member's OWN sales
 * (cashier_id filter, server-side) with the same date range the Blade page
 * offers, the window total, and the receipt behind each row. Tap opens the
 * receipt; the header button starts a new sale.
 */
export default function SellerSales() {
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchSellerSales({ date_from: dateFrom || undefined, date_to: dateTo || undefined }),
    [dateFrom, dateTo],
  );
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const hasFilters = Boolean(dateFrom || dateTo);

  return (
    <AdminPage
      title="My Sales"
      eyebrow="Seller"
      accent={SELLER_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        <Pressable
          onPress={() => router.push('/seller/sale-new')}
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
              lines={[formatDateTime(sale.created_at), sale.payment_summary ?? null].filter(Boolean) as string[]}
              onPress={() => router.push({ pathname: '/seller/sale-detail', params: { id: String(sale.id) } })}
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
    backgroundColor: SELLER_ACCENT.soft,
    borderColor: SELLER_ACCENT.border,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  newBtnText: { color: SELLER_ACCENT.light, fontWeight: '800', fontSize: 12 },
});
