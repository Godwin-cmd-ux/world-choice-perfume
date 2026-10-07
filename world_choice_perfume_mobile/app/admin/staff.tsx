import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, SearchInput, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchStaff, type AdminUserRow } from '../../lib/adminApi';
import { COLORS, RADIUS } from '../../lib/theme';

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
 * block/status/delete live, and the same "create staff account" entry point.
 */
export default function Staff() {
  const [role, setRole] = useState('');
  const [status, setStatus] = useState('');
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(
    () => fetchStaff({ role: role || undefined, status: status || undefined, search: query || undefined }),
    [role, status, query],
  );

  const users: AdminUserRow[] = data?.users ?? [];

  return (
    <AdminPage
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
          <Ionicons name="person-add-outline" size={16} color="#1A1400" />
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
              />
            ))
          )}
        </>
      ) : null}
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
    backgroundColor: COLORS.gold,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  addText: { color: '#1A1400', fontWeight: '800', fontSize: 13 },
  pressed: { opacity: 0.75 },
  applyHint: { color: COLORS.gold, fontSize: 13, fontWeight: '600' },
  count: { color: COLORS.textMuted, fontSize: 12 },
});
