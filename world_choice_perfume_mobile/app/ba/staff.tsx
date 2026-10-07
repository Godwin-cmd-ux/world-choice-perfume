import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, DataCard, GroupLabel, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { approveBaStaff, fetchBaStaff, rejectBaStaff, type BaStaffMember } from '../../lib/baApi';
import { formatDateTime, formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { BA_ACCENT, COLORS } from '../../lib/theme';

const ROLE_TABS = [
  { key: '', label: 'All' },
  { key: 'cashier', label: 'Cashiers' },
  { key: 'seller', label: 'Sellers' },
  { key: 'stock_manager', label: 'Stock Mgrs' },
  { key: 'customer_care', label: 'Customer Care' },
] as const;

const STATUS_TABS = [
  { key: '', label: 'Any status' },
  { key: 'approved', label: 'Approved' },
  { key: 'active', label: 'Active' },
  { key: 'pending', label: 'Pending' },
  { key: 'rejected', label: 'Rejected' },
] as const;

/**
 * Staffs — the website's branch-admin/staffs index: the branch's team
 * (stock manager / seller / customer care / cashier) with each member's
 * sales total, role and status filters, and the approve / reject actions
 * the Blade table offers. Everything is branch-scoped server-side; a 403
 * for anyone outside this branch kicks back to /staff.
 */
export default function BaStaff() {
  const { notice } = useLocalSearchParams<{ notice?: string }>();
  const [role, setRole] = useState('');
  const [status, setStatus] = useState('');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchBaStaff({ role: role || undefined, status: status || undefined }),
    [role, status],
  );
  const [refreshing, setRefreshing] = useState(false);

  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [actionOk, setActionOk] = useState<string | null>(null);
  const [rejecting, setRejecting] = useState<BaStaffMember | null>(null);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const decide = async (member: BaStaffMember, decision: 'approve' | 'reject') => {
    setBusyKey(String(member.id));
    setActionError(null);
    setActionOk(null);
    try {
      const res = decision === 'approve' ? await approveBaStaff(member.id) : await rejectBaStaff(member.id);
      setActionOk(res.message);
      await reload();
    } catch (e) {
      setActionError(String((e as { message?: string }).message ?? e));
    } finally {
      setBusyKey(null);
      setRejecting(null);
    }
  };

  const statusTone = (s?: string | null) =>
    s === 'approved' || s === 'active' ? 'success' : s === 'pending' ? 'warning' : s === 'rejected' ? 'danger' : 'muted';

  return (
    <AdminPage
      title="Staffs"
      eyebrow="Branch Admin"
      accent={BA_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        <Pressable
          onPress={() => router.push('/ba/staff-new')}
          style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
          accessibilityRole="button"
          accessibilityLabel="Add staff member"
        >
          <Text style={styles.newBtnText}>+ New</Text>
        </Pressable>
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {actionError ? <Banner kind="error" message={actionError} /> : null}
      {actionOk ? <Banner kind="success" message={actionOk} /> : notice ? <Banner kind="success" message={String(notice)} /> : null}

      <ChipRow>
        {ROLE_TABS.map((t) => (
          <Chip key={t.key || 'all'} label={t.label} active={role === t.key} onPress={() => setRole(t.key)} />
        ))}
      </ChipRow>
      <ChipRow>
        {STATUS_TABS.map((t) => (
          <Chip key={t.key || 'any'} label={t.label} active={status === t.key} onPress={() => setStatus(t.key)} />
        ))}
      </ChipRow>

      {loading && !data ? (
        <LoadingView label="Loading team…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.staff.length === 0 ? (
        <EmptyView icon="people-outline" title="No staff match" hint="Adjust the filters or add a member with + New." />
      ) : (
        <>
          <GroupLabel right={<Text style={styles.muted}>{data.staff.length} members</Text>}>This branch</GroupLabel>
          {data.staff.map((member) => (
            <DataCard
              key={String(member.id)}
              title={member.name ?? 'Staff member'}
              subtitle={member.email ?? undefined}
              badge={member.status ?? 'unknown'}
              badgeTone={statusTone(member.status)}
              lines={[
                `${(member.role ?? '').replace(/_/g, ' ')}${member.phone ? ` · ${member.phone}` : ''}`,
                `Sales: ${formatMoney(member.total_sales ?? 0)}`,
                member.created_at ? `Joined ${formatDateTime(member.created_at)}` : null,
              ].filter(Boolean) as string[]}
            >
              {member.status === 'pending' || member.status === 'active' || member.status === 'approved' ? (
                <Pressable
                  onPress={() => setRejecting(member)}
                  style={({ pressed }) => [styles.rejectBtn, pressed && { opacity: 0.7 }]}
                  accessibilityRole="button"
                >
                  <Text style={styles.rejectText}>Reject</Text>
                </Pressable>
              ) : null}
              {member.status === 'pending' || member.status === 'rejected' ? (
                <Pressable
                  onPress={() => decide(member, 'approve')}
                  style={({ pressed }) => [styles.approveBtn, pressed && { opacity: 0.7 }]}
                  accessibilityRole="button"
                >
                  <Text style={styles.approveText}>{busyKey === String(member.id) ? 'Working…' : 'Approve'}</Text>
                </Pressable>
              ) : null}
            </DataCard>
          ))}
        </>
      )}

      <ConfirmDialog
        visible={rejecting !== null}
        title="Reject this member?"
        message={`${rejecting?.name ?? 'This member'} will no longer be able to sign in. You can approve them again later.`}
        confirmLabel="Reject"
        danger
        loading={busyKey !== null}
        onConfirm={() => rejecting && decide(rejecting, 'reject')}
        onCancel={() => setRejecting(null)}
      />

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  muted: { color: COLORS.textMuted, fontSize: 11 },
  newBtn: {
    backgroundColor: BA_ACCENT.soft,
    borderColor: BA_ACCENT.border,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  newBtnText: { color: BA_ACCENT.light, fontWeight: '800', fontSize: 12 },
  approveBtn: {
    backgroundColor: 'rgba(52, 211, 153, 0.14)',
    borderColor: 'rgba(52, 211, 153, 0.35)',
    borderWidth: 1,
    borderRadius: 999,
    paddingVertical: 9,
    alignItems: 'center',
  },
  approveText: { color: COLORS.success, fontWeight: '800', fontSize: 12.5 },
  rejectBtn: {
    backgroundColor: 'transparent',
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: 999,
    paddingVertical: 9,
    alignItems: 'center',
  },
  rejectText: { color: COLORS.textSecondary, fontWeight: '800', fontSize: 12.5 },
});
