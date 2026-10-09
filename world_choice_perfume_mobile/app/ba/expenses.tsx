import { router } from 'expo-router';
import { useState } from 'react';
import { AuthField, Banner } from '../../components/authkit';
import { baMenu } from '../../components/basidebar';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { fetchBaExpenses } from '../../lib/baApi';
import { formatDateTime, formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { BA_ACCENT } from '../../lib/theme';

/**
 * Expenses — the website's branch-admin/expenses index, view-only exactly
 * as the Blade page: cashiers commit the expenses, the admin reads them.
 *
 * With no dates the server window is the current business day, so the
 * total starts at zero after midnight and climbs; picking a date range
 * replaces the window. `rangeLabel` comes back from the server the same
 * way the website renders $rangeLabel.
 */
export default function BaExpenses() {
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [category, setCategory] = useState('');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchBaExpenses({ date_from: dateFrom || undefined, date_to: dateTo || undefined, category: category.trim() || undefined }),
    [dateFrom, dateTo, category],
  );
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const clearAll = () => {
    setDateFrom('');
    setDateTo('');
    setCategory('');
  };

  return (
    <AdminPage
      title="Expenses"
      eyebrow="Branch Admin · view only"
      accent={BA_ACCENT.main}
      onBack={() => router.back()}
      onMenu={baMenu.open}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      <Banner kind="connection" message="Expenses are committed by cashiers — this screen only reads them." />

      <GroupLabel>Window</GroupLabel>
      <AuthField label="From (YYYY-MM-DD)" value={dateFrom} onChangeText={setDateFrom} placeholder="2026-10-01" icon="calendar-outline" autoCapitalize="none" />
      <AuthField label="To (YYYY-MM-DD)" value={dateTo} onChangeText={setDateTo} placeholder="2026-10-07" icon="calendar-outline" autoCapitalize="none" />
      <AuthField label="Category (optional)" value={category} onChangeText={setCategory} placeholder="e.g. rent, transport" icon="pricetag-outline" autoCapitalize="none" />
      <GoldButton label="Apply window" icon="funnel-outline" onPress={reload} />
      <GoldButton label="Reset to today" icon="refresh-outline" onPress={clearAll} style={{ marginTop: 4 }} />

      {loading && !data ? (
        <LoadingView label="Loading expenses…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.expenses.length === 0 ? (
        <EmptyView icon="cash-outline" title="No expenses in this window" hint="Widen the dates or reset to today." />
      ) : (
        <>
          <GroupLabel>Window total</GroupLabel>
          <DataCard
            title={formatMoney(data.totalExpenses)}
            subtitle={data.rangeLabel}
            badge={`${data.expenses.length} rows`}
            badgeTone="gold"
          />

          <GroupLabel>Latest first</GroupLabel>
          {data.expenses.map((expense) => (
            <DataCard
              key={String(expense.id)}
              title={`${formatMoney(expense.amount ?? 0)}${expense.category ? ` · ${expense.category}` : ''}`}
              subtitle={expense.user?.name ?? undefined}
              badge="expense"
              badgeTone="muted"
              lines={[
                expense.description ?? null,
                formatDateTime(expense.created_at),
              ].filter(Boolean) as string[]}
            />
          ))}
        </>
      )}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}
