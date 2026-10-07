import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchIncoming, receiveTransferItem, rejectTransferItem } from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';

/**
 * Incoming stock — the flat pending-incoming list the website's
 * stock-transfers/incoming page shows. Each row can be verified into this
 * branch's stock or rejected with a reason (which returns it to the sender
 * and notifies them, exactly like the website).
 */
export default function SmIncoming() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchIncoming(), []);

  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [rejecting, setRejecting] = useState<string | null>(null);
  const [reason, setReason] = useState('');

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

  const rows = data?.rows ?? [];

  return (
    <AdminPage title="Incoming Stock" eyebrow="Stock Manager" accent={SM_ACCENT.main} onBack={() => router.back()}>
      {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

      {loading ? (
        <LoadingView label="Loading incoming…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : rows.length === 0 ? (
        <EmptyView icon="download-outline" title="Nothing incoming" hint="Transfers destined for your branch appear here." />
      ) : (
        rows.map((row, i) => {
          const itemId = String(row.item?.id ?? i);
          return (
            <DataCard
              key={itemId}
              title={row.item_label}
              subtitle={`${row.stock_type_label ?? ''} · from ${row.from_branch_name ?? '—'}`}
              lines={[
                `Transfer ${row.transfer_number ?? ''}${row.note ? ` · ${row.note}` : ''}`,
                row.officer_name ? `Officer: ${row.officer_name}` : null,
                row.created_at ? formatDate(row.created_at) : null,
              ]}
              badge="awaiting receipt"
              badgeTone="warning"
            >
              <View style={styles.actions}>
                <Pressable
                  onPress={() => run(() => receiveTransferItem(itemId))}
                  style={({ pressed }) => [styles.primaryBtn, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <Text style={styles.primaryBtnText}>Verify &amp; receive</Text>
                </Pressable>
                <Pressable
                  onPress={() => setRejecting(rejecting === itemId ? null : itemId)}
                  style={({ pressed }) => [styles.dangerBtn, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <Text style={styles.dangerBtnText}>Reject…</Text>
                </Pressable>
              </View>

              {rejecting === itemId ? (
                <View style={styles.rejectBox}>
                  <AuthField
                    label="Rejection reason (required)"
                    value={reason}
                    onChangeText={setReason}
                    placeholder="e.g. wrong product, damaged packaging"
                    icon="alert-circle-outline"
                    autoCapitalize="sentences"
                  />
                  <Pressable
                    onPress={() => {
                      if (reason.trim().length < 3) return;
                      run(
                        () => rejectTransferItem(itemId, reason.trim()),
                        () => {
                          setRejecting(null);
                          setReason('');
                        },
                      );
                    }}
                    style={({ pressed }) => [styles.dangerBtn, pressed && styles.pressed, reason.trim().length < 3 && styles.disabled]}
                    accessibilityRole="button"
                  >
                    <Text style={styles.dangerBtnText}>Confirm rejection</Text>
                  </Pressable>
                </View>
              ) : null}
            </DataCard>
          );
        })
      )}

      <GroupLabel>After receipt</GroupLabel>
      <Text style={styles.hint}>
        Verified items are added to this branch&apos;s stock immediately; rejected items travel back to the sender, whose stock manager is notified.
      </Text>

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
  hint: { color: COLORS.textMuted, fontSize: 12.5, lineHeight: 18 },
});
