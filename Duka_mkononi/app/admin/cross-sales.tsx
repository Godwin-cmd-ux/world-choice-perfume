import { router } from 'expo-router';
import { Banner } from '../../components/authkit';
import { AdminPage, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchCrossBranchSales } from '../../lib/adminApi';

/**
 * Cross-Branch Sales — the mobile twin of the cashier cross-branch dashboard
 * the Super Admin sidebar links: per-branch revenue today, transactions,
 * paid count, pending orders and how many cashiers are selling. Same
 * business-day figures as the website.
 */
export default function CrossBranchSales() {
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(() => fetchCrossBranchSales(), []);
  const rows = data?.rows ?? [];

  const totals = rows.reduce(
    (acc, r) => ({
      revenue: acc.revenue + r.today_revenue,
      tx: acc.tx + r.today_transactions,
      pending: acc.pending + r.pending_orders,
      cashiers: acc.cashiers + r.active_cashiers,
    }),
    { revenue: 0, tx: 0, pending: 0, cashiers: 0 },
  );

  return (
    <AdminPage title="Cross-Branch Sales" onBack={() => router.back()} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Loading sales…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}
      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}
          <StatGrid>
            <StatTile label="Revenue Today" value={money(totals.revenue)} />
            <StatTile label="Transactions" value={totals.tx} tone="info" />
            <StatTile label="Pending Orders" value={totals.pending} tone={totals.pending > 0 ? 'warning' : 'success'} />
            <StatTile label="Cashiers Selling" value={totals.cashiers} tone="success" />
          </StatGrid>

          <GroupLabel>By branch</GroupLabel>
          {rows.length === 0 ? (
            <EmptyView icon="cash-outline" title="No branches" hint="Nothing to monitor yet." />
          ) : (
            rows.map((row) => (
              <DataCard
                key={String(row.id)}
                title={row.name}
                subtitle={row.address ?? undefined}
                badge={row.pending_orders > 0 ? `${row.pending_orders} pending` : 'Clear'}
                badgeTone={row.pending_orders > 0 ? 'warning' : 'success'}
                lines={[
                  `Today: ${money(row.today_revenue)}  ·  ${row.today_transactions} transactions (${row.today_paid} paid)`,
                  `Pending orders: ${row.pending_orders}  ·  Cashiers active: ${row.active_cashiers}`,
                ]}
              />
            ))
          )}
        </>
      ) : null}
    </AdminPage>
  );
}

function money(v: number | string | null | undefined): string {
  return 'TZS ' + Number(v ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
}
