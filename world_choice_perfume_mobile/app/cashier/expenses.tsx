import { router } from 'expo-router';
import { useSyncExternalStore, useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { cashierMonitor } from '../../lib/cashierMonitor';
import { fetchCashierExpenses } from '../../lib/cashierApi';
import { formatDateTime, formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CASHIER_ACCENT, COLORS } from '../../lib/theme';

/**
 * Expenses — the website's cashier/expenses index: the active branch's
 * expenses (own branch, or the monitored one — read-only) with the
 * business-day window the total starts from, a date range that replaces
 * it, and the category filter. Cashiers are the only role that commits
 * expenses; the header button opens the form (own branch only).
 */
export default function CashierExpenses() {
  const monitor = useSyncExternalStore(cashierMonitor.subscribe, cashierMonitor.get, cashierMonitor.get);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [category, setCategory] = useState('');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () =>
      fetchCashierExpenses({
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        category: category || undefined,
        monitor_branch: monitor?.id,
      }),
    [dateFrom, dateTo, category, monitor?.id],
  );
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const hasFilters = Boolean(dateFrom || dateTo || category);
  const monitoring = Boolean(data?.scope?.in_cross_branch);

  return (
    <AdminPage
      title="Expenses"
      eyebrow={data?.scope?.branch_name ?? 'Cashier'}
      accent={CASHIER_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        monitoring ? undefined : (
          <Pressable
            onPress={() => router.push('/cashier/expense-new')}
            style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
            accessibilityRole="button"
            accessibilityLabel="Record expense"
          >
            <Text style={styles.newBtnText}>+ Record</Text>
          </Pressable>
        )
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {monitoring ? (
        <Banner kind="connection" message={`Watching ${data?.scope?.branch_name ?? 'another branch'} — expenses cannot be recorded while monitoring.`} />
      ) : null}

      <ChipRow>
        <Chip label="Business day" active={!dateFrom && !dateTo} onPress={() => { setDateFrom(''); setDateTo(''); }} />
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

      <ChipRow>
        <Chip label="All categories" active={!category} onPress={() => setCategory('')} />
        {(data?.categories ?? []).map((c) => (
          <Chip
            key={c}
            label={c.charAt(0).toUpperCase() + c.slice(1)}
            active={category === c}
            onPress={() => setCategory(category === c ? '' : c)}
          />
        ))}
      </ChipRow>

      {loading && !data ? (
        <LoadingView label="Loading expenses…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.expenses.length === 0 ? (
        <EmptyView
          icon="cash-outline"
          title={hasFilters ? 'No expenses in this window' : 'No expenses yet'}
          hint={hasFilters ? 'Widen the dates or clear the category.' : 'Recorded expenses show up here with a running total.'}
        />
      ) : (
        <>
          <GroupLabel right={<Text style={styles.muted}>{data.rangeLabel}</Text>}>Window total</GroupLabel>
          <DataCard
            title={hasFilters ? 'Filtered total' : 'Today so far'}
            subtitle={`${data.expenses.length} expenses`}
            badge={formatMoney(data.totalExpenses)}
            badgeTone="warning"
          />
          {data.expenses.map((expense) => (
            <DataCard
              key={String(expense.id)}
              title={`${formatMoney(expense.amount ?? 0)} · ${String(expense.category ?? 'other')}`}
              subtitle={expense.description ?? undefined}
              lines={[
                formatDateTime(expense.created_at),
                expense.user?.name ? `By ${expense.user.name}` : null,
              ].filter(Boolean) as string[]}
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
  muted: { color: COLORS.textMuted, fontSize: 11 },
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
