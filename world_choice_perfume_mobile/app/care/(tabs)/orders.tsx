import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { AuthField, Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, SearchInput, useAsyncData } from '../../../components/adminkit';
import { careMenu } from '../../../components/caresidebar';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchOrders, setOrderPersonalName, updateOrderStatus, type CcOrder } from '../../../lib/careApi';
import { formatDateTime, formatMoney } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * The three tabs of the website's customer-care Orders page, with the very
 * slugs and labels OrderWorkflowService defines (pending / progress /
 * completed) — the server maps them to the pending, picked and served
 * statuses, so any other slug would silently fall back to the pending
 * queue. Pending is the shared branch queue anyone can claim; progress and
 * completed only ever contain this member's own orders.
 *
 * Every status change carries a required note (the website's
 * requireNote prompt), and the personal name is only offered on orders this
 * member picked, exactly like $canName in the blade. Tapping a row opens
 * the order record (customer-care/orders/{id}).
 */
const TABS = [
  { key: 'pending', label: 'Pending Orders' },
  { key: 'progress', label: 'My Orders On Progress' },
  { key: 'completed', label: 'My Completed Orders' },
] as const;

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
  const [noteFor, setNoteFor] = useState<string | null>(null);
  const [noteNext, setNoteNext] = useState<'picked' | 'served' | null>(null);
  const [noteDraft, setNoteDraft] = useState('');

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const askNote = (key: string, next: 'picked' | 'served') => {
    setNameFor(null);
    setNoteFor(key);
    setNoteNext(next);
    setNoteDraft('');
    setActionError(null);
  };

  const confirmNote = async () => {
    if (!noteFor || !noteNext) return;
    const note = noteDraft.trim();
    if (!note) {
      setActionError('Enter a note about this order update (what point you have reached) before changing the status.');
      return;
    }
    setBusyKey(noteFor);
    setActionError(null);
    try {
      await updateOrderStatus(noteFor, noteNext, note);
      setNoteFor(null);
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

  /** assigned_to is the picker column; cashier_id is the legacy one. */
  const isMine = (order: CcOrder) => {
    if (!data) return false;
    const who = String(order.assigned_to ?? order.cashier_id ?? '');
    return who !== '' && who === String(data.userId);
  };

  const emptyHint = q.trim()
    ? 'No orders match your search.'
    : tab === 'pending'
      ? 'No orders waiting to be picked.'
      : tab === 'progress'
        ? 'You have no orders in progress.'
        : 'You have not completed any orders yet.';

  return (
    <AdminPage
      title="Orders"
      onMenu={careMenu.open}
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

      <SearchInput value={q} onChangeText={setQ} placeholder="Search order number or personal name…" />

      {loading && !data ? (
        <LoadingView label="Loading orders…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.orders.length === 0 ? (
        <EmptyView icon="clipboard-outline" title="No orders here" hint={emptyHint} />
      ) : (
        data.orders.map((order) => {
          const key = String(order.id);
          const mine = isMine(order);
          const status = String(order.status ?? 'pending');
          const label = (order.personal_order_name as string | null) ?? null;
          // $canName in the blade: only the picker may name an order they
          // have already picked (or served).
          const canName = mine && (status === 'picked' || status === 'served');
          const next: 'picked' | 'served' | null =
            status === 'pending' ? 'picked' : status === 'picked' && mine ? 'served' : null;
          const customer = order.customer?.name ?? (order.customer_name as string | null) ?? null;

          return (
            <Pressable
              key={key}
              onPress={() => router.push({ pathname: '/care/order-detail', params: { id: key } })}
              style={({ pressed }) => [styles.card, pressed && { opacity: 0.78 }]}
              accessibilityRole="button"
              accessibilityLabel={`Open order ${String(order.order_number ?? key)}`}
            >
              <View style={styles.cardHead}>
                <Text style={styles.cardTitle} numberOfLines={1}>
                  {String(order.order_number ?? `Order #${key}`)}
                </Text>
                <Text style={styles.cardTotal}>{formatMoney((order.total as number) ?? 0)}</Text>
              </View>
              <Text style={styles.cardMeta}>
                {label ?? customer ?? 'No customer'}
                {`  ·  ${status}`}
                {mine ? '  ·  yours' : ''}
              </Text>
              <Text style={styles.cardMeta}>
                {formatDateTime(order.created_at as string | null)}
                {data.pickers[key] ? `  ·  picked by ${data.pickers[key]}` : ''}
              </Text>
              {itemsOf(order).length > 0 ? (
                <Text style={styles.cardItems} numberOfLines={2}>
                  {itemsOf(order).map((it) => `${it.quantity ?? 1}× ${it.name}`).join(', ')}
                </Text>
              ) : null}

              <View style={styles.actions}>
                {next ? (
                  <ActionBtn
                    label={busyKey === key ? '…' : next === 'picked' ? 'Pick' : 'Serve'}
                    tone={next === 'picked' ? 'primary' : 'success'}
                    disabled={busyKey === key}
                    onPress={() => askNote(key, next)}
                  />
                ) : null}
                {canName ? (
                  <ActionBtn
                    label="Personal Name"
                    tone="ghost"
                    onPress={() => {
                      setNoteFor(null);
                      setNameDraft(label ?? '');
                      setNameFor(key);
                    }}
                  />
                ) : null}
              </View>

              {noteFor === key ? (
                <View style={styles.noteWrap}>
                  <Text style={styles.noteHint}>
                    Enter what point you have reached for this order before changing the status.
                  </Text>
                  <TextInput
                    value={noteDraft}
                    onChangeText={setNoteDraft}
                    placeholder="e.g. Order received and being packed…"
                    placeholderTextColor={COLORS.textMuted}
                    multiline
                    style={styles.noteInput}
                    autoCapitalize="sentences"
                  />
                  <View style={styles.actions}>
                    <ActionBtn
                      label={busyKey === key ? '…' : `Mark ${next === 'picked' ? 'Picked' : 'Served'}`}
                      tone="primary"
                      disabled={busyKey === key}
                      onPress={confirmNote}
                    />
                    <ActionBtn
                      label="Cancel"
                      tone="ghost"
                      onPress={() => {
                        setNoteFor(null);
                        setNoteDraft('');
                      }}
                    />
                  </View>
                </View>
              ) : null}

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
            </Pressable>
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
  noteWrap: { marginTop: 10 },
  noteHint: { color: COLORS.textMuted, fontSize: 11, marginBottom: 6 },
  noteInput: {
    minHeight: 74,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.md,
    padding: 12,
    color: COLORS.text,
    fontSize: 14,
    textAlignVertical: 'top',
  },
  nameRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 10, marginTop: 6 },
});
