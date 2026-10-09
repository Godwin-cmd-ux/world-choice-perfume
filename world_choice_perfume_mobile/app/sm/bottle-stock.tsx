import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import {
  addBottleStock,
  deleteBottleStock,
  fetchBottleStock,
  recordBrokenBottles,
  updateBottleStock,
  type SmBottleRecord,
} from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

type Mode = 'list' | 'in' | 'broken';

/**
 * Bottle Stock — mobile twin of bottle-stock.blade.php + its stock-in and
 * broken forms. Only reachable for autonomous branches: the server answers
 * 403 (website's message) when a products_based branch calls it, and that
 * case is rendered as an explanatory empty state rather than an error wall.
 */
export default function SmBottleStock() {
  const { data, error, loading, refreshing, reload, refresh } = useAsyncData(() => fetchBottleStock(), []);

  const [mode, setMode] = useState<Mode>('list');
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [pendingDelete, setPendingDelete] = useState<SmBottleRecord | null>(null);

  // Stock-in form
  const [volume, setVolume] = useState('50ml');
  const [quantity, setQuantity] = useState('');
  const [reason, setReason] = useState('');
  const [hasBox, setHasBox] = useState<'' | 'yes' | 'no'>('');
  const [hasLogo, setHasLogo] = useState<'' | 'yes' | 'no'>('');
  const [logoColor, setLogoColor] = useState('');

  // Broken form
  const [brokenVolume, setBrokenVolume] = useState('50ml');
  const [brokenQty, setBrokenQty] = useState('');
  const [brokenReason, setBrokenReason] = useState('');
  const [brokenVariant, setBrokenVariant] = useState('');

  // Edit row
  const [editRow, setEditRow] = useState<SmBottleRecord | null>(null);
  const [editQty, setEditQty] = useState('');

  const forbidden = (error ?? '').includes('not available for your branch');

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

  const records = data?.bottleRecords ?? [];

  return (
    <AdminPage
      title="Bottle Stock"
      eyebrow="Stock Manager" onMenu={smMenu.open}
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
            <Chip label="Stock in" active={mode === 'in'} onPress={() => setMode('in')} />
            <Chip label="Broken" active={mode === 'broken'} onPress={() => setMode('broken')} />
          </ChipRow>

          {actionError || (error && !forbidden) ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

          {mode === 'list' ? (
            loading ? (
              <LoadingView label="Loading bottles…" />
            ) : error && !data ? (
              <ErrorView message={error} onRetry={reload} />
            ) : (
              <>
                <StatGrid>
                  {Object.entries(data?.bottleMap ?? {}).map(([vol, qty]) => (
                    <StatTile key={vol} label={vol} value={qty} tone={qty > 0 ? 'gold' : 'danger'} />
                  ))}
                </StatGrid>

                <GroupLabel>Variant records</GroupLabel>
                {records.length === 0 ? (
                  <EmptyView icon="cube-outline" title="No bottle stock" hint="Use Stock in to record bottles." />
                ) : (
                  <>
                    {editRow ? (
                      <View style={styles.inlineEdit}>
                        <Text style={styles.inlineEditTitle}>
                          Edit {editRow.volume ?? ''} · {editRow.variant ?? 'plain'}
                        </Text>
                        <AuthField
                          label="Quantity"
                          value={editQty}
                          onChangeText={setEditQty}
                          placeholder="0"
                          icon="layers-outline"
                          keyboardType="number-pad"
                        />
                        <View style={styles.rowActions}>
                          <Pressable
                            style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, !editQty && styles.submitDisabled]}
                            disabled={!editQty}
                            onPress={() =>
                              run(
                                () => updateBottleStock(editRow.id, { quantity: Number(editQty) }),
                                () => setEditRow(null),
                              )
                            }
                            accessibilityRole="button"
                          >
                            <Text style={styles.submitText}>Save</Text>
                          </Pressable>
                          <Pressable
                            onPress={() => setEditRow(null)}
                            style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                            accessibilityRole="button"
                          >
                            <Text style={styles.actionText}>Cancel</Text>
                          </Pressable>
                        </View>
                      </View>
                    ) : null}
                    {records.map((r) => (
                    <DataCard
                      key={String(r.id)}
                      title={`${r.volume ?? 'Bottle'} · ${data?.variantLabelMap?.[String(r.id)] ?? r.variant ?? 'plain'}`}
                      lines={[`Qty: ${r.quantity ?? 0}`]}
                      badge={String(r.quantity ?? 0)}
                      badgeTone={(r.quantity ?? 0) > 0 ? 'success' : 'danger'}
                    >
                      <View style={styles.rowActions}>
                        <Pressable
                          onPress={() => {
                            setEditRow(r);
                            setEditQty(String(r.quantity ?? 0));
                          }}
                          style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                          accessibilityRole="button"
                        >
                          <Text style={styles.actionText}>Edit qty</Text>
                        </Pressable>
                        <Pressable
                          onPress={() => setPendingDelete(r)}
                          style={({ pressed }) => [styles.actionBtn, styles.actionDanger, pressed && styles.pressed]}
                          accessibilityRole="button"
                        >
                          <Text style={[styles.actionText, { color: COLORS.danger }]}>Delete</Text>
                        </Pressable>
                      </View>
                    </DataCard>
                    ))}
                  </>
                )}
              </>
            )
          ) : null}

          {mode === 'in' ? (
            <>
              <GroupLabel>Stock in bottles</GroupLabel>
              <ChipRow>
                {(data?.volumes ?? ['6ml', '12ml', '30ml', '50ml', '100ml']).map((v) => (
                  <Chip key={v} label={v} active={volume === v} onPress={() => setVolume(v)} />
                ))}
              </ChipRow>
              <AuthField label="Quantity" value={quantity} onChangeText={setQuantity} placeholder="e.g. 100" icon="layers-outline" keyboardType="number-pad" error={fieldErrors.quantity} />
              <AuthField label="Reason (optional)" value={reason} onChangeText={setReason} placeholder="Delivery, purchase…" icon="document-text-outline" error={fieldErrors.reason} />
              {[30, 50, 100].includes(Number(volume)) ? (
                <>
                  <Text style={styles.label}>Has box?</Text>
                  <ChipRow>
                    <Chip label="With box" active={hasBox === 'yes'} onPress={() => setHasBox('yes')} />
                    <Chip label="Without box" active={hasBox === 'no'} onPress={() => setHasBox('no')} />
                  </ChipRow>
                  {fieldErrors.has_box ? <Banner kind="error" message={fieldErrors.has_box} /> : null}
                  {hasBox === 'yes' ? (
                    <>
                      <Text style={styles.label}>Has logo?</Text>
                      <ChipRow>
                        <Chip label="With logo" active={hasLogo === 'yes'} onPress={() => setHasLogo('yes')} />
                        <Chip label="No logo" active={hasLogo === 'no'} onPress={() => setHasLogo('no')} />
                      </ChipRow>
                      {fieldErrors.has_logo ? <Banner kind="error" message={fieldErrors.has_logo} /> : null}
                      {hasLogo ? (
                        <>
                          <Text style={styles.label}>{hasLogo === 'yes' ? 'Logo color' : 'Color'}</Text>
                          <ChipRow>
                            {(hasLogo === 'yes' ? ['yellow', 'black'] : ['black', 'white']).map((c) => (
                              <Chip key={c} label={c} active={logoColor === c} onPress={() => setLogoColor(c)} />
                            ))}
                          </ChipRow>
                          {fieldErrors.logo_color ? <Banner kind="error" message={fieldErrors.logo_color} /> : null}
                        </>
                      ) : null}
                    </>
                  ) : null}
                </>
              ) : null}
              <Pressable
                style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, !quantity && styles.submitDisabled]}
                disabled={!quantity}
                onPress={() =>
                  run(
                    () =>
                      addBottleStock({
                        volume,
                        quantity: Number(quantity),
                        reason: reason || undefined,
                        has_box: hasBox || undefined,
                        has_logo: hasLogo || undefined,
                        logo_color: (logoColor as 'yellow' | 'black' | 'white') || undefined,
                      }),
                    () => {
                      setQuantity('');
                      setReason('');
                      setMode('list');
                    },
                  )
                }
                accessibilityRole="button"
              >
                <Text style={styles.submitText}>Add bottles</Text>
              </Pressable>
            </>
          ) : null}

          {mode === 'broken' ? (
            <>
              <GroupLabel>Record broken bottles</GroupLabel>
              <ChipRow>
                {(data?.volumes ?? ['6ml', '12ml', '30ml', '50ml', '100ml']).map((v) => (
                  <Chip key={v} label={v} active={brokenVolume === v} onPress={() => setBrokenVolume(v)} />
                ))}
              </ChipRow>
              <AuthField label="Quantity" value={brokenQty} onChangeText={setBrokenQty} placeholder="e.g. 5" icon="layers-outline" keyboardType="number-pad" error={fieldErrors.quantity} />
              <AuthField label="Reason (optional)" value={brokenReason} onChangeText={setBrokenReason} placeholder="Broken in transit…" icon="document-text-outline" />
              <AuthField label="Variety key (optional, e.g. box-logo-yellow)" value={brokenVariant} onChangeText={setBrokenVariant} placeholder="plain" icon="color-filter-outline" />
              <Pressable
                style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, !brokenQty && styles.submitDisabled]}
                disabled={!brokenQty}
                onPress={() =>
                  run(
                    () =>
                      recordBrokenBottles({
                        volume: brokenVolume,
                        quantity: Number(brokenQty),
                        reason: brokenReason || undefined,
                        variant: brokenVariant || undefined,
                      }),
                    () => {
                      setBrokenQty('');
                      setBrokenReason('');
                      setMode('list');
                    },
                  )
                }
                accessibilityRole="button"
              >
                <Text style={styles.submitText}>Record broken</Text>
              </Pressable>
            </>
          ) : null}
        </>
      )}

      <ConfirmDialog
        visible={pendingDelete !== null}
        title="Delete bottle record?"
        message={`${pendingDelete?.volume ?? ''} (${pendingDelete?.quantity ?? 0} units) will be removed from this branch.`}
        confirmLabel="Delete"
        danger
        loading={busy}
        onCancel={() => setPendingDelete(null)}
        onConfirm={async () => {
          if (!pendingDelete) return;
          const row = pendingDelete;
          setBusy(true);
          try {
            await deleteBottleStock(row.id);
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
  inlineEdit: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.md,
    padding: 14,
    gap: 6,
  },
  inlineEditTitle: { color: SM_ACCENT.light, fontSize: 14, fontWeight: '800' },
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
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
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
