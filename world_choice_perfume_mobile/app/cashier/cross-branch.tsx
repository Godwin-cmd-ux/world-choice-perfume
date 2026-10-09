import { router } from 'expo-router';
import { useSyncExternalStore } from 'react';
import { Banner } from '../../components/authkit';
import { AdminPage, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchCashierCrossBranch } from '../../lib/cashierApi';
import { cashierMonitor } from '../../lib/cashierMonitor';
import { formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CASHIER_ACCENT } from '../../lib/theme';
import { cashierMenu } from '../../components/cashiersidebar';

/**
 * Cross-Branch Monitoring — the website's cashier/cross-branch page for the
 * HQ cashier (Head Quarters-Mikocheni) or the Super Admin: every branch's
 * today — revenue, transactions, paid sales, pending queue and the cashiers
 * on the floor. Tapping a branch "enters" it: the choice is remembered here
 * (the website stores it in the HTTP session) and sent as `monitor_branch`
 * with each read; the server re-authorises monitor rights on every call and
 * refuses every write while monitoring, so this screen stays read-only.
 */
export default function CashierCrossBranch() {
  const monitor = useSyncExternalStore(cashierMonitor.subscribe, cashierMonitor.get, cashierMonitor.get);
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchCashierCrossBranch(), []);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const forbidden = (error ?? '').includes('monitor other branches');
  const branches = data?.branches ?? [];

  const enter = (id: number, name: string) => {
    cashierMonitor.set({ id, name });
    router.replace('/cashier');
  };

  return (
    <AdminPage
      onMenu={cashierMenu.open}
      title="Cross-Branch"
      eyebrow="Head Quarters"
      accent={CASHIER_ACCENT.main}
      onBack={() => router.back()}
    >
      {forbidden ? (
        <Banner
          kind="error"
          message={error ?? 'Only the HQ cashier or the Super Admin can monitor other branches.'}
        />
      ) : (
        <>
          <Banner kind="connection" message="Read-only monitoring: pick a branch to watch its dashboard, sales and orders." />
          {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

          {monitor ? (
            <Banner
              kind="success"
              message={`Currently watching ${monitor.name} — every screen follows that branch until you exit.`}
              actionLabel="Exit branch"
              onAction={() => {
                cashierMonitor.clear();
                reload();
              }}
            />
          ) : null}

          {loading && !data ? (
            <LoadingView label="Loading branches…" />
          ) : error && !data ? (
            <ErrorView message={error} onRetry={reload} />
          ) : branches.length === 0 ? (
            <EmptyView icon="business-outline" title="No branches to monitor" hint="Active branches appear here." />
          ) : (
            <>
              <GroupLabel>Branches today</GroupLabel>
              {branches.map((b) => (
                <DataCard
                  key={String(b.id)}
                  title={b.name}
                  subtitle={b.address ?? undefined}
                  badge={monitor?.id === b.id ? 'Watching' : 'Enter'}
                  badgeTone={monitor?.id === b.id ? 'success' : 'warning'}
                  onPress={() => enter(b.id, b.name)}
                >
                  <StatGrid>
                    <StatTile label="Today" value={formatMoney(b.todayRevenue)} />
                    <StatTile label="Transactions" value={b.todayTransactions} />
                    <StatTile label="Paid" value={b.todayPaid} />
                    <StatTile
                      label="Pending orders"
                      value={b.pendingOrders}
                      tone={b.pendingOrders > 0 ? 'danger' : 'success'}
                    />
                    <StatTile label="Cashiers" value={b.activeCashiers} />
                  </StatGrid>
                </DataCard>
              ))}
            </>
          )}
        </>
      )}
    </AdminPage>
  );
}
