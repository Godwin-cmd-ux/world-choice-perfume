import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, useAsyncData, messageOf } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import { createTransfer, fetchSmScope, fetchTransferForm, type SmTransferItemInput, type SmTransferType } from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

/**
 * New transfer — mobile twin of stock-transfers/create. Target branches,
 * branch stock options and per-product variety buckets all come from the
 * server; a products-only branch only offers the Product Stock type (its
 * bottle transfer attempts are refused with the website's 403).
 */
export default function SmTransferNew() {
  const { data: scope } = useAsyncData(() => fetchSmScope(), []);
  const productsOnly = scope?.is_products_only ?? false;

  const [type, setType] = useState<SmTransferType>('product');
  const { data, error, loading } = useAsyncData(() => fetchTransferForm(type), [type]);

  const [toBranchId, setToBranchId] = useState('');
  const [officerName, setOfficerName] = useState('');
  const [officerPhone, setOfficerPhone] = useState('');
  const [officerId, setOfficerId] = useState('');
  const [note, setNote] = useState('');
  const [items, setItems] = useState<SmTransferItemInput[]>([]);

  const [pickProductId, setPickProductId] = useState('');
  const [pickVolume, setPickVolume] = useState<number | undefined>(undefined);
  const [pickVariant, setPickVariant] = useState<string | undefined>(undefined);
  const [pickQty, setPickQty] = useState('');
  const [pickOilName, setPickOilName] = useState('');
  const [pickOilVolume, setPickOilVolume] = useState('');
  const [pickAccessoryType, setPickAccessoryType] = useState('');
  const [pickAccessoryColor, setPickAccessoryColor] = useState('');
  const [pickBottleVolume, setPickBottleVolume] = useState('');
  const [pickBottleVariant] = useState('');

  const [busy, setBusy] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const typeOptions = useMemo(() => {
    const all: { key: SmTransferType; label: string }[] = [
      { key: 'product', label: 'Product Stock' },
      { key: 'bottle', label: 'Bottle Stock' },
      { key: 'oil_fragrance', label: 'Oil Fragrance' },
      { key: 'bottle_accessories', label: 'Accessories' },
    ];
    return productsOnly ? all.slice(0, 1) : all;
  }, [productsOnly]);

  const options = (data?.options ?? []) as Record<string, unknown>[];
  const productVarieties = data?.productVarieties ?? {};
  const pickedProductVarieties = pickProductId ? (productVarieties[pickProductId] ?? []) : [];

  const addItem = () => {
    if (type === 'product') {
      if (!pickProductId || !pickQty) return;
      if (pickedProductVarieties.length > 0 && (!pickVolume || !pickVariant)) return;
      setItems((prev) => [
        ...prev,
        { product_id: pickProductId, quantity: Number(pickQty), volume: pickVolume, variant: pickVariant },
      ]);
      setPickProductId('');
      setPickVolume(undefined);
      setPickVariant(undefined);
    } else if (type === 'bottle') {
      if (!pickBottleVolume || !pickQty) return;
      setItems((prev) => [...prev, { volume: pickBottleVolume, variant: pickBottleVariant || 'plain', quantity: Number(pickQty) }]);
    } else if (type === 'oil_fragrance') {
      if (!pickOilName || !pickQty) return;
      setItems((prev) => [...prev, { name: pickOilName, volume: pickOilVolume, quantity: Number(pickQty) }]);
    } else {
      if (!pickAccessoryType || !pickAccessoryColor || !pickQty) return;
      setItems((prev) => [...prev, { type: pickAccessoryType, color: pickAccessoryColor, quantity: Number(pickQty) }]);
    }
    setPickQty('');
  };

  const canSubmit = Boolean(toBranchId && officerName && officerPhone && items.length > 0);

  const submit = async () => {
    if (busy || !canSubmit) return;
    setBusy(true);
    setSubmitError(null);
    try {
      await createTransfer({
        type,
        to_branch_id: Number(toBranchId),
        officer_name: officerName,
        officer_phone: officerPhone,
        officer_id: officerId || undefined,
        note: note || undefined,
        items,
      });
      router.back();
    } catch (e) {
      setSubmitError(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  return (
    <AdminPage title="New Transfer" eyebrow="Stock Manager" onMenu={smMenu.open} accent={SM_ACCENT.main} onBack={() => router.back()}>
      {error || submitError ? <Banner kind="error" message={submitError ?? error ?? ''} /> : null}

      <GroupLabel>Stock type</GroupLabel>
      <ChipRow>
        {typeOptions.map((t) => (
          <Chip
            key={t.key}
            label={t.label}
            active={type === t.key}
            onPress={() => {
              setType(t.key);
              setItems([]);
            }}
          />
        ))}
      </ChipRow>
      {productsOnly ? (
        <Text style={styles.hint}>Products-only branch: Product Stock transfers only.</Text>
      ) : null}

      <GroupLabel>Destination</GroupLabel>
      <ChipRow>
        {(data?.branches ?? []).map((b) => (
          <Chip key={String(b.id)} label={b.name} active={toBranchId === String(b.id)} onPress={() => setToBranchId(String(b.id))} />
        ))}
      </ChipRow>

      <GroupLabel>Transport officer</GroupLabel>
      <AuthField label="Officer name" value={officerName} onChangeText={setOfficerName} placeholder="Who carries the stock?" icon="person-outline" autoCapitalize="words" />
      <AuthField label="Officer phone" value={officerPhone} onChangeText={setOfficerPhone} placeholder="07…" icon="call-outline" keyboardType="phone-pad" />
      <AuthField label="Officer ID (optional)" value={officerId} onChangeText={setOfficerId} placeholder="NIDA / badge" icon="card-outline" />
      <AuthField label="Note (optional)" value={note} onChangeText={setNote} placeholder="Anything the receiver should know" icon="document-text-outline" autoCapitalize="sentences" />

      <GroupLabel right={<Text style={styles.count}>{items.length} item(s)</Text>}>Items</GroupLabel>

      {loading ? (
        <Text style={styles.hint}>Loading options…</Text>
      ) : type === 'product' ? (
        <>
          <ChipRow>
            {options.slice(0, 40).map((o) => (
              <Chip
                key={String(o.product_id)}
                label={`${String(o.name)} (${String(o.available)})`}
                active={pickProductId === String(o.product_id)}
                onPress={() => {
                  setPickProductId(String(o.product_id));
                  setPickVolume(undefined);
                  setPickVariant(undefined);
                }}
              />
            ))}
          </ChipRow>
          {pickedProductVarieties.length > 0 ? (
            <>
              <Text style={styles.label}>Bottling (this product has varieties)</Text>
              <ChipRow>
                {pickedProductVarieties.map((b) => (
                  <Chip
                    key={b.volume}
                    label={b.label}
                    active={pickVolume === b.volume}
                    onPress={() => {
                      setPickVolume(b.volume);
                      setPickVariant(undefined);
                    }}
                  />
                ))}
              </ChipRow>
              {pickedProductVarieties
                .find((b) => b.volume === pickVolume)
                ?.variants.map((v) => (
                  <Chip
                    key={v.key}
                    label={`${v.label} (${v.available})`}
                    active={pickVariant === v.key}
                    onPress={() => setPickVariant(v.key)}
                  />
                ))}
            </>
          ) : null}
        </>
      ) : type === 'bottle' ? (
        <ChipRow>
          {(data?.volumes ?? []).map((v) => (
            <Chip key={v} label={v} active={pickBottleVolume === v} onPress={() => setPickBottleVolume(v)} />
          ))}
        </ChipRow>
      ) : type === 'oil_fragrance' ? (
        <ChipRow>
          {options.map((o, i) => (
            <Chip
              key={i}
              label={`${String(o.name)}${o.volume ? ` ${String(o.volume)}ml` : ''} (${String(o.available)})`}
              active={pickOilName === String(o.name) && pickOilVolume === String(o.volume ?? '')}
              onPress={() => {
                setPickOilName(String(o.name));
                setPickOilVolume(String(o.volume ?? ''));
              }}
            />
          ))}
        </ChipRow>
      ) : (
        <>
          <ChipRow>
            {options.map((o, i) => (
              <Chip
                key={i}
                label={`${String(o.type)} · ${String(o.color)} (${String(o.available)})`}
                active={pickAccessoryType === String(o.type) && pickAccessoryColor === String(o.color)}
                onPress={() => {
                  setPickAccessoryType(String(o.type));
                  setPickAccessoryColor(String(o.color));
                }}
              />
            ))}
          </ChipRow>
        </>
      )}

      <View style={styles.qtyRow}>
        <View style={{ flex: 1 }}>
          <AuthField label="Quantity" value={pickQty} onChangeText={setPickQty} placeholder="1" icon="layers-outline" keyboardType="number-pad" />
        </View>
      </View>
      <Pressable
        onPress={addItem}
        style={({ pressed }) => [styles.addBtn, pressed && styles.pressed, !pickQty && styles.disabled]}
        disabled={!pickQty}
        accessibilityRole="button"
      >
        <Text style={styles.addBtnText}>Add item</Text>
      </Pressable>

      {items.map((it, i) => (
        <View key={i} style={styles.itemRow}>
          <Text style={styles.itemText} numberOfLines={2}>
            {summariseItem(it)}
          </Text>
          <Pressable onPress={() => setItems((prev) => prev.filter((_, j) => j !== i))} hitSlop={8} accessibilityLabel="Remove item">
            <Text style={styles.remove}>Remove</Text>
          </Pressable>
        </View>
      ))}

      <GoldButton
        label="Send transfer"
        icon="paper-plane-outline"
        loading={busy}
        disabled={!canSubmit}
        onPress={submit}
        style={{ marginTop: 10 }}
      />

      <BusyOverlay visible={busy} label="Creating transfer…" />
    </AdminPage>
  );
}

function summariseItem(it: SmTransferItemInput): string {
  if (it.product_id != null) {
    return `Product #${it.product_id} × ${it.quantity}${it.volume ? ` · ${it.volume}ml ${it.variant ?? ''}` : ''}`;
  }
  if (it.volume != null && it.name == null && it.type == null) {
    return `${it.volume} (${it.variant ?? 'plain'}) × ${it.quantity}`;
  }
  if (it.name != null) {
    return `${it.name}${it.volume ? ` ${it.volume}ml` : ''} × ${it.quantity}`;
  }
  return `${it.type ?? ''} ${it.color ?? ''} × ${it.quantity}`;
}

const styles = StyleSheet.create({
  hint: { color: COLORS.textMuted, fontSize: 12.5 },
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
  count: { color: SM_ACCENT.light, fontSize: 12, fontWeight: '800' },
  qtyRow: { flexDirection: 'row' },
  addBtn: {
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    paddingVertical: 11,
  },
  addBtnText: { color: SM_ACCENT.light, fontWeight: '800', fontSize: 14 },
  disabled: { opacity: 0.4 },
  pressed: { opacity: 0.7 },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 12,
    marginTop: 8,
  },
  itemText: { color: COLORS.text, fontSize: 13.5, flex: 1 },
  remove: { color: COLORS.danger, fontSize: 12.5, fontWeight: '700' },
});
