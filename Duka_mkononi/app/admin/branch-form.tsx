import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { Image, Pressable, Switch, StyleSheet, Text, TextInput, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, useAsyncData } from '../../components/adminkit';
import { ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { errorMessage, isApiError } from '../../lib/api';
import {
  createBranch,
  fetchBranch,
  fetchBranchOptions,
  updateBranch,
  type BranchFormFields,
} from '../../lib/adminApi';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * Branch create/edit — the mobile twin of the branches create/edit Blade
 * forms: same fields (name, address, category, coordinates, branch admin,
 * photo), same server validation (including the reserved-branch-name rule
 * surfaced through 422 errors), same save-then-return flow. Keyboard-aware
 * scrolling and one-thumb chips replace the desktop selects.
 */
export default function BranchForm() {
  const params = useLocalSearchParams<{ id?: string }>();
  const editingId = params.id ? String(params.id) : null;

  const opts = useAsyncData(() => fetchBranchOptions(), []);
  const existing = useAsyncData(
    () => (editingId ? fetchBranch(editingId) : Promise.resolve(null)),
    [editingId],
  );

  const [name, setName] = useState('');
  const [address, setAddress] = useState('');
  const [latitude, setLatitude] = useState('');
  const [longitude, setLongitude] = useState('');
  const [category, setCategory] = useState('');
  const [adminId, setAdminId] = useState('');
  const [active, setActive] = useState(true);
  const [photo, setPhoto] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [hydrated, setHydrated] = useState(false);

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  // Prefill the edit form once the branch arrives.
  if (editingId && existing.data?.branch && !hydrated) {
    const b = existing.data.branch;
    setHydrated(true);
    setName(b.name ?? '');
    setAddress(b.address ?? '');
    setLatitude(b.latitude != null ? String(b.latitude) : '');
    setLongitude(b.longitude != null ? String(b.longitude) : '');
    setCategory(b.category ?? '');
    setActive(b.is_active !== false);
  }

  const pickPhoto = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      quality: 0.85,
    });
    if (!result.canceled && result.assets[0]) {
      const a = result.assets[0];
      setPhoto({ uri: a.uri, name: a.fileName ?? 'branch.jpg', type: a.mimeType ?? 'image/jpeg' });
    }
  };

  const submit = async () => {
    if (saving) return;
    setErrors({});
    setFormError(null);
    setSuccess(null);

    // Light client-side mirror of the server rules (server stays authoritative).
    const local: Record<string, string> = {};
    if (!name.trim()) local.name = 'The name field is required.';
    if (editingId && isNaN(Number(latitude || 0)) && latitude !== '') local.latitude = 'Latitude must be a number.';
    if (editingId && isNaN(Number(longitude || 0)) && longitude !== '') local.longitude = 'Longitude must be a number.';
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }

    const fields: BranchFormFields = {
      name: name.trim(),
      address: address.trim(),
      latitude: latitude.trim(),
      longitude: longitude.trim(),
      category: category || undefined,
      admin_id: adminId || undefined,
      is_active: active,
    };

    setSaving(true);
    try {
      const res = editingId
        ? await updateBranch(editingId, fields, photo ?? undefined)
        : await createBranch(fields, photo ?? undefined);
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

  if (!editingId && (opts.loading || opts.error)) {
    return (
      <AdminPage title="New Branch" onBack={() => router.back()}>
        {opts.loading ? <LoadingView label="Loading form…" /> : <ErrorView message={opts.error ?? 'Unable to load.'} onRetry={opts.reload} />}
      </AdminPage>
    );
  }
  if (editingId && (existing.loading || (existing.error && !existing.data))) {
    return (
      <AdminPage title="Edit Branch" onBack={() => router.back()}>
        {existing.loading ? (
          <LoadingView label="Loading branch…" />
        ) : (
          <ErrorView message={existing.error ?? 'Unable to load.'} onRetry={existing.reload} />
        )}
      </AdminPage>
    );
  }

  return (
    <AdminPage title={editingId ? 'Edit Branch' : 'New Branch'} onBack={() => router.back()}>
      {success ? <Banner kind="success" message={success} /> : null}
      {formError ? <Banner kind="error" message={formError} /> : null}

      <GroupLabel>Branch details</GroupLabel>

      <Field label="Branch name" required error={errors.name}>
        <TextInput
          value={name}
          onChangeText={setName}
          placeholder="e.g. Kinondoni"
          placeholderTextColor={COLORS.textMuted}
          style={styles.input}
        />
      </Field>

      <Field label="Address" error={errors.address}>
        <TextInput
          value={address}
          onChangeText={setAddress}
          placeholder="Street, city…"
          placeholderTextColor={COLORS.textMuted}
          style={styles.input}
        />
      </Field>

      <GroupLabel>Category</GroupLabel>
      <ChipRow>
        <Chip label="None (autonomous)" active={!category} onPress={() => setCategory('')} />
        {(opts.data?.categories ?? []).map((c) => (
          <Chip key={c} label={c.replaceAll('_', ' ')} active={category === c} onPress={() => setCategory(c)} />
        ))}
      </ChipRow>

      <GroupLabel>Location</GroupLabel>
      <View style={styles.row2}>
        <View style={{ flex: 1 }}>
          <Field label="Latitude" error={errors.latitude}>
            <TextInput
              value={latitude}
              onChangeText={setLatitude}
              placeholder="-6.8"
              placeholderTextColor={COLORS.textMuted}
              keyboardType="decimal-pad"
              style={styles.input}
            />
          </Field>
        </View>
        <View style={{ flex: 1 }}>
          <Field label="Longitude" error={errors.longitude}>
            <TextInput
              value={longitude}
              onChangeText={setLongitude}
              placeholder="39.2"
              placeholderTextColor={COLORS.textMuted}
              keyboardType="decimal-pad"
              style={styles.input}
            />
          </Field>
        </View>
      </View>

      <GroupLabel>Branch admin {editingId ? '' : '(unassigned only)'}</GroupLabel>
      <ChipRow>
        <Chip label="None" active={!adminId} onPress={() => setAdminId('')} />
        {(opts.data?.admins ?? existing.data?.admins ?? []).map((a) => (
          <Chip key={String(a.id)} label={`${a.name}${a.email ? ` (${a.email})` : ''}`} active={adminId === String(a.id)} onPress={() => setAdminId(String(a.id))} />
        ))}
      </ChipRow>

      <GroupLabel>Photo</GroupLabel>
      <Pressable onPress={pickPhoto} style={({ pressed }) => [styles.photo, pressed && styles.pressed]} accessibilityRole="button">
        {photo ? (
          <Image source={{ uri: photo.uri }} style={styles.photoImg} resizeMode="cover" />
        ) : (
          <View style={styles.photoEmpty}>
            <Ionicons name="camera-outline" size={22} color={COLORS.textMuted} />
            <Text style={styles.muted}>{editingId ? 'Replace branch photo' : 'Add branch photo (optional)'}</Text>
          </View>
        )}
      </Pressable>

      {editingId ? (
        <View style={styles.switchRow}>
          <View style={{ flex: 1 }}>
            <Text style={styles.switchLabel}>Branch active</Text>
            <Text style={styles.muted}>Inactive branches are hidden from customers.</Text>
          </View>
          <Switch
            value={active}
            onValueChange={setActive}
            trackColor={{ true: COLORS.goldDark, false: COLORS.surfaceHigh }}
            thumbColor={active ? COLORS.gold : '#9CA3AF'}
          />
        </View>
      ) : null}

      <GoldButton
        label={editingId ? 'Save Changes' : 'Create Branch'}
        onPress={submit}
        loading={saving}
        icon="checkmark-circle-outline"
        style={{ marginTop: 8 }}
      />
      <BusyOverlay visible={saving} label="Saving branch…" />
    </AdminPage>
  );
}

function Field({
  label,
  required,
  error,
  children,
}: {
  label: string;
  required?: boolean;
  error?: string;
  children: React.ReactNode;
}) {
  return (
    <View style={{ gap: 6 }}>
      <Text style={styles.fieldLabel}>
        {label}
        {required ? <Text style={{ color: COLORS.danger }}> *</Text> : null}
      </Text>
      {children}
      {error ? <Text style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  fieldLabel: { color: '#D1D5DB', fontSize: 13.5, fontWeight: '600' },
  fieldError: { color: COLORS.danger, fontSize: 12 },
  input: {
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    height: 48,
    color: COLORS.text,
    fontSize: 15,
  },
  row2: { flexDirection: 'row', gap: 10 },
  photo: {
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    overflow: 'hidden',
    minHeight: 96,
    justifyContent: 'center',
  },
  photoEmpty: { alignItems: 'center', gap: 6, paddingVertical: 24 },
  photoImg: { width: '100%', height: 140 },
  pressed: { opacity: 0.8 },
  muted: { color: COLORS.textMuted, fontSize: 12.5 },
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
  switchLabel: { color: COLORS.text, fontSize: 14.5, fontWeight: '700' },
});
