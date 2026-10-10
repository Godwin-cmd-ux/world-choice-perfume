import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import {
  declareLost,
  fetchLostForm,
  fetchReturns,
  resendReturnItem,
  writeOffReturnItem,
} from '../../lib/smApi';
import { BUTTON, COLORS, RADIUS, SM_ACCENT } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

/**
 * Returned items + Lost items — the two lower transfer screens of the
 * website sidebar (stock-transfers/returns and lost-items). A returned item
 * can be re-sent or written off; the Kinondoni manager is redirected by the
 * server to the mandatory damage report (code: damage_report_required).
 */
export default function SmReturns() {
  const [section, setSection] = useState<'returns' | 'lost'>('returns');
  const returnsData = useAsyncData(() => fetchReturns(), [section]);
  const lostData = useAsyncData(() => fetchLostForm(), [section]);

  const active = section === 'returns' ? returnsData : lostData;
  const { data, error, loading, sessionExpired, reload } = active;

  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [reason, setReason] = useState('');
  const [expandedId, setExpandedId] = useState<string | null>(null);

  const [lostTransferId, setLostTransferId] = useState('');
  const [lostItem, setLostItem] = useState('');
  const [lostQty, setLostQty] = useState('');
  const [lostReason, setLostReason] = useState('');

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

  const returnRows = section === 'returns' ? ((data as { rows?: unknown[] })?.rows ?? []) : [];
  const lostTransfers = section === 'lost' ? ((data as { transfers?: { id: number | string; transfer_number?: string | null; stock_type_label: string; to_branch_name: string }[] })?.transfers ?? []) : [];

  return (
    <AdminPage title="Returns & Lost" eyebrow="Stock Manager" onMenu={smMenu.open} accent={SM_ACCENT.main} onBack={() => router.back()}>
      <ChipRow>
        <Chip label="Returned items" active={section === 'returns'} onPress={() => setSection('returns')} />
        <Chip label="Declare lost item" active={section === 'lost'} onPress={() => setSection('lost')} />
      </ChipRow>

      {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

      {loading ? (
        <LoadingView label="Loading…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : section === 'returns' ? (
        returnRows.length === 0 ? (
          <EmptyView icon="return-down-back-outline" title="No returned items" hint="Items a receiver rejected appear here." />
        ) : (
          returnRows.map((raw, i) => {
            const row = raw as {
              item: Record<string, unknown> & { id: number | string; return_status?: string | null };
              item_label: string;
              transfer_number?: string | null;
              to_branch_name?: string;
            };
            const itemId = String(row.item.id);
            const status = row.item.return_status ?? 'pending';
            const expanded = expandedId === itemId;
            return (
              <DataCard
                key={itemId}
                title={row.item_label}
                subtitle={row.transfer_number ?? undefined}
                lines={[`To ${row.to_branch_name ?? '—'}`]}
                badge={status}
                badgeTone={status === 'resent' ? 'success' : status === 'written_off' ? 'danger' : status === 'reported' ? 'warning' : 'gold'}
                onPress={() => {
                  setExpandedId(expanded ? null : itemId);
                  setReason('');
                }}
              >
                {expanded && status === 'pending' ? (
                  <View style={styles.actions}>
                    <Pressable
                      onPress={() => run(() => resendReturnItem(itemId))}
                      style={({ pressed }) => [styles.primaryBtn, pressed && styles.pressed]}
                      accessibilityRole="button"
                    >
                      <Text style={styles.primaryBtnText}>Re-send</Text>
                    </Pressable>
                    <AuthField
                      label="Write-off reason"
                      value={reason}
                      onChangeText={setReason}
                      placeholder="Why is it lost?"
                      icon="alert-circle-outline"
                      autoCapitalize="sentences"
                    />
                    <Pressable
                      onPress={() => {
                        if (reason.trim().length < 3) return;
                        run(() => writeOffReturnItem(itemId, reason.trim()), () => setExpandedId(null));
                      }}
                      style={({ pressed }) => [styles.dangerBtn, pressed && styles.pressed, reason.trim().length < 3 && styles.disabled]}
                      accessibilityRole="button"
                    >
                      <Text style={styles.dangerBtnText}>Write off as lost</Text>
                    </Pressable>
                  </View>
                ) : null}
              </DataCard>
            );
          })
        )
      ) : (
        <>
          <GroupLabel>Declare an item lost during a transfer</GroupLabel>
          <Text style={styles.label}>Transfer</Text>
          <ChipRow>
            {lostTransfers.map((t) => (
              <Chip
                key={String(t.id)}
                label={`${t.transfer_number ?? t.id} → ${t.to_branch_name}`}
                active={lostTransferId === String(t.id)}
                onPress={() => setLostTransferId(String(t.id))}
              />
            ))}
          </ChipRow>
          <AuthField label="Item description" value={lostItem} onChangeText={setLostItem} placeholder='e.g. "Test perfume" or "50ml"' icon="cube-outline" autoCapitalize="sentences" />
          <AuthField label="Quantity" value={lostQty} onChangeText={setLostQty} placeholder="1" icon="layers-outline" keyboardType="number-pad" />
          <AuthField label="Reason" value={lostReason} onChangeText={setLostReason} placeholder="What happened?" icon="document-text-outline" autoCapitalize="sentences" />
          <Pressable
            onPress={() => {
              if (!lostTransferId || !lostItem || !lostQty || lostReason.trim().length < 3) return;
              run(
                () =>
                  declareLost({
                    transfer_id: lostTransferId,
                    item: lostItem.trim(),
                    quantity: Number(lostQty),
                    reason: lostReason.trim(),
                  }),
                () => {
                  setLostItem('');
                  setLostQty('');
                  setLostReason('');
                },
              );
            }}
            style={({ pressed }) => [
              styles.primaryBtn,
              pressed && styles.pressed,
              (!lostTransferId || !lostItem || !lostQty || lostReason.trim().length < 3) && styles.disabled,
            ]}
            accessibilityRole="button"
          >
            <Text style={styles.primaryBtnText}>Declare lost</Text>
          </Pressable>
          <Text style={styles.hint}>The admin is notified for cross-checking, and matching stock leaves this branch.</Text>
        </>
      )}

      <BusyOverlay visible={busy} label="Working…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  actions: { marginTop: 10, gap: 8 },
  primaryBtn: {
    backgroundColor: BUTTON.fill,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 9,
    alignSelf: 'flex-start',
  },
  primaryBtnText: { color: BUTTON.text, fontWeight: '800', fontSize: 13 },
  dangerBtn: {
    backgroundColor: COLORS.dangerBg,
    borderWidth: 1,
    borderColor: COLORS.dangerBorder,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 9,
    alignSelf: 'flex-start',
  },
  dangerBtnText: { color: COLORS.danger, fontWeight: '800', fontSize: 13 },
  disabled: { opacity: 0.4 },
  pressed: { opacity: 0.7 },
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
  hint: { color: COLORS.textMuted, fontSize: 12.5, lineHeight: 18 },
});
