import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchCrossBranchRows } from '../../lib/smApi';
import { COLORS, SM_ACCENT } from '../../lib/theme';

/**
 * Cross-branch monitoring — the mobile twin of cross-branch.blade.php for
 * the Kinondoni stock manager: every other branch's product / bottle / oil
 * totals and low-stock counts. The server refuses anyone else (website's
 * message), and the whole module is read-only.
 */
export default function SmCrossBranch() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchCrossBranchRows(), []);

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const forbidden = (error ?? '').includes('monitor other branches');
  const rows = data?.rows ?? [];

  return (
    <AdminPage title="Cross-Branch" eyebrow="Kinondoni monitor" accent={SM_ACCENT.main} onBack={() => router.back()}>
      {forbidden ? (
        <Banner kind="error" message={error ?? 'Only the Kinondoni branch stock manager or the Super Admin can monitor other branches.'} />
      ) : (
        <>
          <Banner kind="connection" message="Read-only monitoring: this screen never changes another branch's stock." />
          {error ? <Banner kind="error" message={error} /> : null}

          {loading ? (
            <LoadingView label="Loading branches…" />
          ) : error && !data ? (
            <ErrorView message={error} onRetry={reload} />
          ) : rows.length === 0 ? (
            <EmptyView icon="eye-outline" title="No branches to monitor" hint="Other active branches appear here." />
          ) : (
            <>
              <GroupLabel>Other branches</GroupLabel>
              {rows.map((b) => (
                <DataCard
                  key={String(b.id)}
                  title={b.name}
                  subtitle={b.address ?? undefined}
                  badge={b.lowStock > 0 ? `${b.lowStock} low stock` : 'healthy'}
                  badgeTone={b.lowStock > 0 ? 'warning' : 'success'}
                >
                  <StatGrid>
                    <StatTile label="Products" value={b.totalProducts} />
                    <StatTile label="Low stock" value={b.lowStock} tone={b.lowStock > 0 ? 'danger' : 'success'} />
                    <StatTile label="Bottles" value={b.totalBottles} />
                    <StatTile label="Oils" value={b.totalOils} />
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

const styles = StyleSheet.create({
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
});
