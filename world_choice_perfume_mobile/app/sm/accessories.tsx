import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData, messageOf } from '../../components/adminkit';
import { ErrorView, LoadingView } from '../../components/ui';
import {
  accessoryStockOut,
  addAccessory,
  deleteAccessory,
  fetchAccessories,
  updateAccessory,
  type SmAccessory,
  type SmAccessoryColor,
  type SmAccessoryType,
} from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';

type Mode = 'list' | 'in' | 'out';

const TYPE_LABELS: Record<string, string> = {
  straws: 'Straws',
  bottlenecks: 'Bottle necks',
  bottle_tops: 'Bottle tops',
};

/**
 * Bottle Accessories — mobile twin of bottle-accessories.blade.php: rows
 * grouped by type (straws / bottlenecks / bottle tops), packet counts, the
 * stock-in and stock-out forms with the same insufficient-stock message.
 * Products-only branches get the server's 403 as an explanatory state.
 */
export default function SmAccessories() {
  const { data, error, loading, refreshing, reload, refresh } = useAsyncData(() => fetchAccessories(), []);

  const [mode, setMode] = useState<Mode>('list');
  const [type, setType] = useState<SmAccessoryType>('straws');
  const [color, setColor] = useState<SmAccessoryColor>('silver');
  const [qty, setQty] = useState('');
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [pendingDelete, setPendingDelete] = useState<SmAccessory | null>(null);
  const [editRow, setEditRow] = useState<SmAccessory | null>(null);
  const [editQty, setEditQty] = useState('');

  const forbidden = (error ?? '').includes('not available for your branch');
  const grouped = data?.grouped ?? {};

  const run = async (fn: () => Promise<unknown>, after?: () => void) => {
    setBusy(true);
    setActionError(null);
    setFieldErrors({});
    try {
      await fn();
      after?.();
      await reload();
    } catch (e) {
      const anyErr = e as { fields?: Record<string, string> };
      if (anyErr?.fields && Object.keys(anyErr.fields).length > 0) setFieldErrors(anyErr.fields);
      setActionError(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  return (
    <AdminPage
      title="Bottle Accessories"
      eyebrow="Stock Manager"
      accent={SM_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={refresh}
    >
      {forbidden ? (
        <View style={styles.blocked}>
          <Banner kind="error" message="Bottle, oil fragrance and bottle accessories management are not available for your branch." />
          <Text style={styles.blockedHint}>
            Your branch is products-only: work with Product Stock instead. Nothing on this screen can change your branch&apos;s stock.
          </Text>
        </View>
      ) : (
        <>
          <ChipRow>
            <Chip label="Stock list" active={mode === 'list'} onPress={() => setMode('list')} />
            <Chip label="Add packets" active={mode === 'in'} onPress={() => setMode('in')} />
            <Chip label="Stock out" active={mode === 'out'} onPress={() => setMode('out')} />
          </ChipRow>

          {actionError || (error && !forbidden) ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

          {mode === 'list' ? (
            loading ? (
              <LoadingView label="Loading accessories…" />
            ) : error && !data ? (
              <ErrorView message={error} onRetry={reload} />
            ) : (
              <>
                <StatGrid>
                  <StatTile label="Total packets" value={data?.totalPackets ?? 0} tone="gold" />
                  {['straws', 'bottlenecks', 'bottle_tops'].map((t) => (
                    <StatTile
                      key={t}
                      label={TYPE_LABELS[t]}
                      value={(grouped[t] ?? []).reduce((sum, a) => sum + (a.quantity ?? 0), 0)}
                    />
                  ))}
                </StatGrid>

                {['straws', 'bottlenecks', 'bottle_tops'].map((t) => (
                  <View key={t}>
                    <GroupLabel>{TYPE_LABELS[t]}</GroupLabel>
                    {(grouped[t] ?? []).length === 0 ? (
                      <Text style={styles.emptyLine}>No {TYPE_LABELS[t].toLowerCase()} in stock.</Text>
                    ) : (
                      (grouped[t] ?? []).map((a) => (
                        <DataCard
                          key={String(a.id)}
                          title={`${TYPE_LABELS[t]} — ${a.color ?? ''}`}
                          lines={[`Packets: ${a.quantity ?? 0}`]}
                          badge={String(a.quantity ?? 0)}
                          badgeTone={(a.quantity ?? 0) > 0 ? 'success' : 'danger'}
                        >
                          <View style={styles.rowActions}>
                            <Pressable
                              onPress={() => {
                                setEditRow(a);
                                setEditQty(String(a.quantity ?? 0));
                              }}
                              style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                              accessibilityRole="button"
                            >
                              <Text style={styles.actionText}>Edit qty</Text>
                            </Pressable>
                            <Pressable
                              onPress={() => setPendingDelete(a)}
                              style={({ pressed }) => [styles.actionBtn, styles.actionDanger, pressed && styles.pressed]}
                              accessibilityRole="button"
                            >
                              <Text style={[styles.actionText, { color: COLORS.danger }]}>Delete</Text>
                            </Pressable>
                          </View>
                        </DataCard>
                      ))
                    )}
                  </View>
                ))}
              </>
            )
          ) : null}

          {mode === 'in' || mode === 'out' ? (
            <>
              <GroupLabel>{mode === 'in' ? 'Add accessory stock' : 'Record stock out'}</GroupLabel>
              <Text style={styles.label}>Type</Text>
              <ChipRow>
                {(['straws', 'bottlenecks', 'bottle_tops'] as SmAccessoryType[]).map((t) => (
                  <Chip key={t} label={TYPE_LABELS[t]} active={type === t} onPress={() => setType(t)} />
                ))}
              </ChipRow>
              <Text style={styles.label}>Color</Text>
              <ChipRow>
                <Chip label="Silver" active={color === 'silver'} onPress={() => setColor('silver')} />
                <Chip label="Gold" active={color === 'gold'} onPress={() => setColor('gold')} />
              </ChipRow>
              <AuthField
                label="Packets"
                value={qty}
                onChangeText={setQty}
                placeholder="e.g. 20"
                icon="layers-outline"
                keyboardType="number-pad"
                error={fieldErrors.quantity}
              />
              <AuthField
                label="Reason (optional)"
                value={reason}
                onChangeText={setReason}
                placeholder={mode === 'in' ? 'Delivery…' : 'Used for packing…'}
                icon="document-text-outline"
                error={fieldErrors.reason}
              />
              <Pressable
                style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, !qty && styles.submitDisabled]}
                disabled={!qty}
                onPress={() =>
                  run(
                    () => {
                      const fields = { type, color, quantity: Number(qty), reason: reason || undefined };
                      return mode === 'in' ? addAccessory(fields) : accessoryStockOut(fields);
                    },
                    () => {
                      setQty('');
                      setReason('');
                      setMode('list');
                    },
                  )
                }
                accessibilityRole="button"
              >
                <Text style={styles.submitText}>{mode === 'in' ? 'Add packets' : 'Record stock out'}</Text>
              </Pressable>
            </>
          ) : null}

          {editRow ? (
            <View style={styles.inlineEdit}>
              <Text style={styles.inlineEditTitle}>
                Edit {TYPE_LABELS[String(editRow.type)] ?? editRow.type} · {editRow.color}
              </Text>
              <AuthField label="Packets" value={editQty} onChangeText={setEditQty} placeholder="0" icon="layers-outline" keyboardType="number-pad" />
              <View style={styles.rowActions}>
                <Pressable
                  style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, !editQty && styles.submitDisabled]}
                  disabled={!editQty}
                  onPress={() => run(() => updateAccessory(editRow.id, { quantity: Number(editQty) }), () => setEditRow(null))}
                  accessibilityRole="button"
                >
                  <Text style={styles.submitText}>Save</Text>
                </Pressable>
                <Pressable onPress={() => setEditRow(null)} style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]} accessibilityRole="button">
                  <Text style={styles.actionText}>Cancel</Text>
                </Pressable>
              </View>
            </View>
          ) : null}
        </>
      )}

      <ConfirmDialog
        visible={pendingDelete !== null}
        title="Delete accessory record?"
        message={`${TYPE_LABELS[String(pendingDelete?.type)] ?? ''} (${pendingDelete?.color ?? ''}) — ${pendingDelete?.quantity ?? 0} packets will be removed.`}
        confirmLabel="Delete"
        danger
        loading={busy}
        onCancel={() => setPendingDelete(null)}
        onConfirm={async () => {
          if (!pendingDelete) return;
          const row = pendingDelete;
          setBusy(true);
          try {
            await deleteAccessory(row.id);
            setPendingDelete(null);
            await reload();
          } catch (e) {
            setActionError(messageOf(e));
          } finally {
            setBusy(false);
          }
        }}
      />

      <BusyOverlay visible={busy} label="Working…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  blocked: { gap: 10 },
  blockedHint: { color: COLORS.textSecondary, fontSize: 13, lineHeight: 19 },
  rowActions: { flexDirection: 'row', gap: 8, marginTop: 10, alignItems: 'center' },
  actionBtn: {
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  actionDanger: { backgroundColor: COLORS.dangerBg, borderColor: COLORS.dangerBorder },
  actionText: { color: SM_ACCENT.light, fontSize: 12.5, fontWeight: '700' },
  pressed: { opacity: 0.7 },
  emptyLine: { color: COLORS.textMuted, fontSize: 13 },
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
  inlineEdit: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.md,
    padding: 14,
    gap: 6,
  },
  inlineEditTitle: { color: SM_ACCENT.light, fontSize: 14, fontWeight: '800' },
  submitBtn: {
    backgroundColor: SM_ACCENT.main,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    paddingVertical: 14,
    marginTop: 10,
  },
  submitDisabled: { opacity: 0.4 },
  submitText: { color: '#052E1B', fontSize: 15, fontWeight: '800' },
});
