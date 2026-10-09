import { router } from 'expo-router';
import { useState } from 'react';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, useAsyncData } from '../../components/adminkit';
import { adminMenu } from '../../components/adminsidebar';
import { ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { errorMessage, isApiError } from '../../lib/api';
import { fetchAdminSettings, saveAdminSettings } from '../../lib/adminApi';

/**
 * Settings — the mobile twin of super-admin/settings.blade.php: view and
 * update the company's two secret codes (Super Admin secret + Staff secret
 * code), same server validation (min 4 / max 255). Values are masked like
 * passwords while typing; they are only ever fetched through the
 * role-guarded /admin/settings route, never embedded in the app.
 */
export default function AdminSettings() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchAdminSettings(), []);

  const [superSecret, setSuperSecret] = useState('');
  const [staffSecret, setStaffSecret] = useState('');
  const [hydrated, setHydrated] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  if (data && !hydrated) {
    setHydrated(true);
    setSuperSecret(data.super_admin_secret ?? '');
    setStaffSecret(data.staff_secret_code ?? '');
  }

  const submit = async () => {
    if (saving) return;
    setErrors({});
    setFormError(null);
    setSuccess(null);

    const local: Record<string, string> = {};
    if (superSecret.trim().length < 4) local.super_admin_secret = 'Minimum 4 characters.';
    if (staffSecret.trim().length < 4) local.staff_secret_code = 'Minimum 4 characters.';
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }

    setSaving(true);
    try {
      const res = await saveAdminSettings({
        super_admin_secret: superSecret.trim(),
        staff_secret_code: staffSecret.trim(),
      });
      setSuccess(res.message);
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

  return (
    <AdminPage title="Settings" onBack={() => router.back()} onMenu={adminMenu.open}>
      {loading ? <LoadingView label="Loading settings…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {success ? <Banner kind="success" message={success} /> : null}
          {formError ? <Banner kind="error" message={formError} /> : null}

          <GroupLabel>Company secret codes</GroupLabel>
          <AuthField
            label="Super Admin Secret"
            value={superSecret}
            onChangeText={setSuperSecret}
            placeholder="Super Admin registration secret"
            icon="shield-checkmark-outline"
            secure
            error={errors.super_admin_secret}
          />
          <AuthField
            label="Staff Secret Code"
            value={staffSecret}
            onChangeText={setStaffSecret}
            placeholder="Staff registration secret"
            icon="key-outline"
            secure
            error={errors.staff_secret_code}
          />
          <Banner
            kind="connection"
            message="These codes gate registrations on the website. Changing one takes effect immediately for every new registration."
          />

          <GoldButton label="Save Settings" onPress={submit} loading={saving} icon="save-outline" />
        </>
      ) : null}
      <BusyOverlay visible={saving} label="Saving settings…" />
    </AdminPage>
  );
}
