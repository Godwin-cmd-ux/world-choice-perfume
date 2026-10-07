import { router } from 'expo-router';
import { Banner } from '../../components/authkit';
import { AdminPage, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchCrossBranchStock } from '../../lib/adminApi';

/**
 * Cross-Branch Stock — the mobile twin of the stock-manager cross-branch
 * dashboard the Super Admin sidebar links: per-branch product units, low
 * stock lines (≤5), bottles and oil fragrances. Read-only monitoring, same
 * figures as the website.
 */
export default function CrossBranchStock() {
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(() => fetchCrossBranchStock(), []);
  const rows = data?.rows ?? [];

  const totals = rows.reduce(
    (acc, r) => ({
      products: acc.products + r.total_products,
      low: acc.low + r.low_stock,
      bottles: acc.bottles + r.total_bottles,
      oils: acc.oils + r.total_oils,
    }),
    { products: 0, low: 0, bottles: 0, oils: 0 },
  );

  return (
    <AdminPage title="Cross-Branch Stock" onBack={() => router.back()} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Loading stock…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}
      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}
          <StatGrid>
            <StatTile label="Product Units" value={totals.products.toLocaleString('en-US')} />
            <StatTile label="Low Stock Lines" value={totals.low} tone={totals.low > 0 ? 'warning' : 'success'} />
            <StatTile label="Bottles" value={totals.bottles.toLocaleString('en-US')} />
            <StatTile label="Oil Fragrances" value={totals.oils.toLocaleString('en-US')} />
          </StatGrid>

          <GroupLabel>By branch</GroupLabel>
          {rows.length === 0 ? (
            <EmptyView icon="cube-outline" title="No branches" hint="Nothing to monitor yet." />
          ) : (
            rows.map((row) => (
              <DataCard
                key={String(row.id)}
                title={row.name}
                subtitle={row.address ?? undefined}
                badge={row.low_stock > 0 ? `${row.low_stock} low` : 'OK'}
                badgeTone={row.low_stock > 0 ? 'warning' : 'success'}
                lines={[
                  `Products: ${row.total_products.toLocaleString('en-US')} units  ·  Low stock: ${row.low_stock}`,
                  `Bottles: ${row.total_bottles.toLocaleString('en-US')}  ·  Oils: ${row.total_oils.toLocaleString('en-US')}`,
                ]}
              />
            ))
          )}
        </>
      ) : null}
    </AdminPage>
  );
}
