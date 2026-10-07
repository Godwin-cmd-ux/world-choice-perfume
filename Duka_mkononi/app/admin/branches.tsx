import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import {
  AdminPage,
  BusyOverlay,
  ConfirmDialog,
  DataCard,
  GroupLabel,
  useAsyncData,
} from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import {
  deactivateBranch,
  fetchBranches,
  purgeBranch,
  type BranchListItem,
} from '../../lib/adminApi';
import { errorMessage } from '../../lib/api';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * Branches — the mobile twin of super-admin/branches/index.blade.php.
 * Each desktop table row becomes a tappable card showing the branch's status,
 * cashiers and staff roster; the same actions live inside it: Edit (opens the
 * shared form), Deactivate, and permanent Delete (only offered when already
 * inactive, with the same server-side safeguards and a confirmation dialog).
 */
export default function Branches() {
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(
    () => fetchBranches(),
    [],
  );
  const [openId, setOpenId] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [confirm, setConfirm] = useState<{ kind: 'deactivate' | 'purge'; branch: BranchListItem } | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const branches = data?.branches ?? [];

  const runAction = async (kind: 'deactivate' | 'purge', branch: BranchListItem) => {
    setConfirm(null);
    setBusy(kind);
    setActionError(null);
    try {
      const res = kind === 'deactivate' ? await deactivateBranch(String(branch.id)) : await purgeBranch(String(branch.id));
      setNotice(res.message);
      await reload();
    } catch (e) {
      setActionError(errorMessage(e));
    } finally {
      setBusy(null);
    }
  };

  return (
    <AdminPage
      title="Branches"
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={refresh}
      action={
        <Pressable
          onPress={() => router.push('/admin/branch-form')}
          style={({ pressed }) => [styles.addBtn, pressed && styles.pressed]}
          accessibilityRole="button"
          accessibilityLabel="Create branch"
        >
          <Ionicons name="add" size={20} color="#1A1400" />
          <Text style={styles.addText}>New</Text>
        </Pressable>
      }
    >
      {loading ? <LoadingView label="Loading branches…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {notice ? <Banner kind="success" message={notice} /> : null}
          {actionError ? <Banner kind="error" message={actionError} /> : null}
          {error ? <Banner kind="error" message={error} /> : null}

          <GroupLabel right={<Text style={styles.count}>{branches.length} branches</Text>}>
            All branches
          </GroupLabel>

          {branches.length === 0 ? (
            <EmptyView icon="storefront-outline" title="No branches yet" hint="Tap New to create the first branch." />
          ) : (
            branches.map((branch) => {
              const open = openId === String(branch.id);
              return (
                <DataCard
                  key={String(branch.id)}
                  title={branch.name}
                  subtitle={branch.address ?? undefined}
                  badge={branch.is_active === false ? 'Inactive' : 'Active'}
                  badgeTone={branch.is_active === false ? 'muted' : 'success'}
                  lines={[
                    `Cashiers: ${branch.cashiers_count ?? 0}  ·  Staff: ${branch.users?.length ?? 0}`,
                    branch.category ? `Category: ${String(branch.category).replaceAll('_', ' ')}` : null,
                  ]}
                  onPress={() => setOpenId(open ? null : String(branch.id))}
                >
                  {open ? (
                    <View style={styles.panel}>
                      {(branch.users ?? []).length > 0 ? (
                        <View style={styles.roster}>
                          <Text style={styles.panelTitle}>Staff</Text>
                          {(branch.users ?? []).map((u) => (
                            <Text key={String(u.id)} style={styles.rosterLine} numberOfLines={1}>
                              {u.name} — {roleLabel(String(u.role ?? ''))} · {u.status ?? 'active'}
                            </Text>
                          ))}
                        </View>
                      ) : (
                        <Text style={styles.muted}>No staff assigned to this branch.</Text>
                      )}

                      <View style={styles.actions}>
                        <ActionBtn
                          icon="create-outline"
                          label="Edit"
                          onPress={() =>
                            router.push({ pathname: '/admin/branch-form', params: { id: String(branch.id) } })
                          }
                        />
                        {branch.is_active !== false ? (
                          <ActionBtn
                            icon="pause-circle-outline"
                            label="Deactivate"
                            tone="warning"
                            onPress={() => setConfirm({ kind: 'deactivate', branch })}
                          />
                        ) : (
                          <ActionBtn
                            icon="trash-outline"
                            label="Delete"
                            tone="danger"
                            onPress={() => setConfirm({ kind: 'purge', branch })}
                          />
                        )}
                      </View>
                    </View>
                  ) : null}
                </DataCard>
              );
            })
          )}
        </>
      ) : null}

      <ConfirmDialog
        visible={!!confirm}
        title={confirm?.kind === 'purge' ? `Delete ${confirm?.branch.name}?` : `Deactivate ${confirm?.branch.name}?`}
        message={
          confirm?.kind === 'purge'
            ? 'This permanently deletes the branch and ALL of its data — stock, sales, orders and its staff accounts. This cannot be undone.'
            : 'The branch will stop appearing to customers and staff. You can still edit it, and delete it permanently afterwards.'
        }
        confirmLabel={confirm?.kind === 'purge' ? 'Delete Forever' : 'Deactivate'}
        danger
        onCancel={() => setConfirm(null)}
        onConfirm={() => (confirm ? runAction(confirm.kind, confirm.branch) : undefined)}
      />
      <BusyOverlay visible={busy !== null} label={busy === 'purge' ? 'Deleting…' : 'Deactivating…'} />
    </AdminPage>
  );
}

function ActionBtn({
  icon,
  label,
  tone = 'gold',
  onPress,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  tone?: 'gold' | 'warning' | 'danger';
  onPress: () => void;
}) {
  const color = tone === 'danger' ? COLORS.danger : tone === 'warning' ? COLORS.warning : COLORS.gold;
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.actionBtn, { borderColor: color + '55' }, pressed && styles.pressed]} accessibilityRole="button">
      <Ionicons name={icon} size={15} color={color} />
      <Text style={[styles.actionText, { color }]}>{label}</Text>
    </Pressable>
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
    gap: 3,
    backgroundColor: COLORS.gold,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  addText: { color: '#1A1400', fontWeight: '800', fontSize: 13 },
  pressed: { opacity: 0.75 },
  count: { color: COLORS.textMuted, fontSize: 12 },
  panel: { gap: 10, borderTopWidth: 1, borderTopColor: COLORS.border, paddingTop: 10 },
  panelTitle: {
    color: COLORS.textMuted,
    fontSize: 10.5,
    fontWeight: '700',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  roster: { gap: 4 },
  rosterLine: { color: COLORS.textSecondary, fontSize: 13 },
  muted: { color: COLORS.textMuted, fontSize: 12.5 },
  actions: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderWidth: 1,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 13,
    paddingVertical: 8,
    backgroundColor: COLORS.bgRaised,
  },
  actionText: { fontSize: 13, fontWeight: '700' },
});
