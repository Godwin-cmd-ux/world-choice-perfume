import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchCashierDailyOverview } from '../../lib/cashierApi';
import { staffSession } from '../../lib/staffSession';
import { CASHIER_ACCENT, COLORS, RADIUS } from '../../lib/theme';
import { cashierMenu } from '../../components/cashiersidebar';

/**
 * Daily Sales — All Branches: the website's cashier daily-sales-overview
 * (HQ cashier / Super Admin only). Company totals on top, then each
 * branch's sales / expenses / actual and its share of the company, with the
 * per-staff contribution list inside the branch (tap to expand).
 */
export default function CashierDailyOverview() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchCashierDailyOverview(), []);
  const [openId, setOpenId] = useState<string | null>(null);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const forbidden = (error ?? '').includes('monitor other branches');
  const rows = data?.rows ?? [];

  return (
    <AdminPage title="Daily Sales" eyebrow="All branches" accent={CASHIER_ACCENT.main} onBack={() => router.back()} onMenu={cashierMenu.open}>
      {forbidden ? (
        <Banner
          kind="error"
          message={error ?? 'Only the HQ cashier or the Super Admin can monitor other branches.'}
        />
      ) : (
        <>
          {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

          {loading && !data ? (
            <LoadingView label="Loading today's sales…" />
          ) : error && !data ? (
            <ErrorView message={error} onRetry={reload} />
          ) : data ? (
            <>
              <StatGrid>
                <StatTile label="Company Sales" value={money(data.companySales)} />
                <StatTile label="Expenses" value={money(data.companyExpenses)} tone="danger" />
                <StatTile label="Actual" value={money(data.companySales - data.companyExpenses)} tone="success" />
              </StatGrid>

              <GroupLabel>By branch</GroupLabel>
              {rows.length === 0 ? (
                <EmptyView icon="trending-up-outline" title="No active branches" hint="Nothing to summarise today." />
              ) : (
                rows.map((row) => {
                  const open = openId === String(row.id);
                  return (
                    <DataCard
                      key={String(row.id)}
                      title={row.name}
                      badge={`${row.salesPercent}%`}
                      lines={[
                        `Sales: ${money(row.dailySales)}  ·  Expenses: ${money(row.dailyExpenses)}`,
                        `Actual: ${money(row.actualSales)}  ·  Transactions: ${row.transactions}`,
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
                                  <Text style={styles.staffPct}>{s.salesPercent}% of branch</Text>
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
        </>
      )}
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
