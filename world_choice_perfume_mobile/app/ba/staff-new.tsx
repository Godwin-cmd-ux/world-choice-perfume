import { router } from 'expo-router';
import { useState } from 'react';
import { AuthField, Banner } from '../../components/authkit';
import { baMenu } from '../../components/basidebar';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, useAsyncData } from '../../components/adminkit';
import { ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { createBaStaff, fetchBaStaffFormData, type BaStaffRole } from '../../lib/baApi';
import { BA_ACCENT } from '../../lib/theme';

const ROLE_LABELS: Record<string, string> = {
  stock_manager: 'Stock Manager',
  seller: 'Seller',
  customer_care: 'Customer Care',
  cashier: 'Cashier',
};

/**
 * New Staff — the website's branch-admin/staffs/create + POST /staffs.
 * The account is created into THIS admin's branch (the server stamps
 * branch_id from the session), written to Supabase first and mirrored into
 * SQLite so the new member can sign in immediately. A duplicate email
 * answers 422 "This email is already registered." on the email field.
 */
export default function BaStaffNew() {
  const { data, error, loading, reload } = useAsyncData(() => fetchBaStaffFormData(), []);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [role, setRole] = useState<BaStaffRole>('cashier');

  const [busy, setBusy] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});

  const submit = async () => {
    setBusy(true);
    setSubmitError(null);
    setFields({});
    try {
      const res = await createBaStaff({
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim() || undefined,
        password,
        password_confirmation: confirm,
        role,
      });
      router.replace({ pathname: '/ba/staff', params: { notice: res.message } });
    } catch (e) {
      const err = e as { message?: string; fields?: Record<string, string> };
      setSubmitError(err.message ?? 'The staff account could not be created.');
      setFields(err.fields ?? {});
    } finally {
      setBusy(false);
    }
  };

  const canSubmit = name.trim() && email.trim() && password.length >= 8 && confirm === password;

  return (
    <AdminPage title="New Staff" eyebrow={data?.branchName ?? 'Branch Admin'} accent={BA_ACCENT.main} onBack={() => router.back()} onMenu={baMenu.open}>
      {error ? <ErrorView message={error} onRetry={reload} /> : null}
      {loading && !data ? <LoadingView label="Loading form…" /> : null}
      {submitError ? <Banner kind="error" message={submitError} /> : null}

      <GroupLabel>Role</GroupLabel>
      <ChipRow>
        {(data?.roles ?? []).map((r) => (
          <Chip
            key={r}
            label={ROLE_LABELS[r] ?? r.replace(/_/g, ' ')}
            active={role === r}
            onPress={() => setRole(r as BaStaffRole)}
          />
        ))}
      </ChipRow>

      <GroupLabel>Details</GroupLabel>
      <AuthField label="Full name" value={name} onChangeText={setName} placeholder="e.g. Amina Juma" icon="person-outline" autoCapitalize="words" error={fields.name} />
      <AuthField label="Email" value={email} onChangeText={setEmail} placeholder="name@example.com" icon="mail-outline" keyboardType="email-address" autoCapitalize="none" error={fields.email} />
      <AuthField label="Phone (optional)" value={phone} onChangeText={setPhone} placeholder="07…" icon="call-outline" keyboardType="phone-pad" error={fields.phone} />
      <AuthField label="Password" value={password} onChangeText={setPassword} placeholder="At least 8 characters" icon="lock-closed-outline" secure error={fields.password} />
      <AuthField label="Confirm password" value={confirm} onChangeText={setConfirm} placeholder="Repeat the password" icon="key-outline" secure error={fields.password_confirmation} />

      {fields.email ? null : (
        <Banner kind="connection" message={`This member joins ${data?.branchName ?? 'your branch'} and starts as pending until you approve them.`} />
      )}

      <GoldButton
        label={busy ? 'Creating…' : 'Create staff account'}
        icon="person-add-outline"
        loading={busy}
        disabled={!canSubmit}
        onPress={submit}
        style={{ marginTop: 14 }}
      />

      <BusyOverlay visible={busy} label="Creating account…" />
    </AdminPage>
  );
}
