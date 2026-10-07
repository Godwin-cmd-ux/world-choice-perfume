import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, SearchInput, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchOrders, setOrderPersonalName, updateOrderStatus, type CcOrder } from '../../../lib/careApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

const TABS = [
  { key: 'pending', label: 'Pending' },
  { key: 'picked', label: 'Picked' },
  { key: 'served', label: 'Served' },
] as const;

/**
 * Orders — the website's three customer-care tabs: the pending queue
 * anyone claims, plus picked/served orders only the owner sees. Picking and
 * serving use the same OrderWorkflowService transitions the website does
 * (pending → picked → served, never backwards).
 */
export default function CareOrders() {
  const [tab, setTab] = useState<(typeof TABS)[number]['key']>('pending');
  const [q, setQ] = useState('');
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchOrders({ tab, q: q.trim() || undefined }),
    [tab, q],
  );

  const [nameFor, setNameFor] = useState<string | null>(null);
  const [nameDraft, setNameDraft] = useState('');

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const move = async (order: CcOrder, next: 'picked' | 'served') => {
    setBusyKey(String(order.id));
    setActionError(null);
    try {
      await updateOrderStatus(order.id, next);
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
      await setOrderPersonalName(nameFor, nameDraft.trim());
      setNameFor(null);
      await reload();
    } catch (e) {
      setActionError(String((e as { message?: string }).message ?? e));
    } finally {
      setBusyKey(null);
    }
  };

  const itemsOf = (order: CcOrder): { name: string; quantity?: number }[] => {
    const raw = order.items;
    if (!Array.isArray(raw)) return [];

    return (raw as { name?: string; product?: { name?: string }; quantity?: number }[]).map((it) => ({
      name: it.name ?? it.product?.name ?? 'Item',
      quantity: it.quantity,
    }));
  };

  return (
    <AdminPage
      title="Orders"
      eyebrow="Customer Care"
      accent={CC_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {actionError ? <Banner kind="error" message={actionError} /> : null}

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

      <SearchInput value={q} onChangeText={setQ} placeholder="Search customer or order…" />

      {loading && !data ? (
        <LoadingView label="Loading orders…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.orders.length === 0 ? (
        <EmptyView icon="clipboard-outline" title="No orders here" hint="The pending queue fills up as customers order." />
      ) : (
        data.orders.map((order) => {
          const key = String(order.id);
          const mine = (order.picked_by ?? null) != null && String(order.picked_by) === String(data.userId);
          const label = (order.personal_order_name as string | null) ?? null;
          return (
            <View key={key} style={styles.card}>
              <View style={styles.cardHead}>
                <Text style={styles.cardTitle} numberOfLines={1}>
                  {label || (order.customer_name as string) || `Order #${order.id}`}
                </Text>
                <Text style={styles.cardTotal}>{formatMoney((order.total as number) ?? 0)}</Text>
              </View>
              <Text style={styles.cardMeta}>
                {formatDateTime(order.created_at as string | null)}
                {order.status ? `  ·  ${String(order.status)}` : ''}
                {mine ? '  ·  yours' : ''}
                {data.pickers[key] ? `  ·  picked by ${data.pickers[key]}` : ''}
              </Text>
              {itemsOf(order).length > 0 ? (
                <Text style={styles.cardItems} numberOfLines={2}>
                  {itemsOf(order).map((it) => `${it.quantity ?? 1}× ${it.name}`).join(', ')}
                </Text>
              ) : null}

              <View style={styles.actions}>
                {tab === 'pending' ? (
                  <ActionBtn
                    label={busyKey === key ? '…' : 'Mark Picked'}
                    tone="primary"
                    disabled={busyKey === key}
                    onPress={() => move(order, 'picked')}
                  />
                ) : tab === 'picked' && mine ? (
                  <ActionBtn
                    label={busyKey === key ? '…' : 'Mark Served'}
                    tone="success"
                    disabled={busyKey === key}
                    onPress={() => move(order, 'served')}
                  />
                ) : null}
                <ActionBtn
                  label="Personal Name"
                  tone="ghost"
                  onPress={() => {
                    setNameDraft(label ?? '');
                    setNameFor(key);
                  }}
                />
              </View>

              {nameFor === key ? (
                <View style={styles.nameRow}>
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
  const bg = tone === 'success' ? 'rgba(52, 211, 153, 0.14)' : tone === 'primary' ? CC_ACCENT.soft : 'transparent';
  const border = tone === 'success' ? 'rgba(52, 211, 153, 0.35)' : tone === 'primary' ? CC_ACCENT.border : COLORS.border;
  const fg = tone === 'success' ? COLORS.success : CC_ACCENT.light;
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
  cardTotal: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 14 },
  cardMeta: { color: COLORS.textMuted, fontSize: 11, marginTop: 4 },
  cardItems: { color: COLORS.textSecondary, fontSize: 12, marginTop: 6 },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10 },
  action: { borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  actionText: { fontWeight: '800', fontSize: 12 },
  nameRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 10, marginTop: 6 },
});
