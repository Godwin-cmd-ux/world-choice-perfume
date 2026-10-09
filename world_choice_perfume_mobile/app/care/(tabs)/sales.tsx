import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { careMenu } from '../../../components/caresidebar';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchSales } from '../../../lib/careApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS } from '../../../lib/theme';

/**
 * Sales — the website's customer-care/sales index: the branch's latest 50
 * sales with the customer, cashier, items, total, status, branch and date,
 * the revenue total, and the same filters the website's sales pages carry
 * (a date range plus the payment status). The header button starts a new
 * sale — the blade's "New Sale".
 */
const STATUSES = [
  { key: '', label: 'All' },
  { key: 'paid', label: 'Paid' },
  { key: 'pending', label: 'Pending' },
  { key: 'cancelled', label: 'Cancelled' },
] as const;

export default function CareSales() {
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [status, setStatus] = useState('');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchSales({ date_from: dateFrom || undefined, date_to: dateTo || undefined, status: status || undefined }),
    [dateFrom, dateTo, status],
  );
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const hasFilters = Boolean(dateFrom || dateTo || status);

  return (
    <AdminPage
      title="Sales"
      eyebrow="Customer Care"
      accent={CC_ACCENT.main}
      onMenu={careMenu.open}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        <Pressable
          onPress={() => router.push('/care/sale-new')}
          style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
          accessibilityRole="button"
          accessibilityLabel="New sale"
        >
          <Text style={styles.newBtnText}>+ New</Text>
        </Pressable>
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

      {/* Date range — the same chips the branch-admin sales screen offers. */}
      <ChipRow>
        <Chip label="All dates" active={!dateFrom && !dateTo} onPress={() => { setDateFrom(''); setDateTo(''); }} />
        <Chip label="Today" active={dateFrom === isoDay(0) && !dateTo} onPress={() => { setDateFrom(isoDay(0)); setDateTo(isoDay(0)); }} />
        <Chip label="This week" active={dateFrom === isoDay(-7) && !dateTo} onPress={() => { setDateFrom(isoDay(-7)); setDateTo(''); }} />
        <Chip label="This month" active={dateFrom === isoDay(-30) && !dateTo} onPress={() => { setDateFrom(isoDay(-30)); setDateTo(''); }} />
      </ChipRow>

      {/* Payment status — the blade's status column, as a filter. */}
      <ChipRow>
        {STATUSES.map((s) => (
          <Chip
            key={s.key}
            label={s.label}
            active={status === s.key}
            onPress={() => setStatus(s.key)}
          />
        ))}
      </ChipRow>

      {loading && !data ? (
        <LoadingView label="Loading sales…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.sales.length === 0 ? (
        <EmptyView
          icon="cart-outline"
          title={hasFilters ? 'No sales match these filters' : 'No sales yet'}
          hint={hasFilters ? 'Widen the dates or clear the status filter.' : 'Ring up the first sale with the + New button above.'}
        />
      ) : (
        <>
          <GroupLabel>Latest first</GroupLabel>
          <DataCard
            title={hasFilters ? 'Filtered total' : 'Total'}
            subtitle={`${data.sales.length} sales`}
            badge={formatMoney(data.totalRevenue)}
            badgeTone="success"
          />
          {data.sales.map((sale) => {
            const names = ((sale.items ?? []) as { product?: { name?: string } }[])
              .map((it) => it.product?.name)
              .filter((n): n is string => Boolean(n));
            const extra = names.length - 3;
            const itemsLine =
              names.length === 0
                ? '—'
                : `${names.slice(0, 3).join(', ')}${extra > 0 ? ` +${extra} more` : ''}`;
            const pay = sale.payment_status ?? 'pending';
            return (
              <DataCard
                key={String(sale.id)}
                title={String(sale.sale_number ?? `Sale #${sale.id}`)}
                subtitle={[sale.customer?.name ?? '—', sale.cashier?.name ?? '—'].join(' · ')}
                badge={pay}
                badgeTone={pay === 'paid' ? 'success' : pay === 'cancelled' ? 'danger' : 'warning'}
                lines={[
                  itemsLine,
                  `Branch: ${sale.branch?.name ?? data.scope.branch_name ?? '—'}`,
                  `${formatDateTime(sale.created_at)}${sale.payment_summary ? ` · ${sale.payment_summary}` : ''}`,
                ]}
                onPress={() => router.push({ pathname: '/care/sale-detail', params: { id: String(sale.id) } })}
              />
            );
          })}
        </>
      )}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

/** YYYY-MM-DD for `offset` days from today (the chip presets). */
function isoDay(offsetDays: number): string {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  return d.toISOString().slice(0, 10);
}

const styles = StyleSheet.create({
  newBtn: {
    backgroundColor: 'rgba(56, 189, 248, 0.12)',
    borderColor: 'rgba(56, 189, 248, 0.30)',
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  newBtnText: { color: COLORS.info, fontWeight: '800', fontSize: 12 },
});
