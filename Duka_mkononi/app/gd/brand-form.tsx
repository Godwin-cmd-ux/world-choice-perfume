import { router, useLocalSearchParams } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { Image, Pressable, StyleSheet, Switch, Text, TextInput, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, useAsyncData } from '../../components/adminkit';
import { ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { errorMessage, isApiError } from '../../lib/api';
import { createGdBrand, fetchGdBrand, updateGdBrand } from '../../lib/gdApi';
import { COLORS, GD_ACCENT, RADIUS } from '../../lib/theme';

/**
 * Brand create/edit — the mobile twin of the brands create/edit Blade forms:
 * same fields (name, logo, active flag), same server rules including the
 * duplicate-name 422 surfaced under `name`, same save-then-return flow.
 */
export default function GdBrandForm() {
  const params = useLocalSearchParams<{ id?: string }>();
  const editingId = params.id ? String(params.id) : null;

  const existing = useAsyncData(
    () => (editingId ? fetchGdBrand(editingId) : Promise.resolve(null)),
    [editingId],
  );

  const [name, setName] = useState('');
  const [active, setActive] = useState(true);
  const [logo, setLogo] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [hydrated, setHydrated] = useState(false);

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  // Prefill the edit form once the brand arrives.
  if (editingId && existing.data?.brand && !hydrated) {
    const b = existing.data.brand;
    setHydrated(true);
    setName(b.name ?? '');
    setActive(b.is_active !== false);
  }

  const pickLogo = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      aspect: [1, 1],
      quality: 0.85,
    });
    if (!result.canceled && result.assets[0]) {
      const a = result.assets[0];
      setLogo({ uri: a.uri, name: a.fileName ?? 'logo.png', type: a.mimeType ?? 'image/png' });
    }
  };

  const submit = async () => {
    if (saving) return;
    setErrors({});
    setFormError(null);
    setSuccess(null);

    const local: Record<string, string> = {};
    if (!name.trim()) local.name = 'The name field is required.';
    else if (name.trim().length > 255) local.name = 'The name may not be longer than 255 characters.';
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }

    setSaving(true);
    try {
      const res = editingId
        ? await updateGdBrand(editingId, { name: name.trim(), is_active: active }, logo ?? undefined)
        : await createGdBrand({ name: name.trim(), is_active: active }, logo ?? undefined);
      setSuccess(res.message);
      setTimeout(() => router.back(), 900);
    } catch (e) {
      if (isApiError(e) && e.kind === 'validation') {
        setErrors(e.fields);
        setFormError(e.message);
      } else {
        setFormError(errorMessage(e));
      }
    } finally {
      setSaving(false);
    }
  };

  if (editingId && (existing.loading || (existing.error && !existing.data))) {
    return (
      <AdminPage title="Edit Brand" eyebrow="Graphic Designer" accent={GD_ACCENT.main} onBack={() => router.back()}>
        {existing.loading ? (
          <LoadingView label="Loading brand…" />
        ) : (
          <ErrorView message={existing.error ?? 'Unable to load.'} onRetry={existing.reload} />
        )}
      </AdminPage>
    );
  }

  const currentLogo = logo?.uri ?? existing.data?.brand.logo_url ?? null;

  return (
    <AdminPage
      title={editingId ? 'Edit Brand' : 'New Brand'}
      eyebrow="Graphic Designer"
      accent={GD_ACCENT.main}
      onBack={() => router.back()}
    >
      {success ? <Banner kind="success" message={success} /> : null}
      {formError ? <Banner kind="error" message={formError} /> : null}

      <GroupLabel>Brand details</GroupLabel>

      <View style={styles.field}>
        <Text style={styles.label}>
          Brand name<Text style={{ color: GD_ACCENT.main }}> *</Text>
        </Text>
        <TextInput
          value={name}
          onChangeText={setName}
          placeholder="e.g. Chanel"
          placeholderTextColor={COLORS.textMuted}
          style={styles.input}
        />
        {errors.name ? <Text style={styles.error}>{errors.name}</Text> : null}
      </View>

      <GroupLabel>Logo</GroupLabel>
      <Pressable onPress={pickLogo} style={({ pressed }) => [styles.photo, pressed && styles.pressed]} accessibilityRole="button">
        {currentLogo ? (
          <Image source={{ uri: logo?.uri ?? currentLogo }} style={styles.photoImg} resizeMode="contain" />
        ) : (
          <View style={styles.photoEmpty}>
            <Text style={styles.muted}>{editingId ? 'Add brand logo' : 'Add brand logo (optional)'}</Text>
          </View>
        )}
      </Pressable>

      <View style={styles.switchRow}>
        <View style={{ flex: 1 }}>
          <Text style={styles.label}>Active</Text>
          <Text style={styles.muted}>Active brands appear in Featured Brands on the home page.</Text>
        </View>
        <Switch
          value={active}
          onValueChange={setActive}
          trackColor={{ true: GD_ACCENT.main, false: COLORS.surfaceHigh }}
          thumbColor={active ? GD_ACCENT.light : '#9CA3AF'}
        />
      </View>

      <GoldButton
        label={editingId ? 'Save Changes' : 'Create Brand'}
        onPress={submit}
        loading={saving}
        icon="checkmark-circle-outline"
        style={{ marginTop: 8 }}
      />
      <BusyOverlay visible={saving} label="Saving brand…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  field: { gap: 6 },
  label: { color: COLORS.textSecondary, fontSize: 13, fontWeight: '700' },
  error: { color: COLORS.danger, fontSize: 12.5 },
  input: {
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 13,
    paddingVertical: 12,
    color: COLORS.text,
    fontSize: 15,
  },
  photo: {
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    overflow: 'hidden',
  },
  photoImg: { width: '100%', height: 140, backgroundColor: COLORS.surfaceHigh },
  photoEmpty: { height: 110, alignItems: 'center', justifyContent: 'center' },
  muted: { color: COLORS.textMuted, fontSize: 13 },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 14,
  },
  pressed: { opacity: 0.8 },
});
