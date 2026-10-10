import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import {
  deleteOilStock,
  fetchOilOptions,
  fetchOilStock,
  oilStockIn,
  oilStockOut,
  updateOilStock,
  type SmOilRecord,
} from '../../lib/smApi';
import { BUTTON, COLORS, RADIUS, SM_ACCENT } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

type Mode = 'list' | 'in' | 'out';

/**
 * Oil Fragrance Stock — mobile twin of oil-fragrance-stock.blade.php. Rows
 * are name+volume pairs (Reef 33 500ml and 1000ml are independent), stock-out
 * deducts only the chosen volume, and products-only branches get the server's
 * 403 rendered as an explanatory state.
 */
export default function SmOilFragrance() {
  const { data, error, loading, refreshing, reload, refresh } = useAsyncData(() => fetchOilStock(), []);
  const { data: options } = useAsyncData(() => fetchOilOptions(), []);

  const [mode, setMode] = useState<Mode>('list');
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [pendingDelete, setPendingDelete] = useState<SmOilRecord | null>(null);
  const [editRow, setEditRow] = useState<SmOilRecord | null>(null);
  const [editQty, setEditQty] = useState('');

  const [productId, setProductId] = useState('');
  const [qty, setQty] = useState('');
  const [bottleVolume, setBottleVolume] = useState<'500' | '1000'>('500');
  const [reason, setReason] = useState('');

  const forbidden = (error ?? '').includes('not available for your branch');
  const oils = data?.oils ?? [];
  const products = options?.oilProducts ?? [];

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

  const availableFor = (): number => {
    const p = products.find((x) => String(x.id) === productId);
    if (!p) return 0;
    return options?.stockByProductVolume?.[`${p.name}_${bottleVolume}`] ?? 0;
  };

  return (
    <AdminPage
      title="Oil Fragrance"
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
            <Chip label="Stock out" active={mode === 'out'} onPress={() => setMode('out')} />
          </ChipRow>

          {actionError || (error && !forbidden) ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

          {mode === 'list' ? (
            loading ? (
              <LoadingView label="Loading oils…" />
            ) : error && !data ? (
              <ErrorView message={error} onRetry={reload} />
            ) : (
              <>
                <StatGrid>
                  <StatTile label="Rows" value={oils.length} />
                  <StatTile label="Total bottles" value={data?.totalQuantity ?? 0} tone="gold" />
                </StatGrid>

                <GroupLabel>Stock by fragrance &amp; volume</GroupLabel>
                {oils.length === 0 ? (
                  <EmptyView icon="flask-outline" title="No oil fragrance stock" hint="Use Stock in to record bulk oil." />
                ) : (
                  oils.map((o) => (
                    <DataCard
                      key={String(o.id)}
                      title={`${o.name ?? 'Oil'}${o.volume ? ` · ${o.volume}ml` : ''}`}
                      lines={[`Qty: ${o.quantity ?? 0}`]}
                      badge={String(o.quantity ?? 0)}
                      badgeTone={(o.quantity ?? 0) > 0 ? 'success' : 'danger'}
                    >
                      <View style={styles.rowActions}>
                        <Pressable
                          onPress={() => {
                            setEditRow(o);
                            setEditQty(String(o.quantity ?? 0));
                          }}
                          style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                          accessibilityRole="button"
                        >
                          <Text style={styles.actionText}>Edit qty</Text>
                        </Pressable>
                        <Pressable
                          onPress={() => setPendingDelete(o)}
                          style={({ pressed }) => [styles.actionBtn, styles.actionDanger, pressed && styles.pressed]}
                          accessibilityRole="button"
                        >
                          <Text style={[styles.actionText, { color: COLORS.danger }]}>Delete</Text>
                        </Pressable>
                      </View>
                    </DataCard>
                  ))
                )}
              </>
            )
          ) : null}

          {mode === 'in' || mode === 'out' ? (
            <>
              <GroupLabel>{mode === 'in' ? 'Stock in bulk oil' : 'Stock out (production)'}</GroupLabel>
              <Text style={styles.label}>Fragrance product</Text>
              <ChipRow>
                {products.map((p) => (
                  <Chip key={String(p.id)} label={p.name} active={productId === String(p.id)} onPress={() => setProductId(String(p.id))} />
                ))}
              </ChipRow>
              {fieldErrors.product_id ? <Banner kind="error" message={fieldErrors.product_id} /> : null}

              <Text style={styles.label}>Bottle volume</Text>
              <ChipRow>
                <Chip label="500ml" active={bottleVolume === '500'} onPress={() => setBottleVolume('500')} />
                <Chip label="1000ml" active={bottleVolume === '1000'} onPress={() => setBottleVolume('1000')} />
              </ChipRow>

              <AuthField
                label="Quantity"
                value={qty}
                onChangeText={setQty}
                placeholder="e.g. 10"
                icon="layers-outline"
                keyboardType="number-pad"
                error={fieldErrors.quantity}
              />
              <AuthField
                label="Reason (optional)"
                value={reason}
                onChangeText={setReason}
                placeholder={mode === 'in' ? 'Delivery…' : 'Used for production…'}
                icon="document-text-outline"
              />

              {mode === 'out' && productId ? (
                <Text style={styles.availability}>Available {bottleVolume}ml: {availableFor()}</Text>
              ) : null}

              <Pressable
                style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, (!productId || !qty) && styles.submitDisabled]}
                disabled={!productId || !qty}
                onPress={() => {
                  const fields = {
                    product_id: productId,
                    quantity: Number(qty),
                    bottle_volume: Number(bottleVolume) as 500 | 1000,
                    reason: reason || undefined,
                  };
                  run(mode === 'in' ? () => oilStockIn(fields) : () => oilStockOut(fields), () => {
                    setQty('');
                    setReason('');
                    setMode('list');
                  });
                }}
                accessibilityRole="button"
              >
                <Text style={styles.submitText}>{mode === 'in' ? 'Add stock' : 'Record stock out'}</Text>
              </Pressable>
            </>
          ) : null}

          {editRow ? (
            <View style={styles.inlineEdit}>
              <Text style={styles.inlineEditTitle}>
                Edit {editRow.name ?? 'oil'}{editRow.volume ? ` · ${editRow.volume}ml` : ''}
              </Text>
              <AuthField label="Quantity" value={editQty} onChangeText={setEditQty} placeholder="0" icon="layers-outline" keyboardType="number-pad" />
              <View style={styles.rowActions}>
                <Pressable
                  style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, !editQty && styles.submitDisabled]}
                  disabled={!editQty}
                  onPress={() =>
                    run(
                      () => updateOilStock(editRow.id, { quantity: Number(editQty) }),
                      () => setEditRow(null),
                    )
                  }
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
        title="Delete oil stock record?"
        message={`${pendingDelete?.name ?? ''}${pendingDelete?.volume ? ` ${pendingDelete.volume}ml` : ''} (${pendingDelete?.quantity ?? 0} bottles) will be removed.`}
        confirmLabel="Delete"
        danger
        loading={busy}
        onCancel={() => setPendingDelete(null)}
        onConfirm={async () => {
          if (!pendingDelete) return;
          const row = pendingDelete;
          setBusy(true);
          try {
            await deleteOilStock(row.id);
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
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
  availability: { color: SM_ACCENT.light, fontSize: 12.5, fontWeight: '600', marginTop: 4 },
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
    backgroundColor: BUTTON.fill,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    paddingVertical: 14,
    marginTop: 10,
  },
  submitDisabled: { opacity: 0.4 },
  submitText: { color: BUTTON.text, fontSize: 15, fontWeight: '800' },
});
