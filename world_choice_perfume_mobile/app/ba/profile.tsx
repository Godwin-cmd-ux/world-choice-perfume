import { router } from 'expo-router';
import { useState } from 'react';
import { AuthField, Banner } from '../../components/authkit';
import { baMenu } from '../../components/basidebar';
import { AdminPage, BusyOverlay, GroupLabel, useAsyncData } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import { changeBaPassword, fetchBaProfile, updateBaProfile } from '../../lib/baApi';
import { staffSession } from '../../lib/staffSession';
import { BA_ACCENT } from '../../lib/theme';

/**
 * Account — the website's shared ProfileController screens (the same
 * profile every staff role edits): contact details in one form, password
 * change in the other. Same endpoints admin/GD/SM/CC use.
 */
export default function BaProfile() {
  const { data, sessionExpired } = useAsyncData(() => fetchBaProfile(), []);

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

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const saveProfile = async () => {
    setBusy('profile');
    setErr(null);
    setOk(null);
    setFields({});
    try {
      const res = await updateBaProfile({ name: name.trim(), email: email.trim(), phone: phone.trim() });
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
      const res = await changeBaPassword({ current_password: current, password: next, password_confirmation: confirm });
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
    <AdminPage title="Account" eyebrow="Branch Admin" accent={BA_ACCENT.main} onBack={() => router.back()} onMenu={baMenu.open}>
      {ok ? <Banner kind="success" message={ok} /> : null}
      {err ? <Banner kind="error" message={err} /> : null}

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

      <BusyOverlay visible={busy !== null} label="Saving…" />
    </AdminPage>
  );
}
