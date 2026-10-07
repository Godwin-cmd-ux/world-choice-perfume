import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, useAsyncData, messageOf } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import { changeSmPassword, fetchSmProfile, updateSmProfile } from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';

interface ProfilePayload {
  user?: { id?: number | string; name?: string; email?: string; phone?: string | null; profile_picture?: string | null; role?: string };
  branch?: { id?: number | string; name?: string; address?: string | null } | null;
}

/**
 * Profile & password — same ProfileController endpoints the Super Admin and
 * Graphic Designer modules use (GET/PUT /profile, PUT /profile/password),
 * so every staff role edits their account one way.
 */
export default function SmProfile() {
  const { data, error, loading, reload } = useAsyncData<ProfilePayload>(() => fetchSmProfile() as Promise<ProfilePayload>, []);

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [photo, setPhoto] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [hydrated, setHydrated] = useState(false);

  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const [busy, setBusy] = useState(false);
  const [ok, setOk] = useState<string | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  if (data?.user && !hydrated) {
    setHydrated(true);
    setName(data.user.name ?? '');
    setPhone(data.user.phone ?? '');
    setEmail(data.user.email ?? '');
  }

  const pickPhoto = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      aspect: [1, 1],
      quality: 0.85,
    });
    if (!result.canceled && result.assets[0]) {
      const a = result.assets[0];
      setPhoto({ uri: a.uri, name: a.fileName ?? 'photo.png', type: a.mimeType ?? 'image/png' });
    }
  };

  const saveProfile = async () => {
    if (busy) return;
    setBusy(true);
    setOk(null);
    setErr(null);
    setFieldErrors({});
    try {
      if (photo) {
        const form = new FormData();
        form.append('name', name);
        form.append('phone', phone);
        form.append('email', email);
        form.append('profile_picture', photo as unknown as Blob);
        await updateSmProfile(form as unknown as Record<string, string>);
      } else {
        await updateSmProfile({ name, phone, email });
      }
      setOk('Profile updated.');
      await reload();
    } catch (e) {
      const anyErr = e as { fields?: Record<string, string> };
      if (anyErr?.fields && Object.keys(anyErr.fields).length > 0) setFieldErrors(anyErr.fields);
      setErr(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  const savePassword = async () => {
    if (busy) return;
    setBusy(true);
    setOk(null);
    setErr(null);
    setFieldErrors({});
    try {
      await changeSmPassword({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      setOk('Password changed.');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
    } catch (e) {
      const anyErr = e as { fields?: Record<string, string> };
      if (anyErr?.fields && Object.keys(anyErr.fields).length > 0) setFieldErrors(anyErr.fields);
      setErr(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  const avatarUri = photo?.uri ?? (data?.user?.profile_picture ? String(data.user.profile_picture) : null);

  return (
    <AdminPage title="Profile" eyebrow="Stock Manager" accent={SM_ACCENT.main} onBack={() => router.back()}>
      {ok ? <Banner kind="success" message={ok} /> : null}
      {err || error ? <Banner kind="error" message={err ?? error ?? ''} /> : null}
      {loading ? <Text style={styles.loading}>Loading profile…</Text> : null}

      <View style={styles.avatarRow}>
        <View style={styles.avatar}>
          {avatarUri ? (
            <Image source={{ uri: avatarUri }} style={styles.avatarImg} resizeMode="cover" />
          ) : (
            <Text style={styles.avatarText}>{(name || 'S').charAt(0).toUpperCase()}</Text>
          )}
        </View>
        <Pressable onPress={pickPhoto} style={({ pressed }) => [styles.photoBtn, pressed && styles.pressed]} accessibilityRole="button">
          <Text style={styles.photoBtnText}>Change photo</Text>
        </Pressable>
      </View>

      <GroupLabel>Account</GroupLabel>
      <AuthField label="Full name" value={name} onChangeText={setName} placeholder="Your name" icon="person-outline" autoCapitalize="words" error={fieldErrors.name} />
      <AuthField label="Phone" value={phone} onChangeText={setPhone} placeholder="07…" icon="call-outline" keyboardType="phone-pad" error={fieldErrors.phone} />
      <AuthField label="Email" value={email} onChangeText={setEmail} placeholder="you@example.com" icon="mail-outline" keyboardType="email-address" error={fieldErrors.email} />
      <Text style={styles.branchLine}>
        {data?.branch?.name ? `Branch: ${data.branch.name}` : ''}
        {data?.user?.role ? ` · ${data.user.role.replace('_', ' ')}` : ''}
      </Text>
      <GoldButton label="Save profile" icon="checkmark-circle-outline" loading={busy} disabled={!name || !email} onPress={saveProfile} />

      <GroupLabel>Change password</GroupLabel>
      <AuthField label="Current password" value={currentPassword} onChangeText={setCurrentPassword} placeholder="••••••••" icon="lock-closed-outline" secure error={fieldErrors.current_password} />
      <AuthField label="New password (min 8)" value={newPassword} onChangeText={setNewPassword} placeholder="••••••••" icon="key-outline" secure error={fieldErrors.password} />
      <AuthField label="Confirm new password" value={confirmPassword} onChangeText={setConfirmPassword} placeholder="••••••••" icon="key-outline" secure />
      <GoldButton
        label="Change password"
        icon="shield-checkmark-outline"
        loading={busy}
        disabled={!currentPassword || newPassword.length < 8 || newPassword !== confirmPassword}
        onPress={savePassword}
      />

      <BusyOverlay visible={busy} label="Saving…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  loading: { color: COLORS.textMuted, fontSize: 13 },
  avatarRow: { flexDirection: 'row', alignItems: 'center', gap: 14 },
  avatar: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1.5,
    borderColor: SM_ACCENT.border,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  avatarImg: { width: '100%', height: '100%' },
  avatarText: { color: SM_ACCENT.light, fontSize: 24, fontWeight: '800' },
  photoBtn: {
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 9,
  },
  photoBtnText: { color: SM_ACCENT.light, fontWeight: '700', fontSize: 13 },
  pressed: { opacity: 0.7 },
  branchLine: { color: COLORS.textMuted, fontSize: 12.5, marginBottom: 8 },
});
