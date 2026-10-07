import { router } from 'expo-router';
import { useSyncExternalStore, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, SearchInput, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { cashierMonitor } from '../../../lib/cashierMonitor';
import {
  fetchCashierOrders,
  pickCashierOrder,
  serveCashierOrder,
  setCashierOrderPersonalName,
  type CashierOrder,
} from '../../../lib/cashierApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CASHIER_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

const TABS = [
  { key: 'pending', label: 'Pending' },
  { key: 'progress', label: 'On Progress' },
  { key: 'completed', label: 'Completed' },
] as const;

/**
 * Orders — the isolated twin of cashier/orders. Pending is the shared queue
 * anyone at the branch can claim; picked and completed tabs only ever show
 * the orders this cashier claimed. Pick and serve are dedicated actions that
 * REQUIRE a note — the website validates the same rule — and serve records
 * the sale and deducts stock server-side. While an HQ monitor watches
 * another branch the queue is read-only.
 */
export default function CashierOrders() {
  const monitor = useSyncExternalStore(cashierMonitor.subscribe, cashierMonitor.get, cashierMonitor.get);

  const [tab, setTab] = useState<(typeof TABS)[number]['key']>('pending');
  const [q, setQ] = useState('');
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchCashierOrders({ tab, q: q.trim() || undefined, monitor_branch: monitor?.id }),
    [tab, q, monitor?.id],
  );

  // The note row: which order is acting, and what kind of action.
  const [actFor, setActFor] = useState<string | null>(null);
  const [actKind, setActKind] = useState<'picked' | 'served'>('picked');
  const [noteDraft, setNoteDraft] = useState('');

  const [nameFor, setNameFor] = useState<string | null>(null);
  const [nameDraft, setNameDraft] = useState('');

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const monitoring = Boolean(data?.scope?.in_cross_branch);

  const confirmAct = async (order: CashierOrder) => {
    if (!actFor || noteDraft.trim() === '') return;
    setBusyKey(`act-${actFor}`);
    setActionError(null);
    try {
      if (actKind === 'picked') {
        await pickCashierOrder(order.id, noteDraft.trim());
      } else {
        await serveCashierOrder(order.id, noteDraft.trim());
      }
      setActFor(null);
      setNoteDraft('');
      await reload();
    } catch (e) {
      setActionError(String((e as { message?: string }).message ?? e));
    } finally {
      setBusyKey(null);
    }
  };

  const saveName = async () => {
    if (!nameFor) return;
    setBusyKey(`name-${nameFor}`);
    setActionError(null);
    try {
      await setCashierOrderPersonalName(nameFor, nameDraft.trim());
      setNameFor(null);
      await reload();
    } catch (e) {
      setActionError(String((e as { message?: string }).message ?? e));
    } finally {
      setBusyKey(null);
    }
  };

  const itemsOf = (order: CashierOrder): { name: string; quantity?: number }[] => {
    const raw = order.items;
    if (!Array.isArray(raw)) return [];

    return (raw as { name?: string; product?: { name?: string }; quantity?: number }[]).map((it) => ({
      name: it.name ?? it.product?.name ?? 'Item',
      quantity: it.quantity,
    }));
  };

  return (
    <AdminPage
      title="Order Queue"
      eyebrow={data?.scope?.branch_name ?? 'Cashier'}
      accent={CASHIER_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {actionError ? <Banner kind="error" message={actionError} /> : null}
      {!error && !loading ? (
        monitoring ? (
          <Banner kind="connection" message={`Monitoring ${data?.scope?.branch_name ?? 'another branch'} — the queue is read-only. Exit the branch to pick or serve.`} />
        ) : (
          <Banner kind="connection" message="Pending is shared with your branch. Every pick and serve needs a note, and serving records the sale." />
        )
      ) : null}

      <ChipRow>
        {TABS.map((t) => (
          <Chip
            key={t.key}
            label={`${t.label}${data ? ` (${data.counts[t.key] ?? 0})` : ''}`}
            active={tab === t.key}
            onPress={() => setTab(t.key)}
          />
        ))}
      </ChipRow>

      <SearchInput value={q} onChangeText={setQ} placeholder="Search order number or name…" />

      {loading && !data ? (
        <LoadingView label="Loading orders…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.orders.length === 0 ? (
        <EmptyView
          icon="clipboard-outline"
          title={tab === 'pending' ? 'No orders waiting' : 'Nothing here yet'}
          hint={tab === 'pending' ? 'The queue fills up as customers order.' : 'Orders you pick appear here.'}
        />
      ) : (
        data.orders.map((order) => {
          const key = String(order.id);
          const mine = (order.assigned_to ?? order.picked_by ?? null) != null && String(order.assigned_to ?? order.picked_by) === String(data.userId);
          const label = (order.personal_order_name as string | null) ?? null;
          const picker = order.assigned_to ?? order.picked_by ? data.pickers[String(order.assigned_to ?? order.picked_by)] : null;
          return (
            <View key={key} style={styles.card}>
              <View style={styles.cardHead}>
                <Text style={styles.cardTitle} numberOfLines={1}>
                  {label || (order.customer_name as string) || (order.order_number as string) || `Order #${order.id}`}
                </Text>
                <Text style={styles.cardTotal}>{formatMoney((order.total as number) ?? 0)}</Text>
              </View>
              <Text style={styles.cardMeta}>
                {formatDateTime(order.created_at as string | null)}
                {order.status ? `  ·  ${String(order.status)}` : ''}
                {mine ? '  ·  yours' : ''}
                {picker ? `  ·  picked by ${picker}` : ''}
              </Text>
              {itemsOf(order).length > 0 ? (
                <Text style={styles.cardItems} numberOfLines={2}>
                  {itemsOf(order).map((it) => `${it.quantity ?? 1}× ${it.name}`).join(', ')}
                </Text>
              ) : null}

              <View style={styles.actions}>
                {!monitoring && tab === 'pending' ? (
                  <ActionBtn
                    label={busyKey === `act-${key}` ? '…' : 'Mark Picked'}
                    tone="primary"
                    onPress={() => {
                      setActFor(key);
                      setActKind('picked');
                      setNoteDraft('Picked up');
                      setNameFor(null);
                    }}
                  />
                ) : !monitoring && tab === 'progress' && mine ? (
                  <ActionBtn
                    label={busyKey === `act-${key}` ? '…' : 'Mark Served'}
                    tone="success"
                    onPress={() => {
                      setActFor(key);
                      setActKind('served');
                      setNoteDraft('Served');
                      setNameFor(null);
                    }}
                  />
                ) : null}
                {!monitoring ? (
                  <ActionBtn
                    label="Personal Name"
                    tone="ghost"
                    onPress={() => {
                      setNameDraft(label ?? '');
                      setNameFor(key);
                      setActFor(null);
                    }}
                  />
                ) : null}
              </View>

              {actFor === key ? (
                <View style={styles.noteRow}>
                  <View style={{ flex: 1 }}>
                    <AuthField
                      label={`${actKind === 'picked' ? 'Pick' : 'Serve'} note (required)`}
                      value={noteDraft}
                      onChangeText={setNoteDraft}
                      placeholder={actKind === 'picked' ? 'Picked up' : 'Served'}
                      icon="document-text-outline"
                      maxLength={2000}
                    />
                  </View>
                  <ActionBtn
                    label={busyKey === `act-${key}` ? '…' : 'Confirm'}
                    tone="primary"
                    disabled={busyKey === `act-${key}` || noteDraft.trim() === ''}
                    onPress={() => confirmAct(order)}
                  />
                </View>
              ) : null}

              {nameFor === key ? (
                <View style={styles.noteRow}>
                  <View style={{ flex: 1 }}>
                    <AuthField
                      label="Personal name for this order"
                      value={nameDraft}
                      onChangeText={setNameDraft}
                      placeholder="e.g. Mama Nasia"
                      icon="pricetag-outline"
                      autoCapitalize="words"
                      maxLength={120}
                    />
                  </View>
                  <ActionBtn label={busyKey === `name-${key}` ? '…' : 'Save'} tone="primary" disabled={busyKey === `name-${key}`} onPress={saveName} />
                </View>
              ) : null}
            </View>
          );
        })
      )}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

function ActionBtn({
  label,
  onPress,
  tone,
  disabled = false,
}: {
  label: string;
  onPress: () => void;
  tone: 'primary' | 'success' | 'ghost';
  disabled?: boolean;
}) {
  const bg = tone === 'success' ? 'rgba(52, 211, 153, 0.14)' : tone === 'primary' ? CASHIER_ACCENT.soft : 'transparent';
  const border = tone === 'success' ? 'rgba(52, 211, 153, 0.35)' : tone === 'primary' ? CASHIER_ACCENT.border : COLORS.border;
  const fg = tone === 'success' ? COLORS.success : CASHIER_ACCENT.light;
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={({ pressed }) => [styles.action, { backgroundColor: bg, borderColor: border }, pressed && { opacity: 0.7 }, disabled && { opacity: 0.5 }]}
      accessibilityRole="button"
    >
      <Text style={[styles.actionText, { color: tone === 'ghost' ? COLORS.textSecondary : fg }]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.bgRaised,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
    marginBottom: 10,
  },
  cardHead: { flexDirection: 'row', justifyContent: 'space-between', gap: 10 },
  cardTitle: { color: COLORS.textSecondary, fontWeight: '800', fontSize: 14, flexShrink: 1 },
  cardTotal: { color: CASHIER_ACCENT.light, fontWeight: '800', fontSize: 14 },
  cardMeta: { color: COLORS.textMuted, fontSize: 11, marginTop: 4 },
  cardItems: { color: COLORS.textSecondary, fontSize: 12, marginTop: 6 },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10, alignItems: 'center' },
  action: { borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  actionText: { fontWeight: '800', fontSize: 12 },
  noteRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 10, marginTop: 6 },
});
