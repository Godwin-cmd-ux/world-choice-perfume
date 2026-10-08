import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, GroupLabel, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { approveNews, deleteNews, fetchNews, rejectNews, type CcNewsPost } from '../../lib/careApi';
import { formatDateTime } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../lib/theme';

/**
 * News moderation — HQ only. Two lists exactly like the website: designer
 * posts awaiting review (approve / reject with a reason / edit / delete)
 * and custom posts the team writes itself (compose → news-form, which
 * publishes immediately like the website's store).
 */
export default function CareNews() {
  const [tab, setTab] = useState<'designer' | 'custom'>('designer');
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchNews({ tab }), [tab]);

  const [rejectFor, setRejectFor] = useState<string | null>(null);
  const [rejectReason, setRejectReason] = useState('');
  const [deleteFor, setDeleteFor] = useState<string | null>(null);
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const act = async (key: string, fn: () => Promise<{ message: string }>) => {
    setBusyKey(key);
    setActionError(null);
    try {
      await fn();
      await reload();
    } catch (e) {
      setActionError((e as { message?: string }).message ?? 'That action failed.');
    } finally {
      setBusyKey(null);
    }
  };

  const blocked = !!error && /Head Quarters/i.test(error);
  const posts: CcNewsPost[] = tab === 'designer' ? (data?.designerPosts ?? []) : (data?.customPosts ?? []);

  return (
    <AdminPage
      title="News"
      eyebrow="Head Quarters"
      accent={CC_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => { setRefreshing(true); await reload(); setRefreshing(false); }}
      action={
        <Pressable
          onPress={() => router.push('/care/news-form')}
          style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
          accessibilityRole="button"
          accessibilityLabel="Compose news"
        >
          <Text style={styles.newBtnText}>+ Compose</Text>
        </Pressable>
      }
    >
      {blocked ? <Banner kind="error" message={error} /> : error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {actionError ? <Banner kind="error" message={actionError} /> : null}

      {!blocked ? (
        <ChipRow>
          <Chip label={`Designer (${data?.counts.designer ?? 0})`} active={tab === 'designer'} onPress={() => setTab('designer')} />
          <Chip label={`Custom (${data?.counts.custom ?? 0})`} active={tab === 'custom'} onPress={() => setTab('custom')} />
        </ChipRow>
      ) : null}
      {tab === 'designer' && data ? (
        <GroupLabel right={<Text style={styles.muted}>{data.counts.approved} approved · {data.counts.pending} pending · {data.counts.rejected} rejected</Text>}>Designer posts</GroupLabel>
      ) : null}

      {loading && !data ? (
        <LoadingView label="Loading news…" />
      ) : blocked ? (
        <EmptyView icon="lock-closed-outline" title="Head Quarters only" hint="News moderation lives on the HQ desk." />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : posts.length === 0 ? (
        <EmptyView icon="newspaper-outline" title="No posts here" hint={tab === 'designer' ? 'Designer submissions appear for review.' : 'Compose a custom post with + Compose.'} />
      ) : (
        posts.map((post) => {
          const key = String(post.id);
          const tone = post.status === 'approved' ? 'success' : post.status === 'rejected' ? 'danger' : 'warning';
          return (
            <View key={key} style={styles.card}>
              <View style={styles.cardHead}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.title} numberOfLines={2}>{post.title ?? 'Untitled'}</Text>
                  <Text style={styles.meta}>
                    {[post.author?.name, post.branch?.name, formatDateTime(post.created_at)].filter(Boolean).join(' · ')}
                  </Text>
                </View>
                <View style={[styles.pill, { borderColor: tone === 'success' ? 'rgba(52,211,153,.4)' : tone === 'danger' ? 'rgba(248,113,113,.4)' : 'rgba(252,211,77,.4)' }]}>
                  <Text style={[styles.pillText, { color: tone === 'success' ? COLORS.success : tone === 'danger' ? COLORS.danger : COLORS.warning }]}>
                    {post.status ?? 'pending'}
                  </Text>
                </View>
              </View>
              {post.content ? <Text style={styles.excerpt} numberOfLines={3}>{post.content}</Text> : null}
              {post.rejection_reason ? <Text style={styles.reason}>Rejected: {post.rejection_reason}</Text> : null}

              <View style={styles.actions}>
                {post.status !== 'approved' ? (
                  <Act label={busyKey === key ? '…' : 'Approve'} onPress={() => act(key, () => approveNews(post.id))} />
                ) : null}
                {post.status !== 'rejected' ? (
                  <Act label="Reject" tone="warn" onPress={() => { setRejectFor(key); setRejectReason(''); }} />
                ) : null}
                <Act label="Edit" tone="ghost" onPress={() => router.push({ pathname: '/care/news-form', params: { id: key } })} />
                <Act label="Delete" tone="danger" onPress={() => setDeleteFor(key)} />
              </View>

              {rejectFor === key ? (
                <View style={styles.reasonWrap}>
                  <AuthField
                    label="Rejection reason"
                    value={rejectReason}
                    onChangeText={setRejectReason}
                    placeholder="What should change?"
                    icon="close-circle-outline"
                    autoCapitalize="sentences"
                    maxLength={500}
                  />
                  <View style={styles.actions}>
                    <Act
                      label={busyKey === key ? '…' : 'Confirm Reject'}
                      tone="danger"
                      disabled={!rejectReason.trim() || busyKey === key}
                      onPress={() => {
                        const reason = rejectReason.trim();
                        setRejectFor(null);
                        act(key, () => rejectNews(post.id, reason));
                      }}
                    />
                    <Act label="Cancel" tone="ghost" onPress={() => setRejectFor(null)} />
                  </View>
                </View>
              ) : null}
            </View>
          );
        })
      )}

      <ConfirmDialog
        visible={deleteFor !== null}
        title="Delete post?"
        message="The post is removed for good and the deletion is audited."
        confirmLabel="Delete"
        danger
        loading={busyKey === deleteFor}
        onConfirm={() => {
          const key = deleteFor;
          setDeleteFor(null);
          if (key) act(key, () => deleteNews(key));
        }}
        onCancel={() => setDeleteFor(null)}
      />
      <BusyOverlay visible={refreshing || busyKey !== null} label="Working…" />
    </AdminPage>
  );
}

