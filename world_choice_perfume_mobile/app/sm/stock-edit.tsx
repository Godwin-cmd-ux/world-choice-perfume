import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, messageOf } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import { updateProductStock, updateStockVariety } from '../../lib/smApi';
import { SM_ACCENT } from '../../lib/theme';

/**
 * Edit one stock line — either a product's aggregate branch_stock row or a
 * single variety bucket (kind=variety), mirroring the website's
 * product-stock/{stock} and product-stock/varieties/{variety} forms. The
 * server keeps the aggregate and the variety breakdown in step.
 */
export default function SmStockEdit() {
  const params = useLocalSearchParams<{
    kind?: string;
    stock_id?: string;
    variety_id?: string;
    label?: string;
    quantity?: string;
    price?: string;
  }>();

  const isVariety = params.kind === 'variety';
  const [quantity, setQuantity] = useState(params.quantity ?? '');
  const [price, setPrice] = useState(params.price ?? '');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const submit = async () => {
    if (busy) return;
    setError(null);
    setFieldErrors({});
    setBusy(true);
    try {
      const fields = { quantity: Number(quantity), selling_price: Number(price) };
      if (isVariety) {
        if (!params.variety_id) throw new Error('Variety stock record not found.');
        await updateStockVariety(params.variety_id, fields);
      } else {
        if (!params.stock_id) throw new Error('Stock record not found.');
        await updateProductStock(params.stock_id, fields);
      }
      router.back();
    } catch (e) {
      const anyErr = e as { fields?: Record<string, string> };
      if (anyErr?.fields && Object.keys(anyErr.fields).length > 0) setFieldErrors(anyErr.fields);
      setError(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  return (
    <AdminPage
      title={isVariety ? 'Edit variety' : 'Edit stock'}
      eyebrow={params.label ?? 'Stock Manager'}
      accent={SM_ACCENT.main}
      onBack={() => router.back()}
    >
      {error ? <Banner kind="error" message={error} /> : null}

      <GroupLabel>{params.label ?? 'Stock record'}</GroupLabel>
      <AuthField
        label="Quantity"
        value={quantity}
        onChangeText={setQuantity}
        placeholder="0"
        icon="layers-outline"
        keyboardType="number-pad"
        error={fieldErrors.quantity}
      />
      <AuthField
        label="Selling price (TZS)"
        value={price}
        onChangeText={setPrice}
        placeholder="0"
        icon="cash-outline"
        keyboardType="numeric"
        error={fieldErrors.selling_price}
      />

      <GoldButton
        label="Save changes"
        icon="checkmark-circle-outline"
        loading={busy}
        disabled={!quantity || !price}
        onPress={submit}
        style={{ marginTop: 8 }}
      />

      <BusyOverlay visible={busy} label="Saving…" />
    </AdminPage>
  );
}
