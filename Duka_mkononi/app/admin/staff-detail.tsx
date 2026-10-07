import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, DataCard, GroupLabel, KV, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { Card, ErrorView, LoadingView, OutlineButton } from '../../components/ui';
import { errorMessage } from '../../lib/api';
import {
  changeStaffStatus,
  deleteStaff,
  fetchStaffDetail,
  toggleStaffStatus,
} from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';

const STATUSES = ['active', 'approved', 'pending', 'blocked', 'rejected'];

/**
 * Staff detail — the mobile twin of super-admin/staff/show.blade.php: the
 * account record plus its last 20 sales, expenses and audit actions, and
 * every status operation the website offers (block/unblock shortcut, set any
 * status, delete) each behind a confirmation.
 */
export default function StaffDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const userId = String(id ?? '');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchStaffDetail(userId),
    [userId],
  );

  const [busy, setBusy] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [confirm, setConfirm] = useState<'delete' | 'block' | null>(null);

  const user = data?.user;

  const run = async (kind: 'delete' | 'block' | 'status', status?: string) => {
    setConfirm(null);
    setBusy(kind);
    setActionError(null);
    setNotice(null);
    try {
      let res;
      if (kind === 'delete') res = await deleteStaff(userId);
      else if (kind === 'block') res = await toggleStaffStatus(userId);
      else res = await changeStaffStatus(userId, String(status));
      setNotice(res.message);
      if (kind === 'delete') {
        router.back();
        return;
      }
      await reload();
    } catch (e) {
      setActionError(errorMessage(e));
    } finally {
      setBusy(null);
    }
  };

  return (
    <AdminPage title="Staff Account" onBack={() => router.back()}>
      {loading ? <LoadingView label="Loading account…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data && user ? (
        <>
          {notice ? <Banner kind="success" message={notice} /> : null}
          {actionError ? <Banner kind="error" message={actionError} /> : null}

          <Card style={{ gap: 6 }}>
            <Text style={styles.name}>{user.name || 'Unnamed'}</Text>
            <Text style={styles.role}>{roleLabel(String(user.role ?? ''))}</Text>
            <View style={{ marginTop: 8, gap: 6 }}>
              <KV label="Email:" value={user.email} />
              <KV label="Phone:" value={user.phone} />
              <KV label="Branch:" value={user.branch?.name ?? null} />
              <KV
                label="Status:"
                value={user.status}
                tone={user.status === 'blocked' || user.status === 'rejected' ? 'danger' : 'success'}
              />
              <KV label="Registered:" value={user.created_at ? new Date(user.created_at).toLocaleDateString() : null} />
            </View>
          </Card>

          <StatGrid>
            <StatTile label="Recent Sales" value={`TZS ${Number(data.total_sales ?? 0).toLocaleString('en-US')}`} hint="Last 20 records" />
            <StatTile label="Recent Expenses" value={`TZS ${Number(data.total_expenses ?? 0).toLocaleString('en-US')}`} tone="danger" hint="Last 20 records" />
          </StatGrid>

          <GroupLabel>Change status</GroupLabel>
          <ChipRow>
            {STATUSES.map((s) => (
              <Chip
                key={s}
                label={s.charAt(0).toUpperCase() + s.slice(1)}
                active={user.status === s}
                onPress={() => (user.status === s ? undefined : run('status', s))}
              />
            ))}
          </ChipRow>
          <Text style={styles.hint}>Tap a status to apply it immediately (same rules as the website).</Text>

          <GroupLabel>Activity</GroupLabel>
          <Text style={styles.subHead}>Recent sales</Text>
          {data.sales.length === 0 ? (
            <Text style={styles.muted}>No sales recorded.</Text>
          ) : (
            data.sales.slice(0, 10).map((s) => {
              const sale = s as { id?: unknown; sale_number?: string; total?: number; created_at?: string };
              return (
                <DataCard
                  key={String(sale.id)}
                  title={sale.sale_number ?? `Sale #${String(sale.id)}`}
                  badge={`TZS ${Number(sale.total ?? 0).toLocaleString('en-US')}`}
                  lines={[sale.created_at ? new Date(sale.created_at).toLocaleString() : null]}
                />
              );
            })
          )}

          <Text style={styles.subHead}>Recent expenses</Text>
          {data.expenses.length === 0 ? (
            <Text style={styles.muted}>No expenses recorded.</Text>
          ) : (
            data.expenses.slice(0, 10).map((s) => {
              const exp = s as { id?: unknown; category?: string; amount?: number; created_at?: string };
              return (
                <DataCard
                  key={String(exp.id)}
                  title={exp.category ?? 'Expense'}
                  badge={`TZS ${Number(exp.amount ?? 0).toLocaleString('en-US')}`}
                  badgeTone="danger"
                  lines={[exp.created_at ? new Date(exp.created_at).toLocaleString() : null]}
                />
              );
            })
          )}

          <Text style={styles.subHead}>Audit log</Text>
          {data.audit_logs.length === 0 ? (
            <Text style={styles.muted}>No audit entries.</Text>
          ) : (
            data.audit_logs.map((s) => {
              const log = s as { id?: unknown; action?: string; created_at?: string };
              return (
                <DataCard key={String(log.id)} title={log.action ?? 'action'} lines={[log.created_at ? new Date(log.created_at).toLocaleString() : null]} />
              );
            })
          )}

          <GroupLabel>Danger zone</GroupLabel>
          <View style={styles.actions}>
            <OutlineButton
              label={user.status === 'blocked' ? 'Unblock' : 'Block'}
              icon="ban-outline"
              onPress={() => (user.status === 'blocked' ? run('block') : setConfirm('block'))}
              style={{ flex: 1, borderColor: COLORS.dangerBorder }}
            />
            <OutlineButton label="Delete" icon="trash-outline" onPress={() => setConfirm('delete')} style={{ flex: 1, borderColor: COLORS.dangerBorder }} />
          </View>
        </>
      ) : null}

      <ConfirmDialog
        visible={confirm === 'delete'}
        title="Delete this account?"
        message={`${user?.name ?? 'This account'} will be permanently removed, with its history. This cannot be undone.`}
        confirmLabel="Delete"
        danger
        loading={busy === 'delete'}
        onCancel={() => setConfirm(null)}
        onConfirm={() => run('delete')}
      />
      <ConfirmDialog
        visible={confirm === 'block'}
        title="Block this account?"
        message={`${user?.name ?? 'This account'} will be signed out everywhere and unable to log in until unblocked.`}
        confirmLabel="Block"
        danger
        loading={busy === 'block'}
        onCancel={() => setConfirm(null)}
        onConfirm={() => run('block')}
      />
      <BusyOverlay visible={busy !== null} label="Saving…" />
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
  hint: { color: COLORS.textMuted, fontSize: 12.5 },
  subHead: { color: COLORS.text, fontSize: 15, fontWeight: '700', marginTop: 6 },
  muted: { color: COLORS.textMuted, fontSize: 13 },
  actions: { flexDirection: 'row', gap: 10 },
});
