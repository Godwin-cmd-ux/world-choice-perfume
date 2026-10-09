import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchApprovals, type ApprovalPayload } from '../../../lib/adminApi';
import { COLORS } from '../../../lib/theme';
import { AdminMenuButton } from '../../../components/adminsidebar';

const ROLES = [
  { key: 'all', label: 'All roles' },
  { key: 'cashier', label: 'Cashiers' },
  { key: 'branch_admin', label: 'Branch Admins' },
  { key: 'stock_manager', label: 'Stock Managers' },
  { key: 'customer_care', label: 'Customer Care' },
  { key: 'seller', label: 'Sellers' },
  { key: 'graphic_designer', label: 'Graphic Designers' },
];

const STATUSES = [
  { key: 'pending', label: 'Pending' },
  { key: 'approved', label: 'Approved' },
  { key: 'rejected', label: 'Rejected' },
  { key: 'all', label: 'All' },
];

/**
 * Approvals tab — the mobile twin of super-admin/cashiers/index.blade.php
 * ("Approvals" in the website sidebar). Same role + status filters, same
 * 20-per-page paging, tap through to the account detail where Approve /
 * Reject live.
 */
export default function AdminApprovals() {
  const [role, setRole] = useState('all');
  const [status, setStatus] = useState('pending');
  const [page, setPage] = useState(1);

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<ApprovalPayload>(
    () => fetchApprovals({ role, status, page }),
    [role, status, page],
  );

  if (loading) return <LoadingView label="Loading approvals…" />;
  if (sessionExpired) {
    return (
      <View style={styles.guard}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }
  if (error && !data) return <ErrorView message={error} onRetry={reload} />;

  const users = data?.users ?? [];
  const totalPages = Math.max(1, Math.ceil((data?.total ?? 0) / (data?.per_page ?? 20)));

  return (
    <View style={styles.root}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={COLORS.gold} />}
        keyboardShouldPersistTaps="handled"
      >
        <View>
          <AdminMenuButton />
          <Text style={styles.eyebrow}>Staff accounts</Text>
          <Text style={styles.h1}>Approvals</Text>
        </View>

        {error ? <Banner kind="error" message={error} /> : null}

        <ChipRow>
          {ROLES.map((r) => (
            <Chip key={r.key} label={r.label} active={role === r.key} onPress={() => { setRole(r.key); setPage(1); }} />
          ))}
        </ChipRow>
        <ChipRow>
          {STATUSES.map((s) => (
            <Chip key={s.key} label={s.label} active={status === s.key} onPress={() => { setStatus(s.key); setPage(1); }} />
          ))}
        </ChipRow>

        <GroupLabel right={<Text style={styles.count}>{data?.total ?? 0} accounts</Text>}>
          {STATUSES.find((s) => s.key === status)?.label ?? 'Accounts'}
        </GroupLabel>

        {users.length === 0 ? (
          <EmptyView icon="checkmark-done-outline" title="Nothing to review" hint="No accounts match this filter." />
        ) : (
          users.map((u) => (
            <DataCard
              key={String(u.id)}
              title={u.name || 'Unnamed'}
              subtitle={u.email ?? undefined}
              badge={u.status ?? undefined}
              badgeTone={u.status === 'pending' ? 'warning' : u.status === 'approved' ? 'success' : 'muted'}
              lines={[
                u.role ? `Role: ${roleLabel(u.role)}` : null,
                u.branch ? `Branch: ${u.branch.name}` : null,
                u.phone ? `Phone: ${u.phone}` : null,
              ]}
              onPress={() => router.push({ pathname: '/admin/approval-detail', params: { id: String(u.id) } })}
            />
          ))
        )}

        {totalPages > 1 ? (
          <View style={styles.pager}>
            <Pressable
              style={[styles.pageBtn, page <= 1 && styles.pageBtnOff]}
              disabled={page <= 1}
              onPress={() => setPage((p) => Math.max(1, p - 1))}
            >
              <Text style={styles.pageBtnText}>Previous</Text>
            </Pressable>
            <Text style={styles.pageInfo}>
              {page} / {totalPages}
            </Text>
            <Pressable
              style={[styles.pageBtn, page >= totalPages && styles.pageBtnOff]}
              disabled={page >= totalPages}
              onPress={() => setPage((p) => p + 1)}
            >
              <Text style={styles.pageBtnText}>Next</Text>
            </Pressable>
          </View>
        ) : null}
      </ScrollView>
    </View>
  );
}

function roleLabel(role: string): string {
  return role
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  content: { padding: 16, paddingBottom: 40, gap: 12 },
  guard: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  eyebrow: {
    color: 'rgba(255, 193, 7, 0.65)',
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 3,
    textTransform: 'uppercase',
  },
  h1: { color: COLORS.text, fontSize: 25, fontWeight: '800', marginTop: 4 },
  count: { color: COLORS.textMuted, fontSize: 12 },
  pager: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 16, marginTop: 8 },
  pageBtn: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
  },
  pageBtnOff: { opacity: 0.4 },
  pageBtnText: { color: COLORS.goldLight, fontWeight: '700', fontSize: 13.5 },
  pageInfo: { color: COLORS.textSecondary, fontSize: 13.5, fontWeight: '600' },
});