function Act({ label, onPress, tone = 'primary', disabled = false }: { label: string; onPress: () => void; tone?: 'primary' | 'warn' | 'danger' | 'ghost'; disabled?: boolean }) {
  const color = tone === 'danger' ? COLORS.danger : tone === 'warn' ? COLORS.warning : CC_ACCENT.light;
  const bg = tone === 'ghost' ? 'transparent' : tone === 'warn' ? 'rgba(252,211,77,.10)' : tone === 'danger' ? 'rgba(248,113,113,.10)' : CC_ACCENT.soft;
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={({ pressed }) => [styles.act, { backgroundColor: bg, borderColor: tone === 'ghost' ? COLORS.border : CC_ACCENT.border }, pressed && { opacity: 0.7 }, disabled && { opacity: 0.5 }]}
      accessibilityRole="button"
    >
      <Text style={[styles.actText, { color: tone === 'ghost' ? COLORS.textSecondary : color }]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  muted: { color: COLORS.textMuted, fontSize: 11 },
  newBtn: { backgroundColor: CC_ACCENT.soft, borderColor: CC_ACCENT.border, borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 12, paddingVertical: 6 },
  newBtnText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 12 },
  card: { backgroundColor: COLORS.bgRaised, borderColor: COLORS.border, borderWidth: 1, borderRadius: RADIUS.md, padding: 12, marginBottom: 10 },
  cardHead: { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  title: { color: COLORS.textSecondary, fontWeight: '800', fontSize: 14 },
  meta: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  pill: { borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 10, paddingVertical: 4 },
  pillText: { fontSize: 10, fontWeight: '800', textTransform: 'uppercase' },
  excerpt: { color: COLORS.textSecondary, fontSize: 13, lineHeight: 19, marginTop: 8 },
  reason: { color: COLORS.danger, fontSize: 12, marginTop: 6 },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10 },
  act: { borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  actText: { fontWeight: '800', fontSize: 12 },
  reasonWrap: { backgroundColor: COLORS.bgRaised, borderRadius: RADIUS.md, borderWidth: 1, borderColor: 'rgba(248,113,113,.35)', padding: 10, marginBottom: 10 },
});
