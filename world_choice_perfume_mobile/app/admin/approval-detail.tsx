import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, ConfirmDialog, GroupLabel, KV, useAsyncData } from '../../components/adminkit';
import { Card, ErrorView, LoadingView, OutlineButton } from '../../components/ui';
import { errorMessage } from '../../lib/api';
import { approveUser, fetchApproval, rejectUser } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

/**
 * Approval detail — the mobile twin of super-admin/cashiers/show.blade.php:
 * the account's full record, then Approve / Reject with a confirmation step.
 * Both operations hit the same server endpoints as the website, which also
 * activate/deactivate a branch admin's branch and file the notification.
 */
export default function ApprovalDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const userId = String(id ?? '');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchApproval(userId),
    [userId],
  );

  const [confirm, setConfirm] = useState<'approve' | 'reject' | null>(null);
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const run = async (kind: 'approve' | 'reject') => {
    setConfirm(null);
    setBusy(true);
    setActionError(null);
    try {
      const res = kind === 'approve' ? await approveUser(userId) : await rejectUser(userId);
      setNotice(res.message);
      await reload();
    } catch (e) {
      setActionError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  const user = data?.user;

  return (
    <AdminPage title="Account Review" onBack={() => router.back()} onMenu={adminMenu.open}>
      {loading ? <LoadingView label="Loading account…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {user ? (
        <>
          {notice ? <Banner kind="success" message={notice} /> : null}
          {actionError ? <Banner kind="error" message={actionError} /> : null}

          <Card style={{ gap: 6 }}>
            <Text style={styles.name}>{user.name || 'Unnamed account'}</Text>
            <Text style={styles.role}>{roleLabel(String(user.role ?? ''))}</Text>
            <View style={{ marginTop: 10, gap: 6 }}>
              <KV label="Email:" value={user.email} />
              <KV label="Phone:" value={user.phone} />
              <KV label="Branch:" value={user.branch?.name ?? (user.branch_id ? `Branch #${user.branch_id}` : null)} />
              <KV label="Status:" value={user.status} tone={user.status === 'pending' ? 'warning' : undefined} />
              <KV label="Registered:" value={user.created_at ? new Date(user.created_at).toLocaleString() : null} />
            </View>
          </Card>

          <GroupLabel>Decision</GroupLabel>
          <Text style={styles.hint}>
            Approving lets this account sign in immediately; rejecting blocks it. Branch admin approvals also switch their
            branch on, exactly like the website.
          </Text>

          <View style={styles.actions}>
            <OutlineButton label="Reject" icon="close-circle-outline" onPress={() => setConfirm('reject')} style={styles.rejectBtn} />
            <OutlineButton label="Approve" icon="checkmark-circle-outline" onPress={() => setConfirm('approve')} style={styles.approveBtn} />
          </View>
        </>
      ) : null}

      <ConfirmDialog
        visible={!!confirm}
        title={confirm === 'reject' ? 'Reject this account?' : 'Approve this account?'}
        message={
          confirm === 'reject'
            ? `${user?.name ?? 'This account'} will be rejected and cannot sign in. You can change this later from Staff.`
            : `${user?.name ?? 'This account'} will be able to sign in right away.`
        }
        confirmLabel={confirm === 'reject' ? 'Reject' : 'Approve'}
        danger={confirm === 'reject'}
        loading={busy}
        onCancel={() => setConfirm(null)}
        onConfirm={() => (confirm ? run(confirm) : undefined)}
      />
      <BusyOverlay visible={busy} label="Saving decision…" />
    </AdminPage>
  );
}

function roleLabel(role: string): string {
  return (role || 'staff')
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

const styles = StyleSheet.create({
  name: { color: COLORS.text, fontSize: 20, fontWeight: '800' },
  role: { color: COLORS.gold, fontSize: 13, letterSpacing: 1.4, textTransform: 'uppercase', marginTop: 3 },
  hint: { color: COLORS.textSecondary, fontSize: 13.5, lineHeight: 20 },
  actions: { flexDirection: 'row', gap: 10 },
  rejectBtn: { flex: 1, borderColor: COLORS.dangerBorder },
  approveBtn: { flex: 1, backgroundColor: COLORS.successBg, borderColor: COLORS.successBorder },
});
