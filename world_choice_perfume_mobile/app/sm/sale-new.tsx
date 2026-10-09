import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, StatGrid, StatTile, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { createSale, fetchSaleOptions, type SmSaleFields, type SmVarietyBuckets } from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

interface Line {
  key: string;
  product_id: string;
  name: string;
  quantity: string;
  price: string;
  volume?: number;
  variant?: string;
  variantLabel?: string;
}

/**
 * New Sale — the mobile twin of sales/create + POST /stock-manager/sales.
 * Products, picked bottlings (volume/variety with availability), retail
 * discounts / wholesale prices, customer and split payments all mirror the
 * website checkout; the server re-validates everything (stock, variety
 * buckets, payment totals) and answers 422 with the website's messages.
 *
 * Known scope note: empty-bottle sale lines are not offered here yet (the
 * website supports them); they remain a website-only action.
 */
export default function SmSaleNew() {
  const { data, error, loading, reload } = useAsyncData(() => fetchSaleOptions(), []);

  const [saleType, setSaleType] = useState<'retail' | 'wholesale'>('retail');
  const [lines, setLines] = useState<Line[]>([]);
  const [productSearch, setProductSearch] = useState('');

  const [pickedProductId, setPickedProductId] = useState('');
  const [pickedVolume, setPickedVolume] = useState<number | undefined>(undefined);
  const [pickedVariant, setPickedVariant] = useState<string | undefined>(undefined);
  const [lineQty, setLineQty] = useState('');
  const [linePrice, setLinePrice] = useState('');

  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('');
  const [paymentMode, setPaymentMode] = useState<'single' | 'multi'>('single');
  const [method, setMethod] = useState<'cash' | 'bank_transfer' | 'mobile_payment'>('cash');
  const [cashAmount, setCashAmount] = useState('');
  const [bankAmount, setBankAmount] = useState('');
  const [mobileAmount, setMobileAmount] = useState('');

  const [busy, setBusy] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const buckets: SmVarietyBuckets = data?.productVarieties ?? {};
  const products = useMemo(() => data?.products ?? [], [data]);

  const filtered = useMemo(() => {
    const q = productSearch.trim().toLowerCase();
    const list = products.filter((p) => (p.quantity ?? 0) > 0);
    if (!q) return list;
    return list.filter(
      (p) => (p.product?.name ?? '').toLowerCase().includes(q) || (p.product?.brand ?? '').toLowerCase().includes(q),
    );
  }, [products, productSearch]);

  const pickedProduct = products.find((p) => String(p.product_id) === pickedProductId) ?? null;
  const pickedBuckets = pickedProductId ? (buckets[pickedProductId] ?? []) : [];
  const pickedBucket = pickedBuckets.find((b) => b.volume === pickedVolume) ?? null;
  const pickedVariantRow = pickedBucket?.variants.find((v) => v.key === pickedVariant) ?? null;

  const addLine = () => {
    if (!pickedProductId || !lineQty) return;
    if (pickedBuckets.length > 0 && (!pickedVolume || !pickedVariant)) return;
    setLines((prev) => [
      ...prev,
      {
        key: `${pickedProductId}-${pickedVolume ?? 0}-${pickedVariant ?? 'x'}-${prev.length}`,
        product_id: pickedProductId,
        name: pickedProduct?.product?.name ?? `Product #${pickedProductId}`,
        quantity: lineQty,
        price: linePrice,
        volume: pickedBuckets.length > 0 ? pickedVolume : undefined,
        variant: pickedBuckets.length > 0 ? pickedVariant : undefined,
        variantLabel: pickedVariantRow?.label,
      },
    ]);
    setPickedProductId('');
    setPickedVolume(undefined);
    setPickedVariant(undefined);
    setLineQty('');
    setLinePrice('');
  };

  const subtotal = lines.reduce((sum, l) => {
    const price =
      Number(l.price || 0) ||
      (l.variant
        ? (buckets[l.product_id]?.find((b) => b.volume === l.volume)?.variants.find((v) => v.key === l.variant)?.price ??
          products.find((p) => String(p.product_id) === l.product_id)?.selling_price ??
          0)
        : products.find((p) => String(p.product_id) === l.product_id)?.selling_price ??
          0);
    return sum + Number(l.quantity || 0) * price;
  }, 0);

  const payments: SmSaleFields['payments'] =
    paymentMode === 'multi'
      ? ([
          cashAmount ? { method: 'cash', amount: Number(cashAmount) } : null,
          bankAmount ? { method: 'bank_transfer', amount: Number(bankAmount) } : null,
          mobileAmount ? { method: 'mobile_payment', amount: Number(mobileAmount) } : null,
        ].filter(Boolean) as SmSaleFields['payments'])
      : [{ method }];

  const paymentTotal = payments.reduce((sum, p) => sum + Number(p.amount ?? (paymentMode === 'single' ? subtotal : 0)), 0);

  const canSubmit =
    lines.length > 0 && payments.length > 0 && (paymentMode === 'single' || Math.abs(paymentTotal - subtotal) < 0.01);

  const submit = async () => {
    if (busy || !canSubmit) return;
    setBusy(true);
    setSubmitError(null);
    try {
      const fields: SmSaleFields = {
        customer_name: customerName || undefined,
        customer_phone: customerPhone || undefined,
        payment_mode: paymentMode,
        payments,
        sale_type: saleType,
        items: lines.map((l) => ({
          product_id: l.product_id,
          quantity: Number(l.quantity),
          custom_price: saleType === 'wholesale' && l.price ? Number(l.price) : undefined,
          discount_price: saleType === 'retail' && l.price ? Number(l.price) : undefined,
          volume: l.volume ?? null,
          variant: l.variant ?? null,
        })),
      };
      await createSale(fields);
      router.back();
    } catch (e) {
      setSubmitError(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  return (
    <AdminPage title="New Sale" eyebrow="Stock Manager" onMenu={smMenu.open} accent={SM_ACCENT.main} onBack={() => router.back()}>
      {error ? <Banner kind="error" message={error} /> : null}
      {submitError ? <Banner kind="error" message={submitError} /> : null}

      {loading ? (
        <LoadingView label="Loading sale form…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : (
        <>
          <GroupLabel>Sale type</GroupLabel>
          <ChipRow>
            <Chip label="Retail" active={saleType === 'retail'} onPress={() => setSaleType('retail')} />
            <Chip label="Wholesale" active={saleType === 'wholesale'} onPress={() => setSaleType('wholesale')} />
          </ChipRow>

          <GroupLabel>Customer (optional)</GroupLabel>
          <AuthField label="Name" value={customerName} onChangeText={setCustomerName} placeholder="Walk-in customer" icon="person-outline" />
          <AuthField label="Phone" value={customerPhone} onChangeText={setCustomerPhone} placeholder="07…" icon="call-outline" keyboardType="phone-pad" />

          <GroupLabel>Add product</GroupLabel>
          <AuthField label="Search" value={productSearch} onChangeText={setProductSearch} placeholder="Product or brand…" icon="search-outline" />
          <ChipRow>
            {filtered.slice(0, 30).map((p) => (
              <Chip
                key={String(p.product_id)}
                label={`${p.product?.name ?? 'Product'} (${p.quantity})`}
                active={pickedProductId === String(p.product_id)}
                onPress={() => {
                  setPickedProductId(String(p.product_id));
                  setPickedVolume(undefined);
                  setPickedVariant(undefined);
                }}
              />
            ))}
          </ChipRow>
          {filtered.length === 0 ? <EmptyView icon="cube-outline" title="No products in stock" hint="Stock in first." /> : null}

          {pickedBuckets.length > 0 ? (
            <>
              <Text style={styles.label}>Bottle volume (this product is bottled)</Text>
              <ChipRow>
                {pickedBuckets.map((b) => (
                  <Chip
                    key={b.volume}
                    label={b.label}
                    active={pickedVolume === b.volume}
                    onPress={() => {
                      setPickedVolume(b.volume);
                      setPickedVariant(undefined);
                    }}
                  />
                ))}
              </ChipRow>
              {pickedBucket ? (
                <>
                  <Text style={styles.label}>Variety</Text>
                  <ChipRow>
                    {pickedBucket.variants.map((v) => (
                      <Chip
                        key={v.key}
                        label={`${v.label} (${v.available})`}
                        active={pickedVariant === v.key}
                        onPress={() => setPickedVariant(v.key)}
                      />
                    ))}
                  </ChipRow>
                </>
              ) : null}
            </>
          ) : null}

          <View style={styles.lineRow}>
            <View style={{ flex: 1 }}>
              <AuthField
                label="Quantity"
                value={lineQty}
                onChangeText={setLineQty}
                placeholder="1"
                icon="layers-outline"
                keyboardType="number-pad"
              />
            </View>
            <View style={{ flex: 1 }}>
              <AuthField
                label={saleType === 'retail' ? 'Discount price' : 'Custom price'}
                value={linePrice}
                onChangeText={setLinePrice}
                placeholder="default"
                icon="cash-outline"
                keyboardType="numeric"
              />
            </View>
          </View>
          <Pressable
            onPress={addLine}
            style={({ pressed }) => [styles.addBtn, pressed && styles.pressed, (!pickedProductId || !lineQty) && styles.disabled]}
            disabled={!pickedProductId || !lineQty}
            accessibilityRole="button"
          >
            <Text style={styles.addBtnText}>Add to sale</Text>
          </Pressable>

          {lines.length > 0 ? (
            <>
              <GroupLabel>Sale items</GroupLabel>
              {lines.map((l, i) => (
                <View key={l.key} style={styles.cartLine}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.cartName}>
                      {l.name} × {l.quantity}
                    </Text>
                    <Text style={styles.cartMeta}>
                      {l.variantLabel ? `${l.volume}ml · ${l.variantLabel}` : 'Standard line'}
                      {l.price ? ` · price ${l.price}` : ''}
                    </Text>
                  </View>
                  <Pressable
                    onPress={() => setLines((prev) => prev.filter((_, j) => j !== i))}
                    hitSlop={8}
                    accessibilityLabel="Remove line"
                  >
                    <Text style={styles.remove}>Remove</Text>
                  </Pressable>
                </View>
              ))}
            </>
          ) : null}

          <GroupLabel>Payment</GroupLabel>
          <ChipRow>
            <Chip label="Single payment" active={paymentMode === 'single'} onPress={() => setPaymentMode('single')} />
            <Chip label="Split payments" active={paymentMode === 'multi'} onPress={() => setPaymentMode('multi')} />
          </ChipRow>
          {paymentMode === 'single' ? (
            <ChipRow>
              <Chip label="Cash" active={method === 'cash'} onPress={() => setMethod('cash')} />
              <Chip label="Bank transfer" active={method === 'bank_transfer'} onPress={() => setMethod('bank_transfer')} />
              <Chip label="Mobile money" active={method === 'mobile_payment'} onPress={() => setMethod('mobile_payment')} />
            </ChipRow>
          ) : (
            <>
              <AuthField label="Cash amount" value={cashAmount} onChangeText={setCashAmount} placeholder="0" icon="cash-outline" keyboardType="numeric" />
              <AuthField label="Bank transfer amount" value={bankAmount} onChangeText={setBankAmount} placeholder="0" icon="business-outline" keyboardType="numeric" />
              <AuthField label="Mobile money amount" value={mobileAmount} onChangeText={setMobileAmount} placeholder="0" icon="phone-portrait-outline" keyboardType="numeric" />
              {paymentMode === 'multi' && Math.abs(paymentTotal - subtotal) >= 0.01 && lines.length > 0 ? (
                <Banner kind="error" message={`Payment breakdown (${formatMoney(paymentTotal)}) must equal the sale total (${formatMoney(subtotal)}).`} />
              ) : null}
            </>
          )}

          <StatGrid>
            <StatTile label="Lines" value={lines.length} />
            <StatTile label="Total" value={formatMoney(subtotal)} tone="success" />
          </StatGrid>

          <Pressable
            onPress={submit}
            style={({ pressed }) => [styles.submitBtn, pressed && styles.pressed, !canSubmit && styles.disabled]}
            disabled={!canSubmit}
            accessibilityRole="button"
          >
            <Text style={styles.submitText}>Complete sale</Text>
          </Pressable>
        </>
      )}

      <BusyOverlay visible={busy} label="Recording sale…" />
    </AdminPage>
  );
}

function formatMoney(value: number): string {
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
}

const styles = StyleSheet.create({
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
  lineRow: { flexDirection: 'row', gap: 10 },
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
  cartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 12,
  },
  cartName: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
  cartMeta: { color: COLORS.textMuted, fontSize: 12, marginTop: 2 },
  remove: { color: COLORS.danger, fontSize: 12.5, fontWeight: '700' },
  submitBtn: {
    backgroundColor: SM_ACCENT.main,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    paddingVertical: 15,
    marginTop: 6,
  },
  submitText: { color: '#052E1B', fontSize: 15.5, fontWeight: '800' },
});
