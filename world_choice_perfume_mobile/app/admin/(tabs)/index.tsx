import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchAdminDashboard, type AdminDashboardPayload } from '../../../lib/adminApi';
import { COLORS, RADIUS } from '../../../lib/theme';
import { AdminMenuButton } from '../../../components/adminsidebar';

/**
 * Super Admin dashboard — the mobile twin of super-admin/dashboard.blade.php:
 * today's revenue, sales count, pending orders, pending approvals, active
 * branches, company financials for the business day, and one card per branch
 * with cashiers, today's sales, in-progress count and waiting orders with
 * their durations. Quick links cover the sections the website sidebar links.
 */
export default function AdminDashboard() {
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<AdminDashboardPayload>(
    () => fetchAdminDashboard(),
    [],
  );

  if (loading) return <LoadingView label="Loading dashboard…" />;
  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }
  if (error && !data) return <ErrorView message={error} onRetry={reload} />;
  if (!data) return <EmptyView icon="grid-outline" title="No dashboard data" hint="Pull to refresh." />;

  const fin = data.today_financials ?? {};

  return (
    <View style={styles.root}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={COLORS.gold} />}
      >
        <View>
          <AdminMenuButton />
          <Text style={styles.eyebrow}>World Choice Perfumes</Text>
          <Text style={styles.h1}>Super Admin</Text>
        </View>

        {error ? <Banner kind="error" message={error} /> : null}

        {/* Headline figures — the website's stat cards, phone-sized. */}
        <StatGrid>
          <StatTile label="Today's Sales" value={money(data.today_total_revenue)} hint={`${data.total_sales_count} transactions`} />
          <StatTile label="Pending Orders" value={data.pending_orders} tone="warning" />
          <StatTile label="Approvals" value={data.pending_approvals} tone={data.pending_approvals > 0 ? 'danger' : 'gold'} />
          <StatTile label="Active Branches" value={data.active_branches} tone="success" />
          <StatTile label="Revenue" value={money(fin.revenue ?? 0)} />
          <StatTile label="Expenses" value={money(fin.expenses ?? 0)} tone="danger" />
          <StatTile label="Actual Sales" value={money(fin.actual_sales ?? 0)} tone="success" />
        </StatGrid>

        {/* Quick destinations (the sections behind the website sidebar). */}
        <View style={styles.quickRow}>
          <QuickLink icon="trending-up-outline" label="Daily Sales" onPress={() => router.push('/admin/daily-sales')} />
          <QuickLink icon="stats-chart-outline" label="Reports" onPress={() => router.push('/admin/reports')} />
          <QuickLink icon="storefront-outline" label="Branches" onPress={() => router.push('/admin/branches')} />
          <QuickLink icon="people-outline" label="Staff" onPress={() => router.push('/admin/staff')} />
        </View>

        <GroupLabel>Branches today</GroupLabel>
        {data.branches.length === 0 ? (
          <EmptyView icon="storefront-outline" title="No branches yet" hint="Create one from More → Branches." />
        ) : (
          data.branches.map((branch) => (
            <DataCard
              key={String(branch.id)}
              title={branch.name}
              badge={branch.is_active === false ? 'Inactive' : 'Active'}
              badgeTone={branch.is_active === false ? 'muted' : 'success'}
              lines={[
                `Sales today: ${money(branch.today_sales)}   ·   Cashiers: ${branch.cashiers_count}`,
                `Pending: ${branch.pending_count}   ·   In progress: ${branch.in_progress_count}`,
              ]}
            >
              {branch.pending_orders.length > 0 ? (
                <View style={styles.pendingBox}>
                  <Text style={styles.pendingTitle}>Waiting orders</Text>
                  {branch.pending_orders.slice(0, 5).map((o) => (
                    <View key={String(o.id)} style={styles.pendingRow}>
                      <Text style={styles.pendingOrder} numberOfLines={1}>
                        {o.order_number} · {o.customer_name}
                      </Text>
                      <Text style={styles.pendingAge}>{o.duration_label}</Text>
                    </View>
                  ))}
                  {branch.pending_orders.length > 5 ? (
                    <Text style={styles.pendingMore}>+{branch.pending_orders.length - 5} more</Text>
                  ) : null}
                </View>
              ) : null}
            </DataCard>
          ))
        )}
      </ScrollView>
    </View>
  );
}

function QuickLink({ icon, label, onPress }: { icon: keyof typeof Ionicons.glyphMap; label: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.quick, pressed && styles.pressed]} accessibilityRole="button">
      <Ionicons name={icon} size={19} color={COLORS.gold} />
      <Text style={styles.quickLabel}>{label}</Text>
    </Pressable>
  );
}

function money(v: number | string | null | undefined): string {
  const n = Number(v ?? 0);
  return 'TZS ' + n.toLocaleString('en-US', { maximumFractionDigits: 0 });
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  content: { padding: 16, paddingBottom: 40, gap: 12 },
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  eyebrow: {
    color: COLORS.goldDark,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 3,
    textTransform: 'uppercase',
  },
  h1: { color: COLORS.text, fontSize: 25, fontWeight: '800', marginTop: 4 },
  quickRow: { flexDirection: 'row', gap: 10 },
  quick: {
    flex: 1,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
    alignItems: 'center',
    gap: 6,
  },
  quickLabel: { color: COLORS.textSecondary, fontSize: 11.5, fontWeight: '700', textAlign: 'center' },
  pressed: { opacity: 0.75 },
  pendingBox: {
    backgroundColor: COLORS.bgRaised,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 10,
    gap: 6,
  },
  pendingTitle: {
    color: COLORS.textMuted,
    fontSize: 10.5,
    fontWeight: '700',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  pendingRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 10 },
  pendingOrder: { color: COLORS.textSecondary, fontSize: 13, flex: 1 },
  pendingAge: { color: COLORS.warning, fontSize: 12.5, fontWeight: '700' },
  pendingMore: { color: COLORS.textMuted, fontSize: 12 },
});
