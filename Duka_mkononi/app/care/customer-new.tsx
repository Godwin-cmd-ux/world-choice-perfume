import { router } from 'expo-router';
import { useState } from 'react';
import { Banner, AuthField } from '../../components/authkit';
import { AdminPage, BusyOverlay } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import { createCustomer } from '../../lib/careApi';
import { CC_ACCENT } from '../../lib/theme';

/**
 * New client — the website's customer-care/customers/create form. A phone
 * number that already exists answers { duplicate: true } and the app opens
 * that record, exactly like the website's "Showing their record instead."
 * redirect.
 */
export default function CareCustomerNew() {
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [whatsapp, setWhatsapp] = useState('');
  const [email, setEmail] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});

  const submit = async () => {
    setBusy(true);
    setError(null);
    setFields({});
    try {
      const res = await createCustomer({
        name: name.trim(),
        phone: phone.trim() || undefined,
        whatsapp: whatsapp.trim() || undefined,
        email: email.trim() || undefined,
      });
      router.replace({ pathname: '/care/customer-detail', params: { id: String(res.customer.id), notice: res.message } });
    } catch (e) {
      const err = e as { message?: string; fields?: Record<string, string> };
      setError(err.message ?? 'Could not save the client. Please try again.');
      setFields(err.fields ?? {});
    } finally {
      setBusy(false);
    }
  };

  return (
    <AdminPage title="New Client" eyebrow="Customer Care" accent={CC_ACCENT.main} onBack={() => router.back()}>
      {error ? <Banner kind="error" message={error} /> : null}

      <AuthField
        label="Full name"
        value={name}
        onChangeText={setName}
        placeholder="Client name"
        icon="person-outline"
        autoCapitalize="words"
        error={fields.name}
      />
      <AuthField
        label="Phone"
        value={phone}
        onChangeText={setPhone}
        placeholder="07…"
        icon="call-outline"
        keyboardType="phone-pad"
        error={fields.phone}
      />
      <AuthField
        label="WhatsApp (optional)"
        value={whatsapp}
        onChangeText={setWhatsapp}
        placeholder="Defaults to the phone number"
        icon="logo-whatsapp"
        keyboardType="phone-pad"
        error={fields.whatsapp}
      />
      <AuthField
        label="Email (optional)"
        value={email}
        onChangeText={setEmail}
        placeholder="name@example.com"
        icon="mail-outline"
        keyboardType="email-address"
        error={fields.email}
      />

      <GoldButton label={busy ? 'Saving…' : 'Save Client'} icon="checkmark-outline" loading={busy} onPress={submit} style={{ marginTop: 8 }} />

      <BusyOverlay visible={busy} label="Saving client…" />
    </AdminPage>
  );
}
