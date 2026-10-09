import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, KV, useAsyncData } from '../../components/adminkit';
import { ErrorView, GoldButton, LoadingView, OutlineButton } from '../../components/ui';
import { errorMessage, isApiError } from '../../lib/api';
import { changeAdminPassword, fetchAdminProfile, saveAdminProfile } from '../../lib/adminApi';
import { COLORS, RADIUS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

/**
 * Profile (the sidebar Account → Profile page): edit name / phone / email /
 * photo, and change password — same fields and server rules as the website's
 * profile pages, including the current-password check.
 */
export default function AdminProfile() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchAdminProfile(), []);

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [photo, setPhoto] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [hydrated, setHydrated] = useState(false);

  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [nextConfirm, setNextConfirm] = useState('');

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [notice, setNotice] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState<'profile' | 'password' | null>(null);

  if (data && !hydrated) {
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
      setPhoto({ uri: a.uri, name: a.fileName ?? 'profile.jpg', type: a.mimeType ?? 'image/jpeg' });
    }
  };

  const saveProfile = async () => {
    if (saving) return;
    setErrors({});
    setFormError(null);
    setNotice(null);

    const local: Record<string, string> = {};
    if (!name.trim()) local.name = 'The name field is required.';
    if (!email.trim() || !/^\S+@\S+\.\S+$/.test(email.trim())) local.email = 'Enter a valid email address.';
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }

    setSaving('profile');
    try {
      const res = await saveAdminProfile({ name: name.trim(), email: email.trim(), phone: phone.trim() }, photo ?? undefined);
      setNotice(res.message);
      await reload();
    } catch (e) {
      if (isApiError(e) && e.kind === 'validation') {
        setErrors(e.fields);
        setFormError(e.message);
      } else {
        setFormError(errorMessage(e));
      }
    } finally {
      setSaving(null);
    }
  };

  const savePassword = async () => {
    if (saving) return;
    setErrors({});
    setFormError(null);
    setNotice(null);

    const local: Record<string, string> = {};
    if (!current) local.current_password = 'Enter your current password.';
    if (next.length < 8) local.password = 'The new password must be at least 8 characters.';
    if (next !== nextConfirm) local.password_confirmation = 'The confirmation does not match.';
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }

    setSaving('password');
    try {
      const res = await changeAdminPassword({
        current_password: current,
        password: next,
        password_confirmation: nextConfirm,
      });
      setNotice(res.message);
      setCurrent('');
      setNext('');
      setNextConfirm('');
    } catch (e) {
      if (isApiError(e) && e.kind === 'validation') {
        setErrors(e.fields);
        setFormError(e.message);
      } else {
        setFormError(errorMessage(e));
      }
    } finally {
      setSaving(null);
    }
  };

  const avatarUri = photo?.uri ?? data?.user.profile_picture ?? null;

  return (
    <AdminPage title="My Profile" onBack={() => router.back()} onMenu={adminMenu.open}>
      {loading ? <LoadingView label="Loading profile…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {notice ? <Banner kind="success" message={notice} /> : null}
          {formError ? <Banner kind="error" message={formError} /> : null}

          <View style={styles.avatarRow}>
            <Pressable onPress={pickPhoto} style={styles.avatarWrap} accessibilityRole="button" accessibilityLabel="Change photo">
              {avatarUri ? (
                <Image source={{ uri: avatarUri }} style={styles.avatar} />
              ) : (
                <View style={[styles.avatar, styles.avatarEmpty]}>
                  <Text style={styles.avatarLetter}>{(name || 'S').charAt(0).toUpperCase()}</Text>
                </View>
              )}
              <View style={styles.cameraBadge}>
                <Text style={styles.cameraText}>📷</Text>
              </View>
            </Pressable>
            <View style={{ flex: 1, gap: 4 }}>
              <KV label="Role:" value={data.user.role?.replaceAll('_', ' ')} />
              <KV label="Branch:" value={data.branch?.name ?? null} />
              <KV label="Status:" value={data.user.status} tone="success" />
            </View>
          </View>

          <GroupLabel>Account details</GroupLabel>
          <AuthField label="Full Name *" value={name} onChangeText={setName} placeholder="Your name" icon="person-outline" autoCapitalize="words" error={errors.name} />
          <AuthField label="Email *" value={email} onChangeText={setEmail} placeholder="you@example.com" icon="mail-outline" keyboardType="email-address" error={errors.email} />
          <AuthField label="Phone" value={phone} onChangeText={setPhone} placeholder="+255 7XX XXX XXX" icon="call-outline" keyboardType="phone-pad" error={errors.phone} />
          <GoldButton label="Save Profile" onPress={saveProfile} loading={saving === 'profile'} icon="save-outline" />

          <GroupLabel>Change password</GroupLabel>
          <AuthField label="Current Password *" value={current} onChangeText={setCurrent} placeholder="Your current password" icon="lock-closed-outline" secure error={errors.current_password} />
          <AuthField label="New Password *" value={next} onChangeText={setNext} placeholder="Min. 8 characters" icon="lock-closed-outline" secure error={errors.password} />
          <AuthField label="Confirm New Password *" value={nextConfirm} onChangeText={setNextConfirm} placeholder="Repeat the new password" icon="lock-closed-outline" secure error={errors.password_confirmation} />
          <OutlineButton label="Change Password" icon="key-outline" onPress={savePassword} />
        </>
      ) : null}
      <BusyOverlay visible={saving !== null} label={saving === 'password' ? 'Changing password…' : 'Saving profile…'} />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  avatarRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 16,
  },
  avatarWrap: { width: 76, height: 76 },
  avatar: { width: 76, height: 76, borderRadius: 38 },
  avatarEmpty: {
    backgroundColor: COLORS.goldDeep,
    borderWidth: 1.5,
    borderColor: COLORS.gold,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarLetter: { color: COLORS.goldLight, fontSize: 28, fontWeight: '800' },
  cameraBadge: {
    position: 'absolute',
    bottom: -4,
    right: -4,
    backgroundColor: COLORS.gold,
    width: 26,
    height: 26,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: COLORS.bg,
  },
  cameraText: { fontSize: 12 },
});
