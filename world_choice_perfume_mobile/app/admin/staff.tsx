import { Ionicons } from '@expo/vector-icons';
import { router, useFocusEffect } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import {
  AdminPage,
  BusyOverlay,
  Chip,
  ChipRow,
  ConfirmDialog,
  DataCard,
  GroupLabel,
  SearchInput,
  messageOf,
  useAsyncData,
} from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { deleteStaff, fetchStaff, type AdminUserRow } from '../../lib/adminApi';
import { BUTTON, COLORS, RADIUS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

const ROLES = [
  { key: '', label: 'All roles' },
  { key: 'super_admin', label: 'Super Admins' },
  { key: 'branch_admin', label: 'Branch Admins' },
  { key: 'cashier', label: 'Cashiers' },
  { key: 'stock_manager', label: 'Stock Managers' },
  { key: 'seller', label: 'Sellers' },
  { key: 'customer_care', label: 'Customer Care' },
  { key: 'graphic_designer', label: 'Graphic Designers' },
];

const STATUSES = [
  { key: '', label: 'Any status' },
  { key: 'active', label: 'Active' },
  { key: 'approved', label: 'Approved' },
  { key: 'pending', label: 'Pending' },
  { key: 'blocked', label: 'Blocked' },
  { key: 'rejected', label: 'Rejected' },
];

/**
 * Staff — the mobile twin of super-admin/staff/index.blade.php: the same
 * server-side role/status filters and name/email/phone search (client shows
 * them as chips + a search box), tap-through to the account detail where
 * block/status live, the same per-row delete the website's list offers (never
 * for the super admin, who is the only one able to restore anything), and the
 * same "create staff account" entry point.
 */
export default function Staff() {
  const [role, setRole] = useState('');
  const [status, setStatus] = useState('');
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');

  const [confirmDelete, setConfirmDelete] = useState<AdminUserRow | null>(null);
  const [deleting, setDeleting] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [failure, setFailure] = useState<string | null>(null);

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(
    () => fetchStaff({ role: role || undefined, status: status || undefined, search: query || undefined }),
    [role, status, query],
  );

  const users: AdminUserRow[] = data?.users ?? [];

  // Coming back from the detail screen (deleted, blocked) or the create form
  // must show the change without a pull-to-refresh. The first focus is the
  // mount that already loaded, so it is skipped.
  const reloadRef = useRef(reload);
  reloadRef.current = reload;
  const firstFocus = useRef(true);
  useFocusEffect(
    useCallback(() => {
      if (firstFocus.current) {
        firstFocus.current = false;
        return;
      }
      reloadRef.current();
    }, []),
  );

  const remove = async (user: AdminUserRow) => {
    setConfirmDelete(null);
    setDeleting(String(user.id));
    setNotice(null);
    setFailure(null);
    try {
      const result = await deleteStaff(String(user.id));
      setNotice(result.message);
      reload();
    } catch (e) {
      setFailure(messageOf(e));
    } finally {
      setDeleting(null);
    }
  };

  return (
    <AdminPage
      onMenu={adminMenu.open}
      title="Staff"
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={refresh}
      action={
        <Pressable
          onPress={() => router.push('/admin/staff-form')}
          style={({ pressed }) => [styles.addBtn, pressed && styles.pressed]}
          accessibilityRole="button"
          accessibilityLabel="Create staff account"
        >
          <Ionicons name="person-add-outline" size={16} color={BUTTON.text} />
          <Text style={styles.addText}>New</Text>
        </Pressable>
      }
    >
      {loading ? <LoadingView label="Loading staff…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}
          {notice ? <Banner kind="success" message={notice} /> : null}
          {failure ? <Banner kind="error" message={failure} /> : null}

          <SearchInput value={search} onChangeText={setSearch} placeholder="Name, email or phone…" />
          {search !== query ? (
            <Text style={styles.applyHint} onPress={() => setQuery(search)}>
              Tap to apply “{search}”
            </Text>
          ) : null}

          <ChipRow>
            {ROLES.map((r) => (
              <Chip key={r.key || 'all'} label={r.label} active={role === r.key} onPress={() => setRole(r.key)} />
            ))}
          </ChipRow>
          <ChipRow>
            {STATUSES.map((s) => (
              <Chip key={s.key || 'any'} label={s.label} active={status === s.key} onPress={() => setStatus(s.key)} />
            ))}
          </ChipRow>

          <GroupLabel right={<Text style={styles.count}>{users.length} shown</Text>}>Accounts</GroupLabel>

          {users.length === 0 ? (
            <EmptyView icon="people-outline" title="No staff match" hint="Try a different filter or search." />
          ) : (
            users.map((u) => (
              <DataCard
                key={String(u.id)}
                title={u.name || 'Unnamed'}
                subtitle={u.email ?? undefined}
                badge={u.status ?? undefined}
                badgeTone={
                  u.status === 'blocked' || u.status === 'rejected'
                    ? 'danger'
                    : u.status === 'pending'
                      ? 'warning'
                      : 'success'
                }
                lines={[
                  u.role ? roleLabel(u.role) : null,
                  u.branch?.name ?? null,
                  u.phone ?? null,
                ]}
                onPress={() => router.push({ pathname: '/admin/staff-detail', params: { id: String(u.id) } })}
                right={
                  u.role === 'super_admin' ? undefined : (
                    <Pressable
                      onPress={() => setConfirmDelete(u)}
                      hitSlop={10}
                      style={({ pressed }) => [styles.trash, pressed && styles.pressed]}
                      accessibilityRole="button"
                      accessibilityLabel={`Delete ${u.name ?? 'this staff account'}`}
                    >
                      <Ionicons name="trash-outline" size={17} color={COLORS.danger} />
                    </Pressable>
                  )
                }
              />
            ))
          )}
        </>
      ) : null}

      <ConfirmDialog
        visible={confirmDelete !== null}
        title="Delete this account?"
        message={`${confirmDelete?.name ?? 'This account'} is removed everywhere, together with their sales, expenses and activity records. This cannot be undone.`}
        confirmLabel="Delete"
        danger
        loading={deleting !== null}
        onCancel={() => setConfirmDelete(null)}
        onConfirm={() => confirmDelete && remove(confirmDelete)}
      />
      <BusyOverlay visible={deleting !== null} label="Deleting…" />
    </AdminPage>
  );
}

function roleLabel(role: string): string {
  return role
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

const styles = StyleSheet.create({
  addBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: BUTTON.fill,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  addText: { color: BUTTON.text, fontWeight: '800', fontSize: 13 },
  trash: { padding: 4 },
  pressed: { opacity: 0.75 },
  applyHint: { color: COLORS.gold, fontSize: 13, fontWeight: '600' },
  count: { color: COLORS.textMuted, fontSize: 12 },
});
