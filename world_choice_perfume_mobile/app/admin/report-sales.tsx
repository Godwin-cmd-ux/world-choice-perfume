import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchSalesReport, type SalesReportPayload } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

/**
 * Sales report — the mobile twin of super-admin/reports/sales (and its PDF
 * / print variants: same rows and totals, rendered natively). Date + branch
 * filters, groups per branch with transactions/items, grand totals on top.
 */
export default function ReportSales() {
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [branchId, setBranchId] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<SalesReportPayload>(
    () => fetchSalesReport({ date, branch_id: branchId || undefined }),
    [date, branchId],
  );

  const branches = data?.branches ?? [];

  return (
    <AdminPage title="Sales Report" onBack={() => router.back()} onMenu={adminMenu.open} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Building report…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}

          <ChipRow>
            <Chip label="Today" active={date === today()} onPress={() => setDate(today())} />
            <Chip label="Yesterday" active={date === daysAgo(1)} onPress={() => setDate(daysAgo(1))} />
            <Chip label={daysAgo(2)} active={date === daysAgo(2)} onPress={() => setDate(daysAgo(2))} />
            <Chip label={daysAgo(3)} active={date === daysAgo(3)} onPress={() => setDate(daysAgo(3))} />
          </ChipRow>

          <ChipRow>
            <Chip label="All branches" active={!branchId} onPress={() => setBranchId('')} />
            {branches.map((b) => (
              <Chip key={String(b.id)} label={b.name} active={branchId === String(b.id)} onPress={() => setBranchId(String(b.id))} />
            ))}
          </ChipRow>

          <StatGrid>
            <StatTile label={`Sales · ${data.date}`} value={money(data.total_sales)} />
            <StatTile label="Transactions" value={data.total_transactions} tone="info" />
            <StatTile label="Items Sold" value={data.total_items} tone="success" />
          </StatGrid>

          <GroupLabel>By branch</GroupLabel>
          {data.branch_groups.length === 0 ? (
            <EmptyView icon="trending-up-outline" title="No sales" hint="No transactions on this day." />
          ) : (
            data.branch_groups.map((g) => (
              <DataCard
                key={g.branch_name}
                title={g.branch_name}
                badge={money(g.total_sales)}
                lines={[`${g.transactions} transactions  ·  ${g.items_sold} items`]}
              >
                <View style={{ gap: 4 }}>
                  {(g.sales as Record<string, unknown>[]).slice(0, 12).map((s, i) => {
                    const sale = s as { id?: unknown; sale_number?: string; total?: number; created_at?: string; cashier?: { name?: string }; items_count?: number };
                    return (
                      <View key={String(sale.id ?? i)} style={styles.line}>
                        <Text style={styles.lineMain} numberOfLines={1}>
                          {sale.sale_number ?? `Sale #${String(sale.id ?? i)}`} · {sale.cashier?.name ?? '—'}
                        </Text>
                        <Text style={styles.lineRight}>
                          {money(sale.total)} · {sale.items_count ?? 0} items
                        </Text>
                      </View>
                    );
                  })}
                  {(g.sales ?? []).length > 12 ? <Text style={styles.muted}>+{(g.sales ?? []).length - 12} more</Text> : null}
                </View>
              </DataCard>
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
function daysAgo(n: number): string {
  return new Date(Date.now() - n * 864e5).toISOString().slice(0, 10);
}
function money(v: number | string | null | undefined): string {
  return 'TZS ' + Number(v ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
}

const styles = StyleSheet.create({
  line: { flexDirection: 'row', justifyContent: 'space-between', gap: 10 },
  lineMain: { color: COLORS.textSecondary, fontSize: 13, flex: 1 },
  lineRight: { color: COLORS.goldLight, fontSize: 13, fontWeight: '600' },
  muted: { color: COLORS.textMuted, fontSize: 12 },
});
