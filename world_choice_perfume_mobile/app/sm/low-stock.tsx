import { router } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchLowStock } from '../../lib/smApi';
import { COLORS, SM_ACCENT } from '../../lib/theme';

/**
 * Low-stock list — the same server-side `quantity <= 5` filter the dashboard
 * counts with, so the card number and this list always agree.
 */
export default function SmLowStock() {
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(() => fetchLowStock(), []);

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const rows = data?.rows ?? [];

  return (
    <AdminPage
      title="Low Stock"
      eyebrow="Stock Manager"
      accent={SM_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={refresh}
    >
      {error ? <Banner kind="error" message={error} /> : null}

      {loading ? (
        <LoadingView label="Loading…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : (
        <>
          <StatGrid>
            <StatTile label="Threshold" value={`≤ ${data?.threshold ?? 5}`} hint="units per product" tone="info" />
            <StatTile label="Products to restock" value={rows.length} tone={rows.length > 0 ? 'danger' : 'success'} />
          </StatGrid>

          <GroupLabel>Needs restocking</GroupLabel>
          {rows.length === 0 ? (
            <EmptyView icon="checkmark-circle-outline" title="All stocked up" hint="No product is at or below the threshold." />
          ) : (
            rows.map((row, i) => (
              <DataCard
                key={String(row.product_id ?? i)}
                title={row.name}
                subtitle={row.brand ?? undefined}
                lines={[`${row.category ?? ''} · ${formatMoney(row.selling_price)} TZS`]}
                badge={`${row.quantity} left`}
                badgeTone="danger"
                onPress={() => router.push({ pathname: '/sm/stock-entry', params: { product_id: String(row.product_id ?? '') } })}
              />
            ))
          )}
          <Text style={styles.hint}>Tap a product to top it up.</Text>
        </>
      )}
    </AdminPage>
  );
}

function formatMoney(value: number): string {
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
}

const styles = StyleSheet.create({
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  hint: { color: COLORS.textMuted, fontSize: 12.5, textAlign: 'center', marginTop: 6 },
});
