import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, KV, useAsyncData } from '../../components/adminkit';
import { ErrorView, LoadingView } from '../../components/ui';
import { fetchOrder, setOrderPersonalName, updateOrderStatus } from '../../lib/careApi';
import { formatDateTime, formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../lib/theme';

/**
 * Order record — the website's customer-care/orders/{id}: the order number
 * with the branch and status, the staff-only personal name, who ordered and
 * who served it, the line items, the notes every status change has written,
 * and the allowed transitions behind the same mandatory "what point have you
 * reached" note the blade demands.
 */
export default function CareOrderDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchOrder(id!), [id]);
  const [refreshing, setRefreshing] = useState(false);

  const [nameDraft, setNameDraft] = useState('');
  const [nameSeeded, setNameSeeded] = useState(false);
  const [noteDraft, setNoteDraft] = useState('');
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const order = data?.order;
  if (order && !nameSeeded) {
    setNameDraft((order.personal_order_name as string | null) ?? '');
    setNameSeeded(true);
  }

  const status = String(order?.status ?? 'pending');
  const allowed = (data ? (data.transitions[status] as string[] | undefined) : undefined) ?? [];
  const items = ((order?.items ?? []) as {
    id?: number | string;
    quantity?: number;
    unit_price?: number;
    total?: number;
    volume?: number | string | null;
    variant?: string | null;
    name?: string;
    product?: { name?: string; brand?: string } | null;
  }[]) ?? [];

  const saveName = async () => {
    if (!id) return;
    setBusyKey('name');
    setActionError(null);
    try {
      const res = await setOrderPersonalName(id, nameDraft.trim());
      setNotice(res.message);
      await reload();
    } catch (e) {
      setActionError(String((e as { message?: string }).message ?? e));
    } finally {
      setBusyKey(null);
    }
  };

  const move = async (next: 'picked' | 'served') => {
    const note = noteDraft.trim();
    if (!note) {
      setActionError('Please enter a note about this order update before changing the status.');
      return;
    }
    if (!id) return;
    setBusyKey(next);
    setActionError(null);
    try {
      const res = await updateOrderStatus(id, next, note);
      setNoteDraft('');
      setNotice(res.message);
      await reload();
    } catch (e) {
      setActionError(String((e as { message?: string }).message ?? e));
    } finally {
      setBusyKey(null);
    }
  };

  return (
    <AdminPage
      title={String(order?.order_number ?? 'Order')}
      eyebrow="Order record"
      accent={CC_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {notice ? <Banner kind="success" message={notice} /> : null}
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {actionError ? <Banner kind="error" message={actionError} /> : null}

      {loading && !data ? (
        <LoadingView label="Loading order…" />
      ) : error && !order ? (
        <ErrorView message={error} onRetry={reload} />
      ) : order ? (
        <>
          <DataCard
            title={String(order.order_number ?? `Order #${order.id}`)}
            subtitle={order.branch?.name ?? null}
            badge={status}
            badgeTone={status === 'served' ? 'success' : status === 'picked' ? 'gold' : 'warning'}
          >
            <View>
              <KV label="Customer" value={order.customer?.name ?? 'N/A'} />
              <KV label="Phone" value={order.customer?.phone ?? 'N/A'} />
              <KV label="Served by" value={order.cashier?.name ?? null} />
              <KV label="Placed" value={formatDateTime(order.created_at)} />
              {order.delivery_notes ? <KV label="Notes" value={order.delivery_notes} /> : null}
            </View>
          </DataCard>

          {data?.canName ? (
            <>
              <GroupLabel>Personal name</GroupLabel>
              <View style={styles.nameRow}>
                <View style={{ flex: 1 }}>
                  <AuthField
                    label="Your memory aid for this order"
                    value={nameDraft}
                    onChangeText={setNameDraft}
                    placeholder="e.g. Mama Nasia"
                    icon="pricetag-outline"
                    autoCapitalize="words"
                    maxLength={120}
                  />
                </View>
                <ActionBtn
                  label={busyKey === 'name' ? '…' : 'Save'}
                  tone="primary"
                  disabled={busyKey === 'name'}
                  onPress={saveName}
                />
              </View>
            </>
          ) : null}

          <GroupLabel>Items</GroupLabel>
          {items.length === 0 ? (
            <DataCard title="No line items" />
          ) : (
            items.map((it, i) => (
              <DataCard
                key={String(it.id ?? i)}
                title={it.product?.name ?? it.name ?? `Item ${i + 1}`}
                subtitle={it.product?.brand ?? undefined}
                badge={formatMoney(it.total ?? 0)}
                badgeTone="gold"
                lines={[
                  `${it.quantity ?? 0} × ${formatMoney(it.unit_price ?? 0)}`,
                  it.volume ? `${it.volume}ml${it.variant ? ` · ${String(it.variant).replace(/_/g, ' ')}` : ''}` : null,
                ].filter(Boolean) as string[]}
              />
            ))
          )}
          <DataCard title="Total" badge={formatMoney(order.total ?? 0)} badgeTone="success" />

          {(order.notes ?? []).length > 0 ? (
            <>
              <GroupLabel>Order updates</GroupLabel>
              {(order.notes ?? []).map((n, i) => (
                <DataCard
                  key={String(n.id ?? i)}
                  title={n.note ?? ''}
                  lines={[formatDateTime(n.created_at)].filter(Boolean) as string[]}
                />
              ))}
            </>
          ) : null}

          {allowed.length > 0 ? (
            <>
              <GroupLabel>Order note (required)</GroupLabel>
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
                {allowed.map((s) => (
                  <ActionBtn
                    key={s}
                    label={busyKey === s ? '…' : `Mark ${s.charAt(0).toUpperCase()}${s.slice(1)}`}
                    tone={s === 'served' ? 'success' : 'primary'}
                    disabled={busyKey === s}
                    onPress={() => move(s as 'picked' | 'served')}
                  />
                ))}
              </View>
            </>
          ) : null}
        </>
      ) : null}

      <BusyOverlay visible={refreshing || busyKey !== null} label="Saving…" />
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
      style={({ pressed }) => [
        styles.action,
        { backgroundColor: bg, borderColor: border },
        pressed && { opacity: 0.7 },
        disabled && { opacity: 0.5 },
      ]}
      accessibilityRole="button"
    >
      <Text style={[styles.actionText, { color: tone === 'ghost' ? COLORS.textSecondary : fg }]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  nameRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 10 },
  noteHint: { color: COLORS.textMuted, fontSize: 11, marginBottom: 6 },
  noteInput: {
    minHeight: 74,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.bgRaised,
    borderRadius: RADIUS.md,
    padding: 12,
    color: COLORS.text,
    fontSize: 14,
    textAlignVertical: 'top',
  },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10 },
  action: { borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  actionText: { fontWeight: '800', fontSize: 12 },
});
