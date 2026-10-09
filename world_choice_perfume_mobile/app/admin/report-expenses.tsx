import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchExpensesReport, type ExpensesReportPayload } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

/**
 * Expenses report — the mobile twin of super-admin/reports/expenses:
 * same default range (month start → today), same branch filter, same
 * by-category totals and counts, presented as cards instead of a table.
 */
export default function ReportExpenses() {
  const [dateFrom, setDateFrom] = useState(monthStart());
  const [dateTo, setDateTo] = useState(today());
  const [branchId, setBranchId] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<ExpensesReportPayload>(
    () => fetchExpensesReport({ date_from: dateFrom, date_to: dateTo, branch_id: branchId || undefined }),
    [dateFrom, dateTo, branchId],
  );

  const branches = data?.branches ?? [];
  const cats = [...(data?.expenses_by_category ?? [])].sort((a, b) => b.total - a.total);

  return (
    <AdminPage title="Expenses Report" onBack={() => router.back()} onMenu={adminMenu.open} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Building report…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}

          <ChipRow>
            <Chip label="This month" active={dateFrom === monthStart()} onPress={() => { setDateFrom(monthStart()); setDateTo(today()); }} />
            <Chip label="Last 7 days" active={dateFrom === daysAgo(7)} onPress={() => { setDateFrom(daysAgo(7)); setDateTo(today()); }} />
            <Chip label="Last 30 days" active={dateFrom === daysAgo(30)} onPress={() => { setDateFrom(daysAgo(30)); setDateTo(today()); }} />
          </ChipRow>

          <ChipRow>
            <Chip label="All branches" active={!branchId} onPress={() => setBranchId('')} />
            {branches.map((b) => (
              <Chip key={String(b.id)} label={b.name} active={branchId === String(b.id)} onPress={() => setBranchId(String(b.id))} />
            ))}
          </ChipRow>

          <Text style={styles.range}>
            {data.start_date} → {data.end_date}
          </Text>

          <StatGrid>
            <StatTile label="Total Expenses" value={money(data.total)} tone="danger" />
            <StatTile label="Categories" value={cats.length} tone="info" />
          </StatGrid>

          <GroupLabel>By category</GroupLabel>
          {cats.length === 0 ? (
            <EmptyView icon="wallet-outline" title="No expenses" hint="No expenses in this range." />
          ) : (
            cats.map((c) => (
              <DataCard
                key={c.category}
                title={categoryLabel(c.category)}
                badge={money(c.total)}
                badgeTone="danger"
                lines={[`${c.count} expense${c.count === 1 ? '' : 's'} in range`]}
              />
            ))
          )}
        </>
      ) : null}
    </AdminPage>
  );
}

function categoryLabel(cat: string): string {
  return cat.replace(/[_-]/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase());
}
function today(): string {
  return new Date().toISOString().slice(0, 10);
}
function monthStart(): string {
  const d = new Date();
  return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10);
}
function daysAgo(n: number): string {
  return new Date(Date.now() - n * 864e5).toISOString().slice(0, 10);
}
function money(v: number | string | null | undefined): string {
  return 'TZS ' + Number(v ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
}

const styles = StyleSheet.create({
  range: { color: COLORS.textMuted, fontSize: 12.5, textAlign: 'center' },
});
