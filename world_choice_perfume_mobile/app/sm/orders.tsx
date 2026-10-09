import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchOrders, setOrderPersonalName, updateOrderStatus, type SmOrder } from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

const TABS = ['pending', 'picked', 'served'] as const;

/**
 * Orders — the three website tabs (Pending is the shared queue anyone can
 * claim; Picked and Served are the manager's own). Only picked and served
 * transitions exist: an order can never go back to pending, exactly like
 * OrderWorkflowService enforces server-side.
 */
export default function SmOrders() {
  const [tab, setTab] = useState<(typeof TABS)[number]>('pending');
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchOrders({ tab }), [tab]);

  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [openedId, setOpenedId] = useState<string | null>(null);
  const [personalName, setPersonalName] = useState('');

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const run = async (fn: () => Promise<unknown>, after?: () => void) => {
    setBusy(true);
    setActionError(null);
    try {
      await fn();
      after?.();
      await reload();
    } catch (e) {
      setActionError(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  const orders = data?.orders ?? [];
  const counts = data?.counts ?? {};

  return (
    <AdminPage title="Orders" eyebrow="Stock Manager" onMenu={smMenu.open} accent={SM_ACCENT.main} onBack={() => router.back()}>
      <ChipRow>
        {TABS.map((t) => (
          <Chip
            key={t}
            label={`${t.charAt(0).toUpperCase() + t.slice(1)}${counts[t] != null ? ` (${counts[t]})` : ''}`}
            active={tab === t}
            onPress={() => setTab(t)}
          />
        ))}
      </ChipRow>

      {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

      {loading ? (
        <LoadingView label="Loading orders…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : orders.length === 0 ? (
        <EmptyView icon="list-outline" title={`No ${tab} orders`} hint="Orders appear here as customers place them." />
      ) : (
        orders.map((o: SmOrder) => {
          const id = String(o.id);
          const open = openedId === id;
          const status = String(o.status ?? 'pending');
          return (
            <DataCard
              key={id}
              title={String(o.personal_order_name ?? o.customer_name ?? `Order #${id}`)}
              subtitle={`#${id}${o.pickers && typeof o.pickers === 'string' ? ` · ${o.pickers}` : ''}`}
              lines={[
                o.created_at ? formatDate(o.created_at) : null,
                status === 'pending' ? 'Waiting to be picked' : `Status: ${status}`,
              ]}
              badge={status}
              badgeTone={status === 'served' ? 'success' : status === 'picked' ? 'warning' : 'gold'}
              onPress={() => {
                setOpenedId(open ? null : id);
                setPersonalName(String(o.personal_order_name ?? ''));
              }}
            >
              {open ? (
                <View style={styles.actions}>
                  <AuthField
                    label="Personal name on order"
                    value={personalName}
                    onChangeText={setPersonalName}
                    placeholder="Name for this order"
                    icon="person-outline"
                  />
                  <View style={styles.actionRow}>
                    <Pressable
                      onPress={() => run(() => setOrderPersonalName(id, personalName))}
                      style={({ pressed }) => [styles.smallBtn, pressed && styles.pressed]}
                      accessibilityRole="button"
                    >
                      <Text style={styles.smallBtnText}>Save name</Text>
                    </Pressable>
                    {status === 'pending' ? (
                      <Pressable
                        onPress={() => run(() => updateOrderStatus(id, 'picked'))}
                        style={({ pressed }) => [styles.smallBtn, styles.primary, pressed && styles.pressed]}
                        accessibilityRole="button"
                      >
                        <Text style={styles.smallBtnText}>Mark picked</Text>
                      </Pressable>
                    ) : null}
                    {status !== 'served' ? (
                      <Pressable
                        onPress={() => run(() => updateOrderStatus(id, 'served'))}
                        style={({ pressed }) => [styles.smallBtn, styles.primary, pressed && styles.pressed]}
                        accessibilityRole="button"
                      >
                        <Text style={styles.smallBtnText}>Mark served</Text>
                      </Pressable>
                    ) : null}
                  </View>
                </View>
              ) : null}
            </DataCard>
          );
        })
      )}

      <BusyOverlay visible={busy} label="Working…" />
    </AdminPage>
  );
}

function formatDate(value?: string | null): string {
  if (!value) return '';
  const d = new Date(value);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleString('en-US', {
    timeZone: 'Africa/Dar_es_Salaam',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

const styles = StyleSheet.create({
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  actions: { marginTop: 10, gap: 8 },
  actionRow: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  smallBtn: {
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  primary: { backgroundColor: SM_ACCENT.main, borderColor: SM_ACCENT.main },
  smallBtnText: { color: SM_ACCENT.light, fontSize: 12.5, fontWeight: '800' },
  pressed: { opacity: 0.7 },
});
