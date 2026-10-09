import { router } from 'expo-router';
import { useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { Chip, ChipRow, DataCard, SearchInput, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchAdminOrders, type AdminOrdersPayload } from '../../../lib/adminApi';
import { COLORS } from '../../../lib/theme';
import { AdminMenuButton } from '../../../components/adminsidebar';

/**
 * Orders monitor tab — the mobile twin of super-admin/orders/index.blade.php.
 * Same three tabs (Pending / On Progress / Completed) with the website's
 * counts, the all-branches scope, branch filter, search, waiting durations,
 * and a tap-through to the order detail (where the personal-name correction
 * lives, just like the website).
 */
export default function AdminOrders() {
  const [tab, setTab] = useState('pending');
  const [branchId, setBranchId] = useState('');
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<AdminOrdersPayload>(
    () => fetchAdminOrders({ tab, branch_id: branchId || undefined, q: query || undefined }),
    [tab, branchId, query],
  );

  if (loading) return <LoadingView label="Loading orders…" />;
  if (sessionExpired) {
    return (
      <View style={styles.guard}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }
  if (error && !data) return <ErrorView message={error} onRetry={reload} />;

  const tabs = Object.entries(data?.tab_labels ?? {});
  const counts = data?.counts ?? {};

  return (
    <View style={styles.root}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={COLORS.gold} />}
        keyboardShouldPersistTaps="handled"
      >
        <View>
          <AdminMenuButton />
          <Text style={styles.eyebrow}>All branches</Text>
          <Text style={styles.h1}>Orders</Text>
        </View>

        {error ? <Banner kind="error" message={error} /> : null}

        {/* Status tabs with the website's live counts. */}
        <ChipRow>
          {tabs.map(([key, label]) => (
            <Chip
              key={key}
              label={`${label}${counts[key] !== undefined ? ` (${counts[key]})` : ''}`}
              active={tab === key}
              onPress={() => setTab(key)}
            />
          ))}
        </ChipRow>

        {/* Branch filter. */}
        <ChipRow>
          <Chip label="All branches" active={!branchId} onPress={() => setBranchId('')} />
          {(data?.branches ?? []).map((b) => (
            <Chip key={String(b.id)} label={b.name} active={branchId === String(b.id)} onPress={() => setBranchId(String(b.id))} />
          ))}
        </ChipRow>

        <SearchInput
          value={search}
          onChangeText={setSearch}
          placeholder="Order number or customer…"
        />
        {search !== query ? (
          <Text style={styles.applyHint} onPress={() => setQuery(search)}>
            Tap to apply “{search}”
          </Text>
        ) : null}

        {(data?.orders ?? []).length === 0 ? (
          <EmptyView icon="bag-handle-outline" title="No orders here" hint="Nothing matches this tab and filter." />
        ) : (
          (data?.orders ?? []).map((order) => {
            const pickers = (data?.pickers ?? {}) as Record<string, string>;
            const pickerName =
              typeof order.picked_by_name === 'string'
                ? order.picked_by_name
                : pickers[String(order.picked_by ?? '')] ?? null;
            return (
              <DataCard
                key={String(order.id)}
                title={String(order.personal_order_name || order.order_number || `Order #${order.id}`)}
                subtitle={order.branch?.name ?? undefined}
                badge={order.status ? String(order.status) : undefined}
                badgeTone={order.status === 'pending' ? 'warning' : order.status === 'completed' ? 'success' : 'gold'}
                lines={[
                  order.customer_name ? `Customer: ${order.customer_name}` : null,
                  pickerName ? `Picked by: ${pickerName}` : null,
                  `${order.duration_label ?? ''}${order.duration_label ? ' waiting' : ''}${order.total ? `  ·  TZS ${Number(order.total).toLocaleString('en-US')}` : ''}`.trim() || null,
                ]}
                onPress={() => router.push({ pathname: '/admin/order-detail', params: { id: String(order.id) } })}
              />
            );
          })
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  content: { padding: 16, paddingBottom: 40, gap: 12 },
  guard: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  eyebrow: {
    color: COLORS.goldDark,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 3,
    textTransform: 'uppercase',
  },
  h1: { color: COLORS.text, fontSize: 25, fontWeight: '800', marginTop: 4 },
  applyHint: { color: COLORS.gold, fontSize: 13, fontWeight: '600' },
});
