import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchCcDashboard } from '../../../lib/careApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * Dashboard — the website's customer-care.dashboard: today's money, order
 * queue and client figures for the member's branch. At Head Quarters the
 * two extra tiles (unread inquiries, unread mails) appear, mirroring the
 * website's $isHq block; everyone else never sees them.
 */
export default function CareDashboard() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchCcDashboard(), []);

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const scope = data?.scope;
  const isHq = data?.isHq ?? false;

  const daily = (data?.dailySummary ?? {}) as Record<string, unknown>;
  const dailyLines = Object.entries(daily)
    .filter(([, v]) => typeof v === 'number')
    .slice(0, 3)
    .map(([k, v]) => `${k.replace(/_/g, ' ')}: ${formatMoney(v as number)}`);

  return (
    <AdminPage
      title="Customer Care"
      eyebrow={isHq ? 'Head Quarters-Mikocheni' : scope?.branch_name ?? 'Customer Care'}
      accent={CC_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {!error && !loading && !isHq ? (
        <Banner kind="success" message={`${scope?.branch_name ?? 'Your branch'} — clients, sales and orders for this branch.`} />
      ) : null}
      {!error && !loading && isHq ? (
        <Banner kind="success" message="Head Quarters access — Inquiries, News and the info@ mailbox are under More." />
      ) : null}

      {loading && !data ? (
        <LoadingView label="Loading dashboard…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : data ? (
        <>
          <StatGrid>
            <StatTile label="Today's Revenue" value={formatMoney(data.todayRevenue)} tone="gold" />
            <StatTile label="Sales" value={data.totalSalesCount} tone="success" />
            <StatTile label="Pending Orders" value={data.pendingOrders} tone={data.pendingOrders > 0 ? 'warning' : 'info'} />
            <StatTile label="Clients" value={data.clientsTotal} tone="info" />
            {isHq ? (
              <>
                <StatTile label="Unread Inquiries" value={data.unreadInquiries} tone={data.unreadInquiries > 0 ? 'danger' : 'success'} />
                <StatTile label="Unread Mails" value={data.unreadMails} tone={data.unreadMails > 0 ? 'danger' : 'success'} />
              </>
            ) : null}
          </StatGrid>

          <GroupLabel right={<Text style={styles.muted}>{formatMoney(data.totalRevenue)} total</Text>}>Quick actions</GroupLabel>
          <View style={styles.quickRow}>
            <QuickAction icon="cart-outline" label="New Sale" onPress={() => router.push('/care/sale-new')} />
            <QuickAction icon="person-add-outline" label="New Client" onPress={() => router.push('/care/customer-new')} />
            <QuickAction icon="swap-horizontal-outline" label="Orders" onPress={() => router.push('/care/(tabs)/orders')} />
            {isHq ? (
              <QuickAction icon="mail-outline" label="info@ Mail" onPress={() => router.push('/care/mails')} />
            ) : null}
          </View>

          {dailyLines.length > 0 ? (
            <>
              <GroupLabel>Branch today</GroupLabel>
              <DataCard title="Daily summary" lines={dailyLines} />
            </>
          ) : null}

          {isHq ? (
            <>
              <GroupLabel>Inquiries (HQ)</GroupLabel>
              {data.recentInquiries.length === 0 ? (
                <DataCard title="No inquiries yet" subtitle="Newest customer messages appear here" />
              ) : (
                data.recentInquiries.slice(0, 3).map((inq) => (
                  <DataCard
                    key={String(inq.id)}
                    title={inq.subject || inq.message?.slice(0, 60) || 'Inquiry'}
                    subtitle={inq.user?.name ?? 'Customer'}
                    badge={inq.is_read ? (inq.status ?? 'pending') : 'unread'}
                    badgeTone={inq.is_read ? 'muted' : 'danger'}
                    onPress={() => router.push('/care/inquiries')}
                  />
                ))
              )}
            </>
          ) : null}

          <GroupLabel right={<Text style={styles.muted}>{data.ordersTotal ? formatMoney(data.ordersTotal) : ''}</Text>}>Order queue</GroupLabel>
          <DataCard
            title={`${data.pendingOrders} pending`}
            subtitle="Tap to pick, serve and name orders"
            lines={Object.entries(data.orderStatusCounts).map(([k, v]) => `${k}: ${v}`)}
            onPress={() => router.push('/care/(tabs)/orders')}
          />

          <GroupLabel right={<Text style={styles.muted}>{formatMoney(data.totalRevenue)}</Text>}>Recent sales</GroupLabel>
          {data.sales.length === 0 ? (
            <EmptyView icon="cart-outline" title="No sales yet" hint="New sales appear here the moment they are rung up." />
          ) : (
            data.sales.slice(0, 5).map((sale) => (
              <DataCard
                key={String(sale.id)}
                title={sale.sale_number ?? `Sale #${sale.id}`}
                subtitle={sale.customer?.name ?? 'Walk-in customer'}
                badge={formatMoney(sale.total ?? 0)}
                badgeTone="success"
                lines={[formatDateTime(sale.created_at), sale.payment_summary ?? null]}
                onPress={() => router.push({ pathname: '/care/sale-detail', params: { id: String(sale.id) } })}
              />
            ))
          )}
        </>
      ) : null}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

function QuickAction({ icon, label, onPress }: { icon: keyof typeof Ionicons.glyphMap; label: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.quick, pressed && { opacity: 0.7 }]} accessibilityRole="button">
      <View style={styles.quickIcon}>
        <Ionicons name={icon} size={20} color={CC_ACCENT.light} />
      </View>
      <Text style={styles.quickLabel} numberOfLines={1}>
        {label}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  muted: { color: COLORS.textMuted, fontSize: 11 },
  quickRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 6 },
  quick: {
    width: '48%',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.bgRaised,
    borderColor: CC_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
    paddingHorizontal: 12,
  },
  quickIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: CC_ACCENT.soft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickLabel: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 13, flexShrink: 1 },
});
