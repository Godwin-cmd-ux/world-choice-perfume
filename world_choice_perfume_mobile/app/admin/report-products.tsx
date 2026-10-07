import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchProductPerformanceReport, type ProductPerfPayload } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';

/**
 * Product performance report — the mobile twin of
 * super-admin/reports/product-performance: same revenue/sold/remaining
 * figures plus the website's sell-through and contribution percentages,
 * ranked by revenue, with the same date-range + branch filters.
 */
export default function ReportProducts() {
  const [dateFrom, setDateFrom] = useState(monthStart());
  const [dateTo, setDateTo] = useState(today());
  const [branchId, setBranchId] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<ProductPerfPayload>(
    () => fetchProductPerformanceReport({ date_from: dateFrom, date_to: dateTo, branch_id: branchId || undefined }),
    [dateFrom, dateTo, branchId],
  );

  const branches = data?.branches ?? [];
  const rows = data?.report ?? [];

  return (
    <AdminPage title="Product Performance" onBack={() => router.back()} refreshing={refreshing} onRefresh={refresh}>
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
            <StatTile label="Total Revenue" value={money(data.total_revenue)} />
            <StatTile label="Products" value={rows.length} tone="info" />
          </StatGrid>

          <GroupLabel>Ranked by revenue</GroupLabel>
          {rows.length === 0 ? (
            <EmptyView icon="pricetag-outline" title="No product sales" hint="Nothing sold in this range." />
          ) : (
            rows.map((r, i) => (
              <DataCard
                key={String(r.id)}
                title={`${i + 1}. ${r.name}`}
                badge={money(r.total_revenue)}
                lines={[
                  `Sold: ${r.total_sold} units  ·  Remaining: ${r.remaining}  ·  Available: ${r.available}`,
                  `Sell-through: ${r.sell_through}%  ·  Revenue contribution: ${r.contribution}%`,
                ]}
              />
            ))
          )}
        </>
      ) : null}
    </AdminPage>
  );
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
