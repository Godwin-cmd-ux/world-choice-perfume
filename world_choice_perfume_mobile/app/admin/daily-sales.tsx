import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchDailySales, type DailySalesPayload } from '../../lib/adminApi';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * Daily Sales Overview — the mobile twin of the website's
 * super-admin/daily-sales-overview page: company totals on top, then each
 * branch's sales / expenses / actual and share of the company, with the
 * per-staff contribution list inside the branch (tap to expand).
 */
export default function DailySales() {
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<DailySalesPayload>(
    () => fetchDailySales(),
    [],
  );
  const [openId, setOpenId] = useState<string | null>(null);

  return (
    <AdminPage title="Daily Sales Overview" onBack={() => router.back()} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Loading today's sales…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}
      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}
          <StatGrid>
            <StatTile label="Company Sales" value={money(data.company_sales)} />
            <StatTile label="Expenses" value={money(data.company_expenses)} tone="danger" />
            <StatTile label="Actual" value={money(data.company_actual)} tone="success" />
          </StatGrid>

          <GroupLabel>By branch</GroupLabel>
          {data.rows.length === 0 ? (
            <EmptyView icon="trending-up-outline" title="No active branches" hint="Nothing to summarise today." />
          ) : (
            data.rows.map((row) => {
              const open = openId === String(row.id);
              return (
                <DataCard
                  key={String(row.id)}
                  title={row.name}
                  badge={`${row.sales_percent}%`}
                  lines={[
                    `Sales: ${money(row.daily_sales)}  ·  Expenses: ${money(row.daily_expenses)}`,
                    `Actual: ${money(row.actual_sales)}  ·  Transactions: ${row.transactions}`,
                  ]}
                  onPress={() => setOpenId(open ? null : String(row.id))}
                >
                  {open ? (
                    <View style={styles.staffBox}>
                      <Text style={styles.staffTitle}>Staff contribution</Text>
                      {row.staff.length === 0 ? (
                        <Text style={styles.muted}>No staff activity in this branch today.</Text>
                      ) : (
                        row.staff.map((s) => (
                          <View key={String(s.id)} style={styles.staffRow}>
                            <View style={{ flex: 1 }}>
                              <Text style={styles.staffName}>{s.name}</Text>
                              <Text style={styles.staffRole}>{roleLabel(s.role)}</Text>
                            </View>
                            <View style={{ alignItems: 'flex-end' }}>
                              <Text style={styles.staffSales}>{money(s.sales)}</Text>
                              <Text style={styles.staffPct}>{s.sales_percent}% of branch</Text>
                            </View>
                          </View>
                        ))
                      )}
                    </View>
                  ) : null}
                </DataCard>
              );
            })
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

function money(v: number | string | null | undefined): string {
  return 'TZS ' + Number(v ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
}

const styles = StyleSheet.create({
  staffBox: {
    backgroundColor: COLORS.bgRaised,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 10,
    gap: 8,
  },
  staffTitle: {
    color: COLORS.textMuted,
    fontSize: 10.5,
    fontWeight: '700',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  staffRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  staffName: { color: COLORS.text, fontSize: 13.5, fontWeight: '600' },
  staffRole: { color: COLORS.textMuted, fontSize: 11.5, marginTop: 1 },
  staffSales: { color: COLORS.goldLight, fontSize: 13.5, fontWeight: '700' },
  staffPct: { color: COLORS.textMuted, fontSize: 11, marginTop: 1 },
  muted: { color: COLORS.textMuted, fontSize: 12.5 },
});
