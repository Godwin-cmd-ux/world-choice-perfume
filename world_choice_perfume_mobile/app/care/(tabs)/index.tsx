import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, KV, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { careMenu } from '../../../components/caresidebar';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchCcDashboard, type CcSale } from '../../../lib/careApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * Dashboard — the website's customer-care.dashboard, section for section:
 * the pending-order banner, the summary cards (today's money, sales, orders
 * and clients with their side figures, plus the two Head Quarters cards),
 * today's money strip, then the Sales / Orders / Clients panels and — at
 * Head Quarters — the mailbox statistics, recent inquiries and recent
 * mails. Quick actions stay because a phone has no sidebar to jump from.
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

  const mail = (data?.mailStats ?? {}) as Record<string, unknown>;
  const num = (key: string): number => (typeof mail[key] === 'number' ? (mail[key] as number) : 0);
  const orders = (data?.orders ?? []) as Record<string, unknown>[];
  const clients = (data?.clients ?? []) as { id?: number | string; name?: string | null; phone?: string | null; whatsapp?: string | null; email?: string | null; created_at?: string | null }[];

  const itemsLabel = (items: unknown): string => {
    if (!Array.isArray(items) || items.length === 0) return 'No items';
    return `${items.length} item${items.length === 1 ? '' : 's'}`;
  };

  const saleItems = (sale: CcSale): string => {
    const names = ((sale.items ?? []) as { product?: { name?: string } }[])
      .map((it) => it.product?.name)
      .filter((n): n is string => Boolean(n));
    if (names.length === 0) return '—';
    const extra = names.length - 3;
    return `${names.slice(0, 3).join(', ')}${extra > 0 ? ` +${extra} more` : ''}`;
  };

  const purchasesOf = (clientId: number | string): number =>
    (data?.sales ?? []).filter((s) => String(s.customer?.id ?? '') === String(clientId)).length;

  const statusTone = (status: string) =>
    status === 'served' ? 'success' : status === 'picked' ? 'gold' : 'warning';

  return (
    <AdminPage
      title="Customer Care"
      onMenu={careMenu.open}
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
          {data.pendingOrders > 0 ? (
            <Pressable
              onPress={() => router.push('/care/(tabs)/orders')}
              style={({ pressed }) => [styles.pending, pressed && { opacity: 0.75 }]}
              accessibilityRole="button"
            >
              <View style={styles.pendingIcon}>
                <Ionicons name="time-outline" size={20} color={COLORS.warning} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.pendingTitle}>Pending Orders</Text>
                <Text style={styles.pendingBody}>
                  {data.pendingOrders} order(s) waiting — go to orders to process them
                </Text>
              </View>
              <Text style={styles.pendingCount}>{data.pendingOrders}</Text>
            </Pressable>
          ) : null}

          <StatGrid>
            <StatTile label="Today's Revenue" value={formatMoney(data.todayRevenue)} tone="gold" />
            <StatTile label="Total Sales" value={data.totalSalesCount} hint={formatMoney(data.totalRevenue)} tone="success" />
            <StatTile label="Total Orders" value={orders.length} hint={formatMoney(data.ordersTotal)} tone="info" />
            <StatTile label="Pending Orders" value={data.pendingOrders} tone={data.pendingOrders > 0 ? 'warning' : 'info'} />
            <StatTile label="Total Clients" value={data.clientsTotal} hint={`${data.clientsWithPhone} with phone`} tone="info" />
            {isHq ? (
              <>
                <StatTile
                  label="Total Inquiries"
                  value={num('inquiries') || data.inquiriesCount || 0}
                  hint={`${data.unreadInquiries} unread`}
                  tone={data.unreadInquiries > 0 ? 'danger' : 'success'}
                />
                <StatTile
                  label="Mails Received"
                  value={num('totalReceived')}
                  hint={`${data.unreadMails} unread`}
                  tone={data.unreadMails > 0 ? 'danger' : 'success'}
                />
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

          <GroupLabel right={<Text style={styles.muted}>{formatMoney(data.totalRevenue)}</Text>}>Sales</GroupLabel>
          {data.sales.length === 0 ? (
            <EmptyView icon="cart-outline" title="No sales yet" hint="New sales appear here the moment they are rung up." />
          ) : (
            data.sales.slice(0, 5).map((sale) => {
              const paid = sale.payment_status ?? 'pending';
              return (
                <DataCard
                  key={String(sale.id)}
                  title={sale.sale_number ?? `Sale #${sale.id}`}
                  subtitle={[sale.customer?.name ?? '—', sale.cashier?.name ?? '—'].join(' · ')}
                  badge={paid}
                  badgeTone={paid === 'paid' ? 'success' : paid === 'cancelled' ? 'danger' : 'warning'}
                  lines={[saleItems(sale), formatDateTime(sale.created_at)]}
                  onPress={() => router.push({ pathname: '/care/sale-detail', params: { id: String(sale.id) } })}
                />
              );
            })
          )}

          <GroupLabel right={<Text style={styles.muted}>{formatMoney(data.ordersTotal)}</Text>}>Orders</GroupLabel>
          {orders.length === 0 ? (
            <DataCard title="No orders yet" subtitle="Customer orders land here" />
          ) : (
            orders.slice(0, 5).map((o) => {
              const id = String(o.id ?? '');
              const status = String(o.status ?? 'pending');
              return (
                <DataCard
                  key={id}
                  title={String(o.order_number ?? `Order #${id}`)}
                  subtitle={String((o.customer as { name?: string } | undefined)?.name ?? '—')}
                  badge={status}
                  badgeTone={statusTone(status)}
                  lines={[
                    `${formatMoney(Number(o.total ?? 0))} · ${itemsLabel(o.items)}`,
                    formatDateTime(o.created_at as string | null),
                  ]}
                  onPress={() => router.push({ pathname: '/care/order-detail', params: { id } })}
                />
              );
            })
          )}

          <GroupLabel right={<Text style={styles.muted}>{data.clientsWithPhone} with phone</Text>}>Clients</GroupLabel>
          {clients.length === 0 ? (
            <DataCard title="No clients yet" subtitle="New clients appear here" />
          ) : (
            clients.slice(0, 5).map((c) => (
              <DataCard
                key={String(c.id)}
                title={c.name ?? 'Unnamed'}
                subtitle={[c.phone, c.whatsapp].filter(Boolean).join(' · ') || 'No phone'}
                badge={`${purchasesOf(c.id ?? '')} sales`}
                badgeTone="gold"
                lines={[c.email ?? null, c.created_at ? `Registered ${formatDateTime(c.created_at)}` : null]}
                onPress={() => router.push({ pathname: '/care/customer-detail', params: { id: String(c.id) } })}
              />
            ))
          )}

          {isHq ? (
            <>
              <GroupLabel right={<Text style={styles.muted}>Open mails</Text>}>Email statistics</GroupLabel>
              <DataCard title="info@worldchoiceperfume.com" subtitle="Last 30 days">
                <View>
                  <KV label="Received" value={num('received')} />
                  <KV label="Awaiting answer" value={num('awaiting')} tone={num('awaiting') > 0 ? 'warning' : undefined} />
                  <KV label="Answered" value={num('answered')} tone="success" />
                  <KV label="With attachments" value={num('withAttachments')} />
                  <KV label="Replies sent" value={num('repliesSent')} />
                  <KV label="Replies failed" value={num('repliesFailed')} tone={num('repliesFailed') > 0 ? 'danger' : undefined} />
                  <KV label="Average first reply" value={String(mail.firstReplyLabel ?? 'No answers yet')} />
                </View>
              </DataCard>

              <GroupLabel right={<Text style={styles.muted}>View all</Text>}>Recent mails</GroupLabel>
              {data.recentMails.length === 0 ? (
                <DataCard title="No mail received yet" />
              ) : (
                data.recentMails.slice(0, 5).map((m) => (
                  <DataCard
                    key={String(m.id)}
                    title={m.subject || '(no subject)'}
                    subtitle={m.from_name || m.from_email || null}
                    badge={m.status === 'replied' ? 'Replied' : m.status === 'closed' ? 'Closed' : 'New'}
                    badgeTone={m.status === 'replied' ? 'success' : m.status === 'closed' ? 'muted' : 'danger'}
                    lines={[formatDateTime(m.received_at ?? m.created_at)]}
                    onPress={() => router.push({ pathname: '/care/mail-detail', params: { id: String(m.id) } })}
                  />
                ))
              )}
            </>
          ) : null}
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
  pending: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: 'rgba(252, 211, 77, 0.10)',
    borderColor: 'rgba(252, 211, 77, 0.35)',
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
  },
  pendingIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(252, 211, 77, 0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  pendingTitle: { color: COLORS.text, fontWeight: '800', fontSize: 14 },
  pendingBody: { color: COLORS.textMuted, fontSize: 12, marginTop: 2 },
  pendingCount: { color: COLORS.warning, fontWeight: '800', fontSize: 18 },
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
