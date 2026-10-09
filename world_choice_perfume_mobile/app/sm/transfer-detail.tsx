import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, KV, useAsyncData, messageOf } from '../../components/adminkit';
import { ErrorView, LoadingView } from '../../components/ui';
import { fetchTransfer, receiveTransferItem, rejectTransferItem } from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

/**
 * Transfer detail — the mobile twin of stock-transfers/show, with the two
 * receipt actions (Verify & receive / Reject with reason) that incoming
 * branches use. Both are refused server-side unless the transfer is still
 * in transit and destined for this branch.
 */
export default function SmTransferDetail() {
  const params = useLocalSearchParams<{ id: string }>();
  const { data, error, loading, reload } = useAsyncData(() => fetchTransfer(params.id!), [params.id]);

  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [rejectingId, setRejectingId] = useState<string | null>(null);
  const [rejectReason, setRejectReason] = useState('');

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

  return (
    <AdminPage title="Transfer" eyebrow="Stock Manager" onMenu={smMenu.open} accent={SM_ACCENT.main} onBack={() => router.back()}>
      {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

      {loading ? (
        <LoadingView label="Loading transfer…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : data ? (
        <>
          <DataCard
            title={data.transfer.transfer_number ?? `Transfer #${data.transfer.id}`}
            subtitle={data.stock_type_label}
            lines={[
              `${data.from_branch_name} → ${data.to_branch_name}`,
              data.transfer.status === 'in_transit' ? `In transit · ${data.pendingCount} pending` : `Status: ${data.transfer.status ?? ''}`,
            ]}
            badge={data.transfer.status ?? 'in_transit'}
            badgeTone={data.transfer.status === 'received' ? 'success' : 'warning'}
          >
            <KV label="Officer" value={data.transfer.officer_name ? `${data.transfer.officer_name} · ${data.transfer.officer_phone ?? ''}` : null} />
            <KV label="Note" value={data.transfer.note} />
            <KV label="Sent by" value={data.created_by_name} />
            <KV label="Received by" value={data.received_by_name} />
          </DataCard>

          <GroupLabel>Items ({data.items.length})</GroupLabel>
          {data.items.map((row, i) => {
            const item = row.item as Record<string, unknown>;
            const status = String(item.status ?? 'in_transit');
            const itemId = String(item.id);
            return (
              <DataCard
                key={itemId}
                title={row.item_label}
                lines={[`Qty: ${String(item.quantity ?? '')}`, row.received_by_name ? `Received by ${row.received_by_name}` : null]}
                badge={status}
                badgeTone={status === 'received' ? 'success' : status === 'returned' ? 'danger' : 'warning'}
              >
                {status === 'in_transit' && data.transfer.status === 'in_transit' ? (
                  <View style={styles.actions}>
                    <Pressable
                      onPress={() => run(() => receiveTransferItem(itemId))}
                      style={({ pressed }) => [styles.primaryBtn, pressed && styles.pressed]}
                      accessibilityRole="button"
                    >
                      <Text style={styles.primaryBtnText}>Verify &amp; receive</Text>
                    </Pressable>
                    <Pressable
                      onPress={() => setRejectingId(rejectingId === itemId ? null : itemId)}
                      style={({ pressed }) => [styles.dangerBtn, pressed && styles.pressed]}
                      accessibilityRole="button"
                    >
                      <Text style={styles.dangerBtnText}>Reject…</Text>
                    </Pressable>
                  </View>
                ) : null}

                {rejectingId === itemId ? (
                  <View style={styles.rejectBox}>
                    <AuthField
                      label="Rejection reason (required)"
                      value={rejectReason}
                      onChangeText={setRejectReason}
                      placeholder="Why is this item not acceptable?"
                      icon="alert-circle-outline"
                      autoCapitalize="sentences"
                    />
                    <Pressable
                      onPress={() => {
                        if (!rejectReason || rejectReason.trim().length < 3) return;
                        run(
                          () => rejectTransferItem(itemId, rejectReason.trim()),
                          () => {
                            setRejectingId(null);
                            setRejectReason('');
                          },
                        );
                      }}
                      style={({ pressed }) => [styles.dangerBtn, pressed && styles.pressed, rejectReason.trim().length < 3 && styles.disabled]}
                      accessibilityRole="button"
                    >
                      <Text style={styles.dangerBtnText}>Confirm rejection</Text>
                    </Pressable>
                  </View>
                ) : null}
              </DataCard>
            );
          })}
        </>
      ) : null}

      <BusyOverlay visible={busy} label="Working…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  actions: { flexDirection: 'row', gap: 8, marginTop: 10, flexWrap: 'wrap' },
  primaryBtn: { backgroundColor: SM_ACCENT.main, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  primaryBtnText: { color: '#052E1B', fontWeight: '800', fontSize: 13 },
  dangerBtn: {
    backgroundColor: COLORS.dangerBg,
    borderWidth: 1,
    borderColor: COLORS.dangerBorder,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  dangerBtnText: { color: COLORS.danger, fontWeight: '800', fontSize: 13 },
  disabled: { opacity: 0.4 },
  pressed: { opacity: 0.7 },
  rejectBox: { marginTop: 10, gap: 8 },
});
