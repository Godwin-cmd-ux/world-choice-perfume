import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, messageOf, useAsyncData } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import {
  createProductStockEntry,
  fetchProductStockEntry,
  type SmProductStockEntryForm,
} from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

const CATEGORIES = ['Brand Perfume', 'Oil Fragrance'] as const;
const DETAIL_VOLUMES = [30, 50, 100];

/**
 * Product stock-in — the mobile twin of product-stock-entry.blade.php and
 * POST /stock-manager/product-stock/entry. Oil Fragrance entries require a
 * bottle volume, require the exact box/logo/color variety for 30/50/100ml,
 * and are checked against this branch's own bottle stock (the server
 * re-checks and answers 422 with the website's message when short).
 */
export default function SmStockEntry() {
  const params = useLocalSearchParams<{ product_id?: string }>();
  const {
    data: form,
    error: loadError,
    loading: formLoading,
  } = useAsyncData<SmProductStockEntryForm>(() => fetchProductStockEntry(params.product_id ?? null), [params.product_id]);

  const [category, setCategory] = useState<(typeof CATEGORIES)[number]>('Brand Perfume');
  const [productId, setProductId] = useState<string>(params.product_id ?? '');
  const [quantity, setQuantity] = useState('');
  const [price, setPrice] = useState('');
  const [varietyPrice, setVarietyPrice] = useState('');
  const [bottleVolume, setBottleVolume] = useState('');
  const [bottleVariant, setBottleVariant] = useState('');
  const [dateReceived, setDateReceived] = useState(new Date().toISOString().slice(0, 10));
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const products = useMemo(() => form?.products ?? [], [form]);
  const [productSearch, setProductSearch] = useState('');
  const filteredProducts = useMemo(() => {
    const q = productSearch.trim().toLowerCase();
    if (!q) return products;
    return products.filter(
      (p) => p.name.toLowerCase().includes(q) || (p.brand ?? '').toLowerCase().includes(q),
    );
  }, [products, productSearch]);

  const selectedProduct = products.find((p) => String(p.id) === productId) ?? null;

  // Availability map for the chosen oil bottle volume/variety.
  const variantStock = form?.bottleVariants ?? {};
  const volumeOptions = Object.keys(variantStock).sort((a, b) => Number(a) - Number(b));
  const variantsForVolume = bottleVolume ? (variantStock[bottleVolume] ?? {}) : {};
  const needsDetails = bottleVolume ? DETAIL_VOLUMES.includes(Number(bottleVolume)) : false;
  const available = bottleVolume && bottleVariant ? (variantsForVolume[bottleVariant] ?? 0) : 0;

  const submit = async () => {
    if (busy) return;
    setError(null);
    setFieldErrors({});
    try {
      await createProductStockEntry({
        product_id: productId,
        quantity: Number(quantity),
        selling_price: Number(price),
        variety_price: varietyPrice ? Number(varietyPrice) : undefined,
        category,
        bottle_volume: category === 'Oil Fragrance' && bottleVolume ? Number(bottleVolume) : undefined,
        bottle_variant: category === 'Oil Fragrance' && bottleVariant ? bottleVariant : undefined,
        date_received: dateReceived,
      });
      router.back();
    } catch (e) {
      const anyErr = e as { fields?: Record<string, string> };
      if (anyErr?.fields && Object.keys(anyErr.fields).length > 0) {
        setFieldErrors(anyErr.fields);
      }
      setError(messageOf(e));
    }
  };

  return (
    <AdminPage title="Stock In" eyebrow="Stock Manager" onMenu={smMenu.open} accent={SM_ACCENT.main} onBack={() => router.back()}>
      {error ? <Banner kind="error" message={error} /> : null}
      {!loadError && formLoading ? <Text style={styles.loading}>Loading form…</Text> : null}
      {loadError ? <Banner kind="error" message={loadError} /> : null}

      <GroupLabel>Category</GroupLabel>
      <View style={styles.chipRow}>
        {CATEGORIES.map((c) => (
          <Pressable
            key={c}
            onPress={() => {
              setCategory(c);
              if (c === 'Brand Perfume') {
                setBottleVolume('');
                setBottleVariant('');
              }
            }}
            style={[styles.chip, category === c && styles.chipActive]}
            accessibilityRole="button"
          >
            <Text style={[styles.chipText, category === c && styles.chipTextActive]}>{c}</Text>
          </Pressable>
        ))}
      </View>

      <GroupLabel>Product</GroupLabel>
      <AuthField
        label="Search products"
        value={productSearch}
        onChangeText={setProductSearch}
        placeholder="Type to filter…"
        icon="search-outline"
      />
      <View style={styles.productList}>
        {filteredProducts.slice(0, 40).map((p) => {
          const active = String(p.id) === productId;
          return (
            <Pressable
              key={String(p.id)}
              onPress={() => setProductId(String(p.id))}
              style={[styles.productRow, active && styles.productRowActive]}
              accessibilityRole="button"
            >
              <Ionicons
                name={active ? 'radio-button-on' : 'radio-button-off'}
                size={17}
                color={active ? SM_ACCENT.main : COLORS.textMuted}
              />
              <Text style={[styles.productName, active && { color: SM_ACCENT.light }]} numberOfLines={1}>
                {p.name}
              </Text>
              <Text style={styles.productBrand}>{p.brand ?? ''}</Text>
            </Pressable>
          );
        })}
        {filteredProducts.length === 0 ? <Text style={styles.loading}>No products match.</Text> : null}
      </View>
      {fieldErrors.product_id ? <Banner kind="error" message={fieldErrors.product_id} /> : null}

      <GroupLabel>Quantity &amp; price</GroupLabel>
      <AuthField
        label="Quantity"
        value={quantity}
        onChangeText={setQuantity}
        placeholder="e.g. 24"
        icon="layers-outline"
        keyboardType="number-pad"
        error={fieldErrors.quantity}
      />
      <AuthField
        label="Selling price (TZS)"
        value={price}
        onChangeText={setPrice}
        placeholder="e.g. 25000"
        icon="cash-outline"
        keyboardType="numeric"
        error={fieldErrors.selling_price}
      />
      {category === 'Oil Fragrance' ? (
        <AuthField
          label="Variety price (optional, wins over selling price)"
          value={varietyPrice}
          onChangeText={setVarietyPrice}
          placeholder="e.g. 30000"
          icon="pricetag-outline"
          keyboardType="numeric"
          error={fieldErrors.variety_price}
        />
      ) : null}
      <AuthField
        label="Date received"
        value={dateReceived}
        onChangeText={setDateReceived}
        placeholder="YYYY-MM-DD"
        icon="calendar-outline"
        error={fieldErrors.date_received}
      />

      {category === 'Oil Fragrance' ? (
        <>
          <GroupLabel>Bottle (consumed from this branch&apos;s own stock)</GroupLabel>
          <View style={styles.chipRow}>
            {volumeOptions.map((v) => (
              <Pressable
                key={v}
                onPress={() => {
                  setBottleVolume(v);
                  setBottleVariant('');
                }}
                style={[styles.chip, bottleVolume === v && styles.chipActive]}
                accessibilityRole="button"
              >
                <Text style={[styles.chipText, bottleVolume === v && styles.chipTextActive]}>{v}ml</Text>
              </Pressable>
            ))}
          </View>
          {fieldErrors.bottle_volume ? <Banner kind="error" message={fieldErrors.bottle_volume} /> : null}

          {needsDetails ? (
            <>
              <Text style={styles.label}>Variety (box / logo / color)</Text>
              <View style={styles.chipRow}>
                {Object.keys(variantsForVolume).map((key) => (
                  <Pressable
                    key={key}
                    onPress={() => setBottleVariant(key)}
                    style={[styles.chip, bottleVariant === key && styles.chipActive]}
                    accessibilityRole="button"
                  >
                    <Text style={[styles.chipText, bottleVariant === key && styles.chipTextActive]}>{key}</Text>
                  </Pressable>
                ))}
              </View>
              {bottleVariant ? (
                <Text style={styles.availability}>
                  Available at this branch: {available}
                </Text>
              ) : null}
              {fieldErrors.bottle_variant ? <Banner kind="error" message={fieldErrors.bottle_variant} /> : null}
            </>
          ) : bottleVolume ? (
            <Text style={styles.availability}>Available plain {bottleVolume}ml: {variantStock[bottleVolume]?.plain ?? 0}</Text>
          ) : null}
        </>
      ) : null}

      <GoldButton
        label="Record stock entry"
        icon="checkmark-circle-outline"
        loading={busy}
        disabled={!productId || !quantity || !price || !dateReceived}
        onPress={async () => {
          setBusy(true);
          await submit();
          setBusy(false);
        }}
        style={{ marginTop: 8 }}
      />
      <Text style={styles.hint}>
        {selectedProduct ? `${selectedProduct.name} · ${category}` : 'Pick a product to continue.'}
      </Text>

      <BusyOverlay visible={busy} label="Recording…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  loading: { color: COLORS.textMuted, fontSize: 13 },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  chipActive: { backgroundColor: SM_ACCENT.soft, borderColor: SM_ACCENT.border },
  chipText: { color: COLORS.textSecondary, fontSize: 13, fontWeight: '700' },
  chipTextActive: { color: SM_ACCENT.light },
  productList: {
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.surface,
    paddingVertical: 4,
    gap: 2,
    maxHeight: 260,
  },
  productRow: { flexDirection: 'row', alignItems: 'center', gap: 9, paddingHorizontal: 12, paddingVertical: 9 },
  productRowActive: { backgroundColor: SM_ACCENT.soft },
  productName: { color: COLORS.text, fontSize: 14, fontWeight: '600', flex: 1 },
  productBrand: { color: COLORS.textMuted, fontSize: 11.5 },
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
  availability: { color: SM_ACCENT.light, fontSize: 12.5, marginTop: 6, fontWeight: '600' },
  hint: { color: COLORS.textMuted, fontSize: 12.5, textAlign: 'center', marginTop: 10 },
});
