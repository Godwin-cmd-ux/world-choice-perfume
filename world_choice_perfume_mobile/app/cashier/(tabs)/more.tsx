import { router } from 'expo-router';
import { useSyncExternalStore } from 'react';
import { Banner } from '../../../components/authkit';
import { AdminPage, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { ErrorView, LoadingView } from '../../../components/ui';
import { cashierMonitor } from '../../../lib/cashierMonitor';
import { fetchCashierScope } from '../../../lib/cashierApi';
import { staffSession } from '../../../lib/staffSession';
import { CASHIER_ACCENT } from '../../../lib/theme';
import { cashierMenu } from '../../../components/cashiersidebar';

/**
 * More — the hub the bottom bar cannot hold. The website sidebar runs to
 * four entries plus Account, and six for an HQ monitor (Daily Sales — All
 * Branches, Cross-Branch Monitoring), so the extras live here as grouped
 * tiles: Records (expenses), Head Quarters (monitoring, only when the
 * server says the member holds the HQ tier) and Account.
 */
export default function CashierMore() {
  const monitor = useSyncExternalStore(cashierMonitor.subscribe, cashierMonitor.get, cashierMonitor.get);

  const { data: scope, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchCashierScope({ monitor_branch: monitor?.id }),
    [monitor?.id],
  );

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  return (
    <AdminPage title="More" eyebrow={scope?.branch_name ?? 'Cashier'} accent={CASHIER_ACCENT.main} onMenu={cashierMenu.open}>
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {loading && !scope ? <LoadingView label="Loading…" /> : null}
      {error && !scope ? <ErrorView message={error} onRetry={reload} /> : null}

      {scope?.in_cross_branch ? (
        <Banner
          kind="connection"
          message={`Monitoring ${scope.branch_name ?? 'another branch'} — read-only.`}
          actionLabel="Exit branch"
          onAction={() => {
            cashierMonitor.clear();
            reload();
          }}
        />
      ) : null}

      {scope ? (
        <>
          <GroupLabel>Records</GroupLabel>
          <DataCard
            title="Expenses"
            subtitle="Branch expenses for the business day — record a new one"
            onPress={() => router.push('/cashier/expenses')}
          />
          <DataCard
            title="New Sale"
            subtitle="Ring up a retail or wholesale sale"
            onPress={() => router.push('/cashier/sale-new')}
          />

          {scope.is_cross_branch_monitor ? (
            <>
              <GroupLabel>Head Quarters</GroupLabel>
              <DataCard
                title="Cross-Branch Monitoring"
                subtitle="Today's takings, queue and cashiers at every branch"
                onPress={() => router.push('/cashier/cross-branch')}
              />
              <DataCard
                title="Daily Sales — All Branches"
                subtitle="Company-wide sales, expenses and staff contribution"
                onPress={() => router.push('/cashier/daily-overview')}
              />
            </>
          ) : null}

          <GroupLabel>Account</GroupLabel>
          <DataCard title="Profile" subtitle="Name, email, phone and password" onPress={() => router.push('/cashier/profile')} />
          <DataCard
            title="Sign Out"
            subtitle="End this staff session on the device"
            onPress={() => {
              staffSession.clear();
              router.replace('/staff');
            }}
          />
        </>
      ) : null}
    </AdminPage>
  );
}
