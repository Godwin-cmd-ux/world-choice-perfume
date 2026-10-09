import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchStaffPerformanceReport, type StaffPerfPayload } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

/**
 * Staff performance report — the mobile twin of
 * super-admin/reports/staff-performance: same staff scope (cashiers, stock
 * managers, branch admins, active/approved), same date-range + branch
 * filters, same per-person totals/transactions/items, ranked by sales.
 */
export default function ReportStaff() {
  const [dateFrom, setDateFrom] = useState(monthStart());
  const [dateTo, setDateTo] = useState(today());
  const [branchId, setBranchId] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<StaffPerfPayload>(
    () => fetchStaffPerformanceReport({ date_from: dateFrom, date_to: dateTo, branch_id: branchId || undefined }),
    [dateFrom, dateTo, branchId],
  );

  const branches = data?.branches ?? [];
  const rows = data?.report ?? [];
  const totalSales = rows.reduce((sum, r) => sum + Number(r.total_sales ?? 0), 0);

  return (
    <AdminPage title="Staff Performance" onBack={() => router.back()} onMenu={adminMenu.open} refreshing={refreshing} onRefresh={refresh}>
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
            <StatTile label="Combined Sales" value={money(totalSales)} />
            <StatTile label="Staff Ranked" value={rows.length} tone="info" />
          </StatGrid>

          <GroupLabel>Ranked by sales</GroupLabel>
          {rows.length === 0 ? (
            <EmptyView icon="people-outline" title="No staff activity" hint="No sales in this range." />
          ) : (
            rows.map((r, i) => (
              <DataCard
                key={`${r.user_id}-${i}`}
                title={`${i + 1}. ${r.user_name}`}
                subtitle={`${roleLabel(r.role)} · ${r.branch_name}`}
                badge={money(r.total_sales)}
                lines={[`${r.transaction_count} transactions  ·  ${r.items_sold} items sold`]}
              />
            ))
          )}
        </>
      ) : null}
    </AdminPage>
  );
}

function roleLabel(role: string): string {
  return (role || 'staff')
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
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
