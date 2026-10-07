import { router } from 'expo-router';
import { useState } from 'react';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchStockReport, type StockReportPayload } from '../../lib/adminApi';

/**
 * Stock report — the mobile twin of super-admin/reports/stock: units,
 * selling price and stock value for every branch_stock row, branch filter,
 * totals on top (same calculation: quantity × selling_price).
 */
export default function ReportStock() {
  const [branchId, setBranchId] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<StockReportPayload>(
    () => fetchStockReport({ branch_id: branchId || undefined }),
    [branchId],
  );

  const branches = data?.branches ?? [];
  const rows = data?.report ?? [];

  return (
    <AdminPage title="Stock Report" onBack={() => router.back()} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Building report…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}

          <ChipRow>
            <Chip label="All branches" active={!branchId} onPress={() => setBranchId('')} />
            {branches.map((b) => (
              <Chip key={String(b.id)} label={b.name} active={branchId === String(b.id)} onPress={() => setBranchId(String(b.id))} />
            ))}
          </ChipRow>

          <StatGrid>
            <StatTile label="Stock Value" value={money(data.total_value)} />
            <StatTile label="Units On Hand" value={data.total_units.toLocaleString('en-US')} tone="success" />
            <StatTile label="Lines" value={rows.length} tone="info" />
          </StatGrid>

          <GroupLabel>Products</GroupLabel>
          {rows.length === 0 ? (
            <EmptyView icon="cube-outline" title="No stock rows" hint="Nothing matches this filter." />
          ) : (
            rows.map((r, i) => (
              <DataCard
                key={`${r.product}-${r.branch}-${i}`}
                title={r.product}
                subtitle={`${r.brand ? r.brand + ' · ' : ''}${r.branch}`}
                badge={money(r.stock_value)}
                lines={[`${r.quantity} units × ${money(r.selling_price)}`]}
              />
            ))
          )}
        </>
      ) : null}
    </AdminPage>
  );
}

function money(v: number | string | null | undefined): string {
  const n = Number(v ?? 0);
  return 'TZS ' + n.toLocaleString('en-US', { maximumFractionDigits: n > 0 && n < 100 ? 2 : 0 }  );
}
