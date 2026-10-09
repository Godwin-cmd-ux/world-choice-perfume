import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import {
  Chip,
  ChipRow,
  ConfirmDialog,
  DataCard,
  GroupLabel,
  StatGrid,
  StatTile,
  useAsyncData,
} from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { deleteGdPost, fetchGdNews, type GdNewsPayload, type GdPost } from '../../../lib/gdApi';
import { COLORS, GD_ACCENT, RADIUS } from '../../../lib/theme';
import { GdMenuButton } from '../../../components/gdsidebar';

type Filter = 'all' | 'approved' | 'pending' | 'rejected';

/**
 * News list — the mobile twin of graphic-designer/news/index.blade.php: the
 * same five counts, then every own post as a card with Title (+ rejection
 * reason), Branch, Status pill and Date (Africa/Dar_es_Salaam), plus edit /
 * delete actions. The "+" action opens the create form.
 */
export default function GdNews() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData<GdNewsPayload>(
    () => fetchGdNews(),
    [],
  );

  const [filter, setFilter] = useState<Filter>('all');
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

  if (loading) return <LoadingView label="Loading posts…" />;
  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }
  if (error && !data) return <ErrorView message={error} onRetry={reload} />;

  const posts = (data?.posts ?? []).filter((p) => filter === 'all' || p.status === filter);
  const c = data?.counts;

  return (
    <View style={styles.root}>
      <View style={styles.scroll}>
        <View style={styles.headRow}>
          <View>
            <GdMenuButton />
            <Text style={[styles.eyebrow, { color: GD_ACCENT.main }]}>Graphic Designer</Text>
            <Text style={styles.h1}>News Posts</Text>
          </View>
          <Pressable
            onPress={() => router.push('/gd/news-form')}
            style={({ pressed }) => [styles.addBtn, pressed && styles.pressed]}
            accessibilityRole="button"
            accessibilityLabel="Create post"
          >
            <Ionicons name="add" size={22} color="#1A1400" />
          </Pressable>
        </View>

        {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

        {c ? (
          <StatGrid>
            <StatTile label="Total" value={c.total} />
            <StatTile label="Approved" value={c.approved} tone="success" />
            <StatTile label="Pending" value={c.pending} tone="warning" />
            <StatTile label="Rejected" value={c.rejected} tone={c.rejected > 0 ? 'danger' : 'gold'} />
            <StatTile label="Today" value={c.today} tone="info" />
          </StatGrid>
        ) : null}

        <ChipRow>
          <Chip label="All" active={filter === 'all'} onPress={() => setFilter('all')} />
          <Chip label="Approved" active={filter === 'approved'} onPress={() => setFilter('approved')} />
          <Chip label="Pending" active={filter === 'pending'} onPress={() => setFilter('pending')} />
          <Chip label="Rejected" active={filter === 'rejected'} onPress={() => setFilter('rejected')} />
        </ChipRow>

        {posts.length === 0 ? (
          <EmptyView
            icon="newspaper-outline"
            title={filter === 'all' ? 'No posts yet' : `No ${filter} posts`}
            hint={filter === 'all' ? 'Tap + to write your first post.' : 'Try another filter.'}
          />
        ) : (
          posts.map((post) => (
            <DataCard
              key={String(post.id)}
              title={post.title}
              subtitle={post.status === 'rejected' && post.rejection_reason ? `Reason: ${post.rejection_reason}` : undefined}
              lines={[
                post.branch?.name ? `Branch: ${post.branch.name}` : '',
                formatDate(post.created_at),
              ].filter(Boolean)}
              badge={post.status}
              badgeTone={post.status === 'approved' ? 'success' : post.status === 'rejected' ? 'danger' : 'warning'}
            >
              <View style={styles.rowActions}>
                <Pressable
                  onPress={() => router.push({ pathname: '/gd/news-form', params: { id: String(post.id) } })}
                  style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <Ionicons name="create-outline" size={15} color={GD_ACCENT.light} />
                  <Text style={styles.actionText}>Edit</Text>
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

        <GroupLabel>Every submit or edit re-enters customer care approval</GroupLabel>
      </View>

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
  headRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  eyebrow: { fontSize: 11, fontWeight: '700', letterSpacing: 3, textTransform: 'uppercase' },
  h1: { color: COLORS.text, fontSize: 25, fontWeight: '800', marginTop: 4 },
  addBtn: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: GD_ACCENT.main,
    alignItems: 'center',
    justifyContent: 'center',
  },
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
  actionDanger: { backgroundColor: COLORS.dangerBg, borderColor: COLORS.dangerBorder },
  actionText: { color: GD_ACCENT.light, fontSize: 13, fontWeight: '700' },
  pressed: { opacity: 0.75 },
});
