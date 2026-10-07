import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, KV, useAsyncData } from '../../components/adminkit';
import { Card, ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { errorMessage, isApiError } from '../../lib/api';
import { fetchAdminOrder, saveOrderPersonalName } from '../../lib/adminApi';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * Order detail — the mobile twin of super-admin/orders/show.blade.php,
 * including the one Super Admin order operation: correcting an order's
 * personal name for any branch (same OrderWorkflowService guard rails —
 * nothing else on the row can be touched).
 */
export default function OrderDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = String(id ?? '');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchAdminOrder(orderId),
    [orderId],
  );

  const order = data?.order;
  const [label, setLabel] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [saveError, setSaveError] = useState<string | null>(null);

  const effectiveLabel = label ?? String(order?.personal_order_name ?? '');

  const save = async () => {
    if (saving) return;
    setSaving(true);
    setSaveError(null);
    setNotice(null);
    try {
      const res = await saveOrderPersonalName(orderId, effectiveLabel.trim());
      setNotice(res.message);
      await reload();
    } catch (e) {
      if (isApiError(e) && e.kind === 'validation') setSaveError(Object.values(e.fields)[0] ?? e.message);
      else setSaveError(errorMessage(e));
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminPage title="Order Detail" onBack={() => router.back()}>
      {loading ? <LoadingView label="Loading order…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {order ? (
        <>
          {notice ? <Banner kind="success" message={notice} /> : null}
          {saveError ? <Banner kind="error" message={saveError} /> : null}

          <Card style={{ gap: 6 }}>
            <Text style={styles.title}>
              {String(order.personal_order_name || order.order_number || `Order #${order.id}`)}
            </Text>
            <View style={{ marginTop: 8, gap: 6 }}>
              <KV label="Status:" value={order.status ? String(order.status) : null} tone={order.status === 'pending' ? 'warning' : 'success'} />
              <KV label="Branch:" value={order.branch?.name ?? (order.branch_id ? `Branch #${order.branch_id}` : null)} />
              <KV label="Customer:" value={(order.customer_name as string) ?? null} />
              <KV label="Total:" value={order.total != null ? `TZS ${Number(order.total).toLocaleString('en-US')}` : null} />
              <KV label="Placed:" value={order.created_at ? new Date(order.created_at).toLocaleString() : null} />
              <KV label="Waiting:" value={order.duration_label ? String(order.duration_label) : null} />
            </View>
          </Card>

          <GroupLabel>Items</GroupLabel>
          <Card style={{ gap: 8 }}>
            {(() => {
              const items = Array.isArray(order.items) ? (order.items as Record<string, unknown>[]) : [];
              if (items.length === 0) return <Text style={styles.muted}>No item details on this order.</Text>;
              return items.map((it, i) => {
                const product = (it.product ?? {}) as { name?: string };
                const qty = Number(it.quantity ?? 0);
                const total = Number(it.total ?? 0);
                return (
                  <View key={i} style={styles.itemRow}>
                    <Text style={styles.itemName} numberOfLines={1}>
                      {product.name ?? 'Item'} × {qty}
                    </Text>
                    <Text style={styles.itemTotal}>TZS {total.toLocaleString('en-US')}</Text>
                  </View>
                );
              });
            })()}
          </Card>

          <GroupLabel>Order label (personal name)</GroupLabel>
          <Text style={styles.hint}>
            Super Admin may correct the name shown for this order, for any branch — the website offers the same field.
          </Text>
          <TextInput
            value={effectiveLabel}
            onChangeText={setLabel}
            placeholder="e.g. Birthday gift for Amina"
            placeholderTextColor={COLORS.textMuted}
            style={styles.input}
            maxLength={120}
          />
          <GoldButton label="Save Label" onPress={save} loading={saving} icon="save-outline" />
        </>
      ) : null}
      <BusyOverlay visible={saving} label="Saving…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  title: { color: COLORS.text, fontSize: 19, fontWeight: '800' },
  muted: { color: COLORS.textMuted, fontSize: 13 },
  hint: { color: COLORS.textSecondary, fontSize: 13, lineHeight: 19 },
  itemRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 10 },
  itemName: { color: COLORS.textSecondary, fontSize: 14, flex: 1 },
  itemTotal: { color: COLORS.goldLight, fontSize: 14, fontWeight: '700' },
  input: {
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    height: 48,
    color: COLORS.text,
    fontSize: 15,
  },
});
