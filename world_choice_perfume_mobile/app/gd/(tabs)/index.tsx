import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner } from '../../../components/authkit';
import { ConfirmDialog, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { deleteGdPost, fetchGdDashboard, type GdDashboardPayload, type GdPost } from '../../../lib/gdApi';
import { COLORS, GD_ACCENT, RADIUS } from '../../../lib/theme';
import { GdMenuButton } from '../../../components/gdsidebar';

/**
 * GD dashboard — the mobile twin of graphic-designer/dashboard.blade.php:
 * the same five stat cards (Total / Approved / Pending / Rejected / Today)
 * and the same "Rejected Posts" list with Redesign & Submit (→ news edit)
 * and Delete, or the "all approved" empty state.
 */
export default function GdDashboard() {
  // Screens draw edge-to-edge, so the page keeps its own header clear of the status bar.
  const insets = useSafeAreaInsets();
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<GdDashboardPayload>(
    () => fetchGdDashboard(),
    [],
  );

  const [pendingDelete, setPendingDelete] = useState<GdPost | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const confirmDelete = async () => {
    if (!pendingDelete || deleting) return;
    setDeleting(true);
    setActionError(null);
    try {
      await deleteGdPost(String(pendingDelete.id));
      setPendingDelete(null);
      await reload();
    } catch (e) {
      setActionError(e instanceof Error ? e.message : 'Something went wrong. Please try again.');
    } finally {
      setDeleting(false);
    }
  };

  if (loading) return <LoadingView label="Loading dashboard…" />;
  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }
  if (error && !data) return <ErrorView message={error} onRetry={reload} />;
  if (!data) return <EmptyView icon="grid-outline" title="No dashboard data" hint="Pull to refresh." />;

  const c = data.counts;

  return (
    <View style={[styles.root, { paddingTop: insets.top }]}>
      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={GD_ACCENT.main} />}
      >
        <View>
          <GdMenuButton />
          <Text style={[styles.eyebrow, { color: GD_ACCENT.main }]}>World Choice Perfumes</Text>
          <Text style={styles.h1}>Graphic Designer</Text>
        </View>

        {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

        {/* The website's five stat cards, phone-sized. */}
        <StatGrid>
          <StatTile label="Total Posts" value={c.total} />
          <StatTile label="Approved" value={c.approved} tone="success" />
          <StatTile label="Pending" value={c.pending} tone="warning" />
          <StatTile label="Rejected" value={c.rejected} tone={c.rejected > 0 ? 'danger' : 'gold'} />
          <StatTile label="Today" value={c.today} tone="info" />
        </StatGrid>

        <GroupLabel>Rejected posts</GroupLabel>
        {data.rejected.length === 0 ? (
          <View style={styles.allGood}>
            <Ionicons name="checkmark-circle-outline" size={22} color={GD_ACCENT.main} />
            <Text style={styles.allGoodText}>All your posts have been approved. Great work!</Text>
          </View>
        ) : (
          data.rejected.map((post) => (
            <DataCard
              key={String(post.id)}
              title={post.title}
              subtitle={post.rejection_reason ? `Reason: ${post.rejection_reason}` : undefined}
              lines={[formatDate(post.created_at)]}
              badge="Rejected"
              badgeTone="danger"
            >
              <View style={styles.rowActions}>
                <Pressable
                  onPress={() => router.push({ pathname: '/gd/news-form', params: { id: String(post.id) } })}
                  style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <Ionicons name="create-outline" size={15} color={GD_ACCENT.light} />
                  <Text style={styles.actionText}>Redesign &amp; Submit</Text>
                </Pressable>
                <Pressable
                  onPress={() => setPendingDelete(post)}
                  style={({ pressed }) => [styles.actionBtn, styles.actionDanger, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <Ionicons name="trash-outline" size={15} color={COLORS.danger} />
                  <Text style={[styles.actionText, { color: COLORS.danger }]}>Delete</Text>
                </Pressable>
              </View>
            </DataCard>
          ))
        )}
      </ScrollView>

      <ConfirmDialog
        visible={pendingDelete !== null}
        title="Delete post?"
        message={`“${pendingDelete?.title ?? ''}” will be removed permanently. This cannot be undone.`}
        confirmLabel="Delete"
        danger
        loading={deleting}
        onCancel={() => setPendingDelete(null)}
        onConfirm={confirmDelete}
      />
    </View>
  );
}

function formatDate(value?: string | null): string {
  if (!value) return '';
  const d = new Date(value);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleDateString('en-US', { timeZone: 'Africa/Dar_es_Salaam', month: 'short', day: 'numeric', year: 'numeric' });
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  scroll: { padding: 16, paddingBottom: 40, gap: 12 },
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  eyebrow: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 3,
    textTransform: 'uppercase',
  },
  h1: { color: COLORS.text, fontSize: 25, fontWeight: '800', marginTop: 4 },
  allGood: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: GD_ACCENT.soft,
    borderWidth: 1,
    borderColor: GD_ACCENT.border,
    borderRadius: RADIUS.md,
    padding: 14,
  },
  allGoodText: { color: COLORS.text, fontSize: 14, fontWeight: '600', flex: 1 },
  rowActions: { flexDirection: 'row', gap: 8 },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: RADIUS.md,
    backgroundColor: GD_ACCENT.soft,
    borderWidth: 1,
    borderColor: GD_ACCENT.border,
  },
  actionDanger: {
    backgroundColor: COLORS.dangerBg,
    borderColor: COLORS.dangerBorder,
  },
  actionText: { color: GD_ACCENT.light, fontSize: 13, fontWeight: '700' },
  pressed: { opacity: 0.75 },
});
