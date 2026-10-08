import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { AuthField, Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, ConfirmDialog, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { GoldButton } from '../../../components/ui';
import { changeCcPassword, fetchCcProfile, updateCcProfile } from '../../../lib/careApi';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * Profile — the last entry of the website's customer-care sidebar
 * (Dashboard · Clients · Sales · Orders · Profile): the shared
 * ProfileController screens every staff role edits — contact details in one
 * form, password change in the other — plus the card that carries the photo
 * the server stores, and Logout (the sidebar's sign-out entry).
 */
export default function CareProfile() {
  const { data, sessionExpired } = useAsyncData(() => fetchCcProfile(), []);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [seeded, setSeeded] = useState(false);
  if (data && !seeded) {
    const user = (data.user ?? data) as Record<string, string | null>;
    setName((user.name as string) ?? '');
    setEmail((user.email as string) ?? '');
    setPhone((user.phone as string) ?? '');
    setSeeded(true);
  }

  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [confirm, setConfirm] = useState('');
  const [busy, setBusy] = useState<'profile' | 'password' | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [pwErr, setPwErr] = useState<string | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});
  const [pwFields, setPwFields] = useState<Record<string, string>>({});
  const [confirmOut, setConfirmOut] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const identity = staffSession.getIdentity();

  const saveProfile = async () => {
    setBusy('profile');
    setErr(null);
    setOk(null);
    setFields({});
    try {
      const res = await updateCcProfile({ name: name.trim(), email: email.trim(), phone: phone.trim() });
      setOk(res.message ?? 'Profile saved.');
    } catch (e) {
      const error = e as { message?: string; fields?: Record<string, string> };
      setErr(error.message ?? 'Could not save the profile.');
      setFields(error.fields ?? {});
    } finally {
      setBusy(null);
    }
  };

  const savePassword = async () => {
    setBusy('password');
    setPwErr(null);
    setOk(null);
    setPwFields({});
    try {
      const res = await changeCcPassword({ current_password: current, password: next, password_confirmation: confirm });
      setOk(res.message ?? 'Password changed.');
      setCurrent('');
      setNext('');
      setConfirm('');
    } catch (e) {
      const error = e as { message?: string; fields?: Record<string, string> };
      setPwErr(error.message ?? 'Could not change the password.');
      setPwFields(error.fields ?? {});
    } finally {
      setBusy(null);
    }
  };

  return (
    <AdminPage title="Profile" eyebrow="Customer Care" accent={CC_ACCENT.main}>
      {ok ? <Banner kind="success" message={ok} /> : null}
      {err ? <Banner kind="error" message={err} /> : null}

      <GroupLabel>Account</GroupLabel>
      <DataCard
        title={identity?.name ?? name ?? 'Customer Care'}
        subtitle={[identity?.email, 'customer_care'].filter(Boolean).join(' · ')}
        lines={identity?.branch?.name ? [identity.branch.name] : []}
      />

      <GroupLabel>Profile</GroupLabel>
      <AuthField label="Name" value={name} onChangeText={setName} placeholder="Your name" icon="person-outline" autoCapitalize="words" error={fields.name} />
      <AuthField label="Email" value={email} onChangeText={setEmail} placeholder="name@example.com" icon="mail-outline" keyboardType="email-address" error={fields.email} />
      <AuthField label="Phone" value={phone} onChangeText={setPhone} placeholder="07…" icon="call-outline" keyboardType="phone-pad" error={fields.phone} />
      <GoldButton label={busy === 'profile' ? 'Saving…' : 'Save Profile'} icon="checkmark-outline" loading={busy === 'profile'} onPress={saveProfile} />

      <GroupLabel>Password</GroupLabel>
      {pwErr ? <Banner kind="error" message={pwErr} /> : null}
      <AuthField label="Current password" value={current} onChangeText={setCurrent} placeholder="••••••••" icon="lock-closed-outline" secure error={pwFields.current_password} />
      <AuthField label="New password" value={next} onChangeText={setNext} placeholder="••••••••" icon="key-outline" secure error={pwFields.password} />
      <AuthField label="Confirm new password" value={confirm} onChangeText={setConfirm} placeholder="••••••••" icon="key-outline" secure error={pwFields.password_confirmation} />
      <GoldButton label={busy === 'password' ? 'Changing…' : 'Change Password'} icon="shield-checkmark-outline" loading={busy === 'password'} onPress={savePassword} style={{ marginTop: 4 }} />

      <GroupLabel>Session</GroupLabel>
      <Pressable
        onPress={() => setConfirmOut(true)}
        style={({ pressed }) => [styles.signOut, pressed && { opacity: 0.7 }]}
        accessibilityRole="button"
      >
        <Ionicons name="log-out-outline" size={18} color={COLORS.danger} />
        <Text style={styles.signOutText}>Sign Out</Text>
      </Pressable>

      <ConfirmDialog
        visible={confirmOut}
        title="Sign out?"
        message="You will need the staff secret code and your password to sign back in."
        confirmLabel="Sign Out"
        danger
        onConfirm={() => {
          staffSession.clear();
          router.replace('/');
        }}
        onCancel={() => setConfirmOut(false)}
      />

      <BusyOverlay visible={busy !== null} label="Saving…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  signOut: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderColor: 'rgba(248, 113, 113, 0.4)',
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
  },
  signOutText: { color: COLORS.danger, fontWeight: '800', fontSize: 13 },
});
