import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, useAsyncData } from '../../components/adminkit';
import { EmptyView, GoldButton, LoadingView, ErrorView } from '../../components/ui';
import { createSellerSale, fetchSellerSaleOptions, type SellerEmptyBottleLine, type SellerSaleItemInput } from '../../lib/sellerApi';
import { formatMoney } from '../../lib/format';
import { COLORS, RADIUS, SELLER_ACCENT } from '../../lib/theme';
import { sellerMenu } from '../../components/sellersidebar';

interface Line {
  key: string;
  kind: 'product' | 'bottle';
  product_id?: string;
  name: string;
  quantity: string;
  price: string;
  volume?: number;
  variant?: string;
}

/**
 * New Sale — the mobile twin of seller/sales/create + POST /seller/sales.
 * Products, picked bottlings (volume/variety), a custom price (the seller
 * store honours custom_price on retail and wholesale and records the
 * website's Price Customized audit), empty-bottle lines in wholesale mode,
 * customer details and split payments all mirror the website checkout; the
 * server re-validates everything and answers 422 with the website's
 * messages.
 */
export default function SellerSaleNew() {
  const { data, error, loading, reload } = useAsyncData(() => fetchSellerSaleOptions(), []);

  const [saleType, setSaleType] = useState<'retail' | 'wholesale'>('retail');
  const [lines, setLines] = useState<Line[]>([]);
  const [productSearch, setProductSearch] = useState('');

  const [pickedProductId, setPickedProductId] = useState('');
  const [pickedVolume, setPickedVolume] = useState<number | undefined>(undefined);
  const [pickedVariant, setPickedVariant] = useState<string | undefined>(undefined);
  const [lineQty, setLineQty] = useState('');
  const [linePrice, setLinePrice] = useState('');

  const [bottleVolume, setBottleVolume] = useState('');
  const [bottleVariant, setBottleVariant] = useState('');
  const [bottleQty, setBottleQty] = useState('');
  const [bottlePrice, setBottlePrice] = useState('');

  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('');
  const [paymentMode, setPaymentMode] = useState<'single' | 'multi'>('single');
  const [method, setMethod] = useState<'cash' | 'bank_transfer' | 'mobile_payment'>('cash');
  const [cashAmount, setCashAmount] = useState('');
  const [bankAmount, setBankAmount] = useState('');
  const [mobileAmount, setMobileAmount] = useState('');

  const [busy, setBusy] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const buckets = data?.productVarieties ?? {};
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
  const defaultPrice = pickedVariantRow?.price ?? pickedProduct?.selling_price ?? 0;

  const addLine = () => {
    if (!pickedProductId || !lineQty) return;
    if (pickedBuckets.length > 0 && (!pickedVolume || !pickedVariant)) return;
    setLines((prev) => [
      ...prev,
      {
        key: `${pickedProductId}-${pickedVolume ?? 0}-${pickedVariant ?? 'x'}-${prev.length}`,
        kind: 'product',
        product_id: pickedProductId,
        name: pickedProduct?.product?.name ?? `Product #${pickedProductId}`,
        quantity: lineQty,
        price: linePrice,
        volume: pickedBuckets.length > 0 ? pickedVolume : undefined,
        variant: pickedBuckets.length > 0 ? pickedVariant : undefined,
      },
    ]);
    setPickedProductId('');
    setPickedVolume(undefined);
    setPickedVariant(undefined);
    setLineQty('');
    setLinePrice('');
  };

  const addBottleLine = () => {
    if (!bottleVolume || !bottleQty || !bottlePrice) return;
    const variantMap = data?.bottleVariants[bottleVolume];
    if (variantMap && Object.keys(variantMap).length > 0 && !bottleVariant) return;
    setLines((prev) => [
      ...prev,
      {
        key: `bottle-${bottleVolume}-${bottleVariant || 'plain'}-${prev.length}`,
        kind: 'bottle',
        name: `Empty bottle ${bottleVolume}ml`,
        quantity: bottleQty,
        price: bottlePrice,
        volume: Number(bottleVolume),
        variant: variantMap && Object.keys(variantMap).length > 0 ? bottleVariant || undefined : undefined,
      },
    ]);
    setBottleVolume('');
    setBottleVariant('');
    setBottleQty('');
    setBottlePrice('');
  };

  const productLines = lines.filter((l) => l.kind === 'product');
  const bottleLines = lines.filter((l) => l.kind === 'bottle');
  const total = lines.reduce((sum, l) => sum + Number(l.quantity || 0) * Number(l.price || 0), 0);

  const paymentSum =
    Number(cashAmount || 0) + Number(bankAmount || 0) + Number(mobileAmount || 0);

  const submit = async () => {
    setBusy(true);
    setSubmitError(null);
    try {
      const items: SellerSaleItemInput[] = productLines.map((l) => ({
        product_id: l.product_id!,
        quantity: Number(l.quantity),
        custom_price: l.price ? Number(l.price) : undefined,
        volume: l.volume,
        variant: l.variant,
      }));
      const emptyBottles: SellerEmptyBottleLine[] = bottleLines.map((l) => ({
        volume: l.volume!,
        quantity: Number(l.quantity),
        price: Number(l.price),
        variant: l.variant,
      }));

      const res = await createSellerSale({
        customer_name: customerName.trim() || undefined,
        customer_phone: customerPhone.trim() || undefined,
        payment_mode: paymentMode,
        payments:
          paymentMode === 'single'
            ? [{ method }]
            : [
                ...(cashAmount ? [{ method: 'cash' as const, amount: Number(cashAmount) }] : []),
                ...(bankAmount ? [{ method: 'bank_transfer' as const, amount: Number(bankAmount) }] : []),
                ...(mobileAmount ? [{ method: 'mobile_payment' as const, amount: Number(mobileAmount) }] : []),
              ],
        items,
        empty_bottles: emptyBottles.length ? emptyBottles : undefined,
        sale_type: saleType,
      });

      const id = (res.sale as { id?: number | string } | undefined)?.id;
      router.replace({ pathname: '/seller/sale-detail', params: { id: String(id ?? ''), notice: res.message } });
    } catch (e) {
      const err = e as { message?: string };
      setSubmitError(err.message ?? 'The sale could not be completed.');
    } finally {
      setBusy(false);
    }
  };

  const canSubmit =
    productLines.length + bottleLines.length > 0 &&
    (paymentMode === 'single' || Math.abs(paymentSum - total) <= 0.01);

  return (
    <AdminPage title="New Sale" eyebrow="Seller" accent={SELLER_ACCENT.main} onBack={() => router.back()} onMenu={sellerMenu.open}>
      {error ? <ErrorView message={error} onRetry={reload} /> : null}
      {loading && !data ? <LoadingView label="Loading stock…" /> : null}
      {submitError ? <Banner kind="error" message={submitError} /> : null}

      {data ? (
        <>
          <GroupLabel>Sale type</GroupLabel>
          <ChipRow>
            <Chip label="Retail" active={saleType === 'retail'} onPress={() => setSaleType('retail')} />
            <Chip label="Wholesale" active={saleType === 'wholesale'} onPress={() => setSaleType('wholesale')} />
          </ChipRow>

          <GroupLabel>Products</GroupLabel>
          <AuthField
            label="Search products"
            value={productSearch}
            onChangeText={setProductSearch}
            placeholder="Name or brand…"
            icon="search-outline"
          />
          {filtered.length === 0 ? (
            <EmptyView icon="cube-outline" title="No products in stock" hint="Branch stock with quantity above zero only." />
          ) : (
            <View style={styles.picker}>
              {filtered.slice(0, 12).map((p) => {
                const active = String(p.product_id) === pickedProductId;
                return (
                  <Pressable
                    key={String(p.id)}
                    onPress={() => {
                      setPickedProductId(String(p.product_id));
                      setPickedVolume(undefined);
                      setPickedVariant(undefined);
                    }}
                    style={[styles.pickRow, active && styles.pickRowActive]}
                    accessibilityRole="button"
                  >
                    <Text style={[styles.pickName, active && { color: SELLER_ACCENT.light }]} numberOfLines={1}>
                      {p.product?.name ?? `Product #${p.product_id}`}
                    </Text>
                    <Text style={styles.pickMeta}>
                      {p.quantity} left · {formatMoney(p.selling_price)}
                    </Text>
                  </Pressable>
                );
              })}
            </View>
          )}

          {pickedProductId && pickedBuckets.length > 0 ? (
            <>
              <GroupLabel>Bottling</GroupLabel>
              <ChipRow>
                {pickedBuckets.map((b) => (
                  <Chip key={b.volume} label={b.label} active={pickedVolume === b.volume} onPress={() => { setPickedVolume(b.volume); setPickedVariant(undefined); }} />
                ))}
              </ChipRow>
              {pickedBucket ? (
                <ChipRow>
                  {pickedBucket.variants
                    .filter((v) => v.available > 0 || v.key === pickedVariant)
                    .map((v) => (
                      <Chip
                        key={v.key}
                        label={`${v.label} (${v.available})`}
                        active={pickedVariant === v.key}
                        onPress={() => setPickedVariant(v.key)}
                      />
                    ))}
                </ChipRow>
              ) : null}
            </>
          ) : null}

          {pickedProductId ? (
            <View style={styles.lineForm}>
              <View style={{ flex: 1 }}>
                <AuthField label="Quantity" value={lineQty} onChangeText={setLineQty} placeholder="1" icon="albums-outline" keyboardType="number-pad" />
              </View>
              <View style={{ flex: 1 }}>
                <AuthField
                  label={saleType === 'wholesale' ? 'Custom price' : 'Price (optional)'}
                  value={linePrice}
                  onChangeText={setLinePrice}
                  placeholder={formatMoney(defaultPrice)}
                  icon="pricetag-outline"
                  keyboardType="numeric"
                />
              </View>
            </View>
          ) : null}

          <GoldButton label="Add to sale" icon="add-outline" disabled={!pickedProductId || !lineQty || (pickedBuckets.length > 0 && (!pickedVolume || !pickedVariant))} onPress={addLine} />

          {saleType === 'wholesale' ? (
            <>
              <GroupLabel>Empty bottles (wholesale)</GroupLabel>
              <ChipRow>
                {Object.keys(data.bottleStock).map((vol) => (
                  <Chip key={vol} label={`${vol}ml (${data.bottleStock[vol]})`} active={bottleVolume === vol} onPress={() => { setBottleVolume(vol); setBottleVariant(''); }} />
                ))}
              </ChipRow>
              {bottleVolume && data.bottleVariants[bottleVolume] && Object.keys(data.bottleVariants[bottleVolume]).length > 0 ? (
                <ChipRow>
                  {Object.keys(data.bottleVariants[bottleVolume]).map((vk) => (
                    <Chip key={vk} label={`${vk} (${data.bottleVariants[bottleVolume][vk]})`} active={bottleVariant === vk} onPress={() => setBottleVariant(vk)} />
                  ))}
                </ChipRow>
              ) : null}
              <View style={styles.lineForm}>
                <View style={{ flex: 1 }}>
                  <AuthField label="Qty" value={bottleQty} onChangeText={setBottleQty} placeholder="1" icon="albums-outline" keyboardType="number-pad" />
                </View>
                <View style={{ flex: 1 }}>
                  <AuthField label="Price each" value={bottlePrice} onChangeText={setBottlePrice} placeholder="0" icon="pricetag-outline" keyboardType="numeric" />
                </View>
              </View>
              <GoldButton label="Add bottle line" icon="add-outline" disabled={!bottleVolume || !bottleQty || !bottlePrice} onPress={addBottleLine} />
            </>
          ) : null}

          <GroupLabel right={<Text style={styles.total}>{formatMoney(total)}</Text>}>Sale ({lines.length} lines)</GroupLabel>
          {lines.length === 0 ? (
            <EmptyView icon="cart-outline" title="Nothing added yet" hint="Pick a product and tap Add to sale." />
          ) : (
            lines.map((l) => (
              <View key={l.key} style={styles.cartRow}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.cartName} numberOfLines={1}>
                    {l.quantity}× {l.name}
                    {l.volume ? ` · ${l.volume}ml` : ''}
                    {l.variant ? ` · ${l.variant}` : ''}
                  </Text>
                  <Text style={styles.cartMeta}>{l.price ? `${formatMoney(Number(l.price))} each` : 'branch price'}</Text>
                </View>
                <Pressable onPress={() => setLines((prev) => prev.filter((x) => x.key !== l.key))} hitSlop={8} accessibilityLabel="Remove line">
                  <Text style={styles.remove}>Remove</Text>
                </Pressable>
              </View>
            ))
          )}

          <GroupLabel>Customer</GroupLabel>
          <AuthField label="Name (optional)" value={customerName} onChangeText={setCustomerName} placeholder="Walk-in by default" icon="person-outline" autoCapitalize="words" />
          <AuthField label="Phone (optional)" value={customerPhone} onChangeText={setCustomerPhone} placeholder="07…" icon="call-outline" keyboardType="phone-pad" />

          <GroupLabel>Payment</GroupLabel>
          <ChipRow>
            <Chip label="Single" active={paymentMode === 'single'} onPress={() => setPaymentMode('single')} />
            <Chip label="Split" active={paymentMode === 'multi'} onPress={() => setPaymentMode('multi')} />
            {paymentMode === 'single' ? (
              <>
                <Chip label="Cash" active={method === 'cash'} onPress={() => setMethod('cash')} />
                <Chip label="Bank" active={method === 'bank_transfer'} onPress={() => setMethod('bank_transfer')} />
                <Chip label="Mobile" active={method === 'mobile_payment'} onPress={() => setMethod('mobile_payment')} />
              </>
            ) : null}
          </ChipRow>
          {paymentMode === 'multi' ? (
            <>
              <AuthField label="Cash" value={cashAmount} onChangeText={setCashAmount} placeholder="0" icon="cash-outline" keyboardType="numeric" />
              <AuthField label="Bank transfer" value={bankAmount} onChangeText={setBankAmount} placeholder="0" icon="business-outline" keyboardType="numeric" />
              <AuthField label="Mobile payment" value={mobileAmount} onChangeText={setMobileAmount} placeholder="0" icon="phone-portrait-outline" keyboardType="numeric" />
              <Banner
                kind={Math.abs(paymentSum - total) <= 0.01 ? 'success' : 'error'}
                message={`Split ${formatMoney(paymentSum)} of ${formatMoney(total)}`}
              />
            </>
          ) : null}

          <GoldButton
            label={busy ? 'Completing…' : `Complete sale · ${formatMoney(total)}`}
            icon="checkmark-circle-outline"
            loading={busy}
            disabled={!canSubmit}
            onPress={submit}
            style={{ marginTop: 14 }}
          />
        </>
      ) : null}

      <BusyOverlay visible={busy} label="Completing sale…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  picker: {
    backgroundColor: COLORS.bgRaised,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    maxHeight: 240,
    overflow: 'hidden',
  },
  pickRow: { paddingHorizontal: 12, paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: COLORS.border },
  pickRowActive: { backgroundColor: SELLER_ACCENT.soft },
  pickName: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 13 },
  pickMeta: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  lineForm: { flexDirection: 'row', gap: 10 },
  total: { color: SELLER_ACCENT.light, fontWeight: '800', fontSize: 13 },
  cartRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.bgRaised,
    borderColor: SELLER_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
    marginBottom: 8,
  },
  cartName: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 13 },
  cartMeta: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  remove: { color: COLORS.danger, fontWeight: '700', fontSize: 12 },
});
