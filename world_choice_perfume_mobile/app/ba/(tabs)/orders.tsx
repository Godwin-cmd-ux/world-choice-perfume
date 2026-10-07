import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, SearchInput, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchBaOrders, setBaOrderPersonalName, updateBaOrderStatus, type BaOrder } from '../../../lib/baApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { BA_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

const TABS = [
  { key: 'pending', label: 'Pending' },
  { key: 'progress', label: 'On Progress' },
  { key: 'completed', label: 'Completed' },
] as const;

/**
 * Orders — the supervisory twin of branch-admin/orders. Unlike every other
 * staff role, the tabs are NOT isolated: the whole branch's queue is listed
 * (isolated=false), oldest pending first, with waiting times, the pending
 * watch (how long the oldest order has waited, how many are late) and the
 * team activity board. Visibility is read-only though — picking and
 * serving still belong to whoever claimed the order, and the server
 * refuses a takeover, surfacing its message right here.
 */
export default function BaOrders() {
  const [tab, setTab] = useState<(typeof TABS)[number]['key']>('pending');
  const [q, setQ] = useState('');
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchBaOrders({ tab, q: q.trim() || undefined }),
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

  const move = async (order: BaOrder, next: 'picked' | 'served') => {
    setBusyKey(String(order.id));
    setActionError(null);
    try {
      await updateBaOrderStatus(order.id, next);
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
      await setBaOrderPersonalName(nameFor, nameDraft.trim());
      setNameFor(null);
      await reload();
    } catch (e) {
      setActionError(String((e as { message?: string }).message ?? e));
    } finally {
      setBusyKey(null);
    }
  };

  const itemsOf = (order: BaOrder): { name: string; quantity?: number }[] => {
    const raw = order.items;
    if (!Array.isArray(raw)) return [];

    return (raw as { name?: string; product?: { name?: string }; quantity?: number }[]).map((it) => ({
      name: it.name ?? it.product?.name ?? 'Item',
      quantity: it.quantity,
    }));
  };

  const watch = data?.pendingWatch as Record<string, unknown> | undefined;
  type TeamMember = { id?: string; name?: string; open?: number; served?: number; longest_open_minutes?: number | null };
  const teamRows = (Array.isArray(data?.team)
    ? ((data?.team as TeamMember[]) ?? [])
    : Object.values((data?.team as Record<string, TeamMember>) ?? {}));

  return (
    <AdminPage
      title="Order Queue"
      eyebrow={data?.scope?.branch_name ?? 'Branch Admin'}
      accent={BA_ACCENT.main}
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
        <Banner kind="connection" message="Watching every order at this branch. Picking and serving stays with whoever claimed the order." />
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

      <SearchInput value={q} onChangeText={setQ} placeholder="Search customer or order…" />

      {watch && tab === 'pending' ? (
        <>
          <GroupLabel>Pending watch</GroupLabel>
          <DataCard
            title={`${watch.count ?? 0} unclaimed`}
            subtitle={`Oldest waiting ${watch.longest_label ?? '—'}`}
            badge={watch.late_count ? `${watch.late_count} late` : 'on time'}
            badgeTone={watch.late_count ? 'warning' : 'success'}
          />
        </>
      ) : null}

      {teamRows.length > 0 ? (
        <>
          <GroupLabel>Team activity</GroupLabel>
          {teamRows.slice(0, 5).map((member, i) => (
            <DataCard
              key={String(member.id ?? i)}
              title={String(member.name ?? 'Staff')}
              badge={member.open ? `${member.open} open` : 'idle'}
              badgeTone={member.open ? 'warning' : 'muted'}
              lines={[
                `${member.served ?? 0} served today`,
                member.longest_open_minutes != null ? `Longest open: ${humanMinutes(Number(member.longest_open_minutes))}` : null,
              ].filter(Boolean) as string[]}
            />
          ))}
        </>
      ) : null}

      {loading && !data ? (
        <LoadingView label="Loading orders…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.orders.length === 0 ? (
        <EmptyView icon="clipboard-outline" title="No orders here" hint="The pending queue fills up as customers order." />
      ) : (
        data.orders.map((order) => {
          const key = String(order.id);
          const mine = (order.assigned_to ?? order.picked_by ?? null) != null && String(order.assigned_to ?? order.picked_by) === String(data.userId);
          const label = (order.personal_order_name as string | null) ?? null;
          const picker = order.assigned_to ?? order.picked_by ? data.pickers[String(order.assigned_to ?? order.picked_by)] : null;
          const waiting = order.waiting_label ? `  ·  waiting ${String(order.waiting_label)}` : '';
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
                {waiting}
                {mine ? '  ·  yours' : ''}
                {picker ? `  ·  picked by ${picker}` : ''}
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
                ) : tab === 'progress' && mine ? (
                  <ActionBtn
                    label={busyKey === key ? '…' : 'Mark Served'}
                    tone="success"
                    disabled={busyKey === key}
                    onPress={() => move(order, 'served')}
                  />
                ) : tab === 'progress' ? (
                  <Text style={styles.watchNote}>{"In a colleague's hands"}</Text>
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

function humanMinutes(minutes: number): string {
  if (!Number.isFinite(minutes) || minutes < 1) return 'just now';
  if (minutes < 60) return `${minutes}m`;
  if (minutes < 1440) return `${Math.floor(minutes / 60)}h ${minutes % 60}m`;
  return `${Math.floor(minutes / 1440)}d ${Math.floor((minutes % 1440) / 60)}h`;
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
  const bg = tone === 'success' ? 'rgba(52, 211, 153, 0.14)' : tone === 'primary' ? BA_ACCENT.soft : 'transparent';
  const border = tone === 'success' ? 'rgba(52, 211, 153, 0.35)' : tone === 'primary' ? BA_ACCENT.border : COLORS.border;
  const fg = tone === 'success' ? COLORS.success : BA_ACCENT.light;
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
  cardTotal: { color: BA_ACCENT.light, fontWeight: '800', fontSize: 14 },
  cardMeta: { color: COLORS.textMuted, fontSize: 11, marginTop: 4 },
  cardItems: { color: COLORS.textSecondary, fontSize: 12, marginTop: 6 },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10, alignItems: 'center' },
  action: { borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  actionText: { fontWeight: '800', fontSize: 12 },
  watchNote: { color: COLORS.textMuted, fontSize: 11.5, fontStyle: 'italic' },
  nameRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 10, marginTop: 6 },
});
