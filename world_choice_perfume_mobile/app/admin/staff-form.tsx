import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, useAsyncData } from '../../components/adminkit';
import { ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { errorMessage, isApiError } from '../../lib/api';
import { createStaff, fetchStaffOptions, type StaffFormFields } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';

const ROLE_LABELS: Record<string, string> = {
  branch_admin: 'Branch Admin',
  cashier: 'Cashier',
  stock_manager: 'Stock Manager',
  seller: 'Seller',
  customer_care: 'Customer Care',
  graphic_designer: 'Graphic Designer',
};

/**
 * Create staff account — the mobile twin of super-admin/staff/create.blade
 * form: same fields and server rules (name, unique email, phone, min-8
 * password + confirmation, role, branch), server errors mapped per field.
 * The account is created active and can sign in immediately, exactly like
 * the website's flow.
 */
export default function StaffForm() {
  const opts = useAsyncData(() => fetchStaffOptions(), []);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [role, setRole] = useState('cashier');
  const [branchId, setBranchId] = useState('');

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const submit = async () => {
    if (saving) return;
    setErrors({});
    setFormError(null);
    setSuccess(null);

    const local: Record<string, string> = {};
    if (!name.trim()) local.name = 'The name field is required.';
    if (!email.trim()) local.email = 'The email field is required.';
    else if (!/^\S+@\S+\.\S+$/.test(email.trim())) local.email = 'Enter a valid email address.';
    if (!password) local.password = 'The password field is required.';
    else if (password.length < 8) local.password = 'The password must be at least 8 characters.';
    if (password !== confirm) local.password_confirmation = 'The password confirmation does not match.';
    if (!branchId) local.branch_id = 'Select a branch.';
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }

    const fields: StaffFormFields = {
      name: name.trim(),
      email: email.trim(),
      phone: phone.trim(),
      password,
      password_confirmation: confirm,
      role,
      branch_id: branchId,
    };

    setSaving(true);
    try {
      const res = await createStaff(fields);
      setSuccess(res.message);
      setTimeout(() => router.back(), 1100);
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

  if (opts.loading || opts.error) {
    return (
      <AdminPage title="New Staff Account" onBack={() => router.back()}>
        {opts.loading ? <LoadingView label="Loading form…" /> : <ErrorView message={opts.error ?? 'Unable to load.'} onRetry={opts.reload} />}
      </AdminPage>
    );
  }

  return (
    <AdminPage title="New Staff Account" onBack={() => router.back()}>
      {success ? <Banner kind="success" message={success} /> : null}
      {formError ? <Banner kind="error" message={formError} /> : null}

      <AuthField
        label="Full Name *"
        value={name}
        onChangeText={setName}
        placeholder="John Doe"
        icon="person-outline"
        autoCapitalize="words"
        error={errors.name}
      />
      <AuthField
        label="Email *"
        value={email}
        onChangeText={setEmail}
        placeholder="you@example.com"
        icon="mail-outline"
        keyboardType="email-address"
        error={errors.email}
      />
      <AuthField
        label="Phone"
        value={phone}
        onChangeText={setPhone}
        placeholder="+255 7XX XXX XXX"
        icon="call-outline"
        keyboardType="phone-pad"
        error={errors.phone}
      />
      <AuthField
        label="Password *"
        value={password}
        onChangeText={setPassword}
        placeholder="Min. 8 characters"
        icon="lock-closed-outline"
        secure
        error={errors.password}
      />
      <AuthField
        label="Confirm Password *"
        value={confirm}
        onChangeText={setConfirm}
        placeholder="Repeat your password"
        icon="lock-closed-outline"
        secure
        error={errors.password_confirmation}
      />

      <GroupLabel>Role</GroupLabel>
      <ChipRow>
        {(opts.data?.roles ?? []).map((r) => (
          <Chip key={r} label={ROLE_LABELS[r] ?? r} active={role === r} onPress={() => setRole(r)} />
        ))}
      </ChipRow>
      {errors.role ? <Text style={styles.error}>{errors.role}</Text> : null}

      <GroupLabel>Branch</GroupLabel>
      <ChipRow>
        {(opts.data?.branches ?? []).map((b) => (
          <Chip key={String(b.id)} label={b.name} active={branchId === String(b.id)} onPress={() => setBranchId(String(b.id))} />
        ))}
      </ChipRow>
      {errors.branch_id ? <Text style={styles.error}>{errors.branch_id}</Text> : null}

      <GoldButton label="Create Account" onPress={submit} loading={saving} icon="person-add-outline" style={{ marginTop: 10 }} />
      <Text style={styles.note}>
        The account is created active — the person can sign in with the password you set here.
      </Text>
      <BusyOverlay visible={saving} label="Creating account…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  error: { color: COLORS.danger, fontSize: 12, marginTop: 6 },
  note: { color: COLORS.textMuted, fontSize: 12.5, lineHeight: 18, textAlign: 'center' },
});
