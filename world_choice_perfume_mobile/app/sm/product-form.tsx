import * as ImagePicker from 'expo-image-picker';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, Switch, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, useAsyncData, messageOf } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import {
  createProduct,
  fetchProduct,
  fetchProductFormData,
  updateProduct,
  type SmProductFields,
} from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

type PickedImage = { uri: string; name: string; type: string };

/**
 * Product create/edit — mobile twin of products/create and products/edit.
 * Brands come from the graphic designer's brand table (the same server-side
 * `brand.in` rule applies: unregistered brands are rejected), and edit keeps
 * the product's current brand even if it was later removed.
 */
export default function SmProductForm() {
  const params = useLocalSearchParams<{ id?: string }>();
  const isEdit = Boolean(params.id);

  const { data: formData, error: formError } = useAsyncData(() => fetchProductFormData(), []);
  const { data: existing, error: existingError, loading } = useAsyncData(
    () => (params.id ? fetchProduct(params.id) : Promise.resolve(null)),
    [params.id],
  );

  const [name, setName] = useState('');
  const [brand, setBrand] = useState('');
  const [category, setCategory] = useState<'Oil Fragrance' | 'Brand Perfume'>('Brand Perfume');
  const [sex, setSex] = useState('');
  const [ingredient, setIngredient] = useState('');
  const [description, setDescription] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [images, setImages] = useState<PickedImage[]>([]);
  const [hydrated, setHydrated] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  // Hydrate the edit form once the product arrives.
  if (isEdit && existing && !hydrated) {
    const p = existing.product;
    setHydrated(true);
    setName(p.name ?? '');
    setBrand(p.brand ?? '');
    setCategory((p.category ?? 'Brand Perfume') === 'Oil Fragrance' ? 'Oil Fragrance' : 'Brand Perfume');
    setSex(p.sex_category ?? '');
    setIngredient(p.fundamental_ingredient ?? '');
    setDescription(p.description ?? '');
    setIsActive(p.is_active !== false);
  }

  const brands = existing?.brands ?? formData?.brands ?? [];

  const pickImages = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsMultipleSelection: true,
      quality: 0.85,
    });
    if (!result.canceled) {
      setImages(
        result.assets.map((a) => ({
          uri: a.uri,
          name: a.fileName ?? `photo-${Date.now()}.jpg`,
          type: a.mimeType ?? 'image/jpeg',
        })),
      );
    }
  };

  const submit = async () => {
    if (busy) return;
    setError(null);
    setFieldErrors({});
    setBusy(true);
    try {
      const fields: SmProductFields = {
        name,
        description: description || null,
        brand: brand || null,
        category,
        sex_category: sex || null,
        fundamental_ingredient: ingredient || null,
      };
      if (isEdit && params.id) {
        await updateProduct(params.id, { ...fields, is_active: isActive }, images.length ? images : undefined);
      } else {
        await createProduct(fields, images.length ? images : undefined);
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
      title={isEdit ? 'Edit product' : 'New product'}
      eyebrow="Stock Manager" onMenu={smMenu.open}
      accent={SM_ACCENT.main}
      onBack={() => router.back()}
    >
      {error || formError || existingError ? <Banner kind="error" message={error ?? formError ?? existingError ?? ''} /> : null}
      {isEdit && loading ? <Text style={styles.loading}>Loading product…</Text> : null}

      <GroupLabel>Basics</GroupLabel>
      <AuthField label="Product name" value={name} onChangeText={setName} placeholder="e.g. Reef 33" icon="pricetag-outline" error={fieldErrors.name} autoCapitalize="words" />

      <Text style={styles.label}>Category</Text>
      <ChipRow>
        <Chip label="Brand Perfume" active={category === 'Brand Perfume'} onPress={() => setCategory('Brand Perfume')} />
        <Chip label="Oil Fragrance" active={category === 'Oil Fragrance'} onPress={() => setCategory('Oil Fragrance')} />
      </ChipRow>
      {fieldErrors.category ? <Banner kind="error" message={fieldErrors.category} /> : null}

      <Text style={styles.label}>Brand (graphic designer&apos;s list)</Text>
      <ChipRow>
        <Chip label="No brand" active={brand === ''} onPress={() => setBrand('')} />
        {brands.map((b) => (
          <Chip key={b} label={b} active={brand === b} onPress={() => setBrand(b)} />
        ))}
      </ChipRow>
      {fieldErrors.brand ? <Banner kind="error" message={fieldErrors.brand} /> : null}

      <Text style={styles.label}>For</Text>
      <ChipRow>
        {['', 'male', 'female', 'unisex', 'accessories'].map((s) => (
          <Chip key={s || 'any'} label={s === '' ? 'Any' : s} active={sex === s} onPress={() => setSex(s)} />
        ))}
      </ChipRow>

      <Text style={styles.label}>Fundamental ingredient</Text>
      <ChipRow>
        <Chip label="None" active={ingredient === ''} onPress={() => setIngredient('')} />
        {(formData?.fundamentalIngredients ?? []).map((ing) => (
          <Chip key={ing} label={ing} active={ingredient === ing} onPress={() => setIngredient(ing)} />
        ))}
      </ChipRow>

      <AuthField
        label="Description"
        value={description}
        onChangeText={setDescription}
        placeholder="Short description…"
        icon="document-text-outline"
        autoCapitalize="sentences"
      />

      <GroupLabel>Photos</GroupLabel>
      <Pressable onPress={pickImages} style={({ pressed }) => [styles.pickBtn, pressed && styles.pressed]} accessibilityRole="button">
        <Text style={styles.pickBtnText}>{images.length ? `Change photos (${images.length} selected)` : 'Pick photos'}</Text>
      </Pressable>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.imageRow}>
        {(images.length ? images : (existing?.product.images ?? []).map((img) => ({ uri: img.image_url ?? '' }))).map((img, i) =>
          img.uri ? <Image key={i} source={{ uri: img.uri }} style={styles.thumb} resizeMode="cover" /> : null,
        )}
      </ScrollView>

      {isEdit ? (
        <View style={styles.activeRow}>
          <Text style={styles.activeLabel}>Active in catalogue</Text>
          <Switch
            value={isActive}
            onValueChange={setIsActive}
            trackColor={{ true: SM_ACCENT.border, false: COLORS.border }}
            thumbColor={isActive ? SM_ACCENT.main : COLORS.textMuted}
          />
        </View>
      ) : null}

      <GoldButton
        label={isEdit ? 'Save product' : 'Create product'}
        icon="checkmark-circle-outline"
        loading={busy}
        disabled={!name}
        onPress={submit}
        style={{ marginTop: 8 }}
      />

      <BusyOverlay visible={busy} label="Saving…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  loading: { color: COLORS.textMuted, fontSize: 13 },
  label: { color: COLORS.textSecondary, fontSize: 12.5, fontWeight: '700', marginTop: 6 },
  pickBtn: {
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    paddingVertical: 11,
  },
  pickBtnText: { color: SM_ACCENT.light, fontWeight: '800', fontSize: 13.5 },
  pressed: { opacity: 0.7 },
  imageRow: { marginTop: 10, flexDirection: 'row' },
  thumb: { width: 74, height: 74, borderRadius: RADIUS.md, marginRight: 8, backgroundColor: COLORS.surface },
  activeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    paddingVertical: 10,
    marginTop: 12,
  },
  activeLabel: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
});
