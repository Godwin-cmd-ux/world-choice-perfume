import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { deleteInquiry, fetchInquiries, markInquiryFeatured, markInquiryRead, replyToInquiry } from '../../lib/careApi';
import { formatDateTime } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../lib/theme';

type Filter = 'all' | 'unread';

/**
 * Inquiries — HQ only (the website's `customer-care.hq` gate). The list
 * shows the branch's customer messages; opening one marks it read, a reply
 * sets status=replied, "Feature" pins it as a homepage remark and delete
 * removes it — every write the website's InquiryController makes.
 */
export default function CareInquiries() {
  const [filter, setFilter] = useState<Filter>('all');
  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchInquiries(filter === 'unread' ? { status: 'unread' } : {}),
    [filter],
  );

  const [openId, setOpenId] = useState<string | null>(null);
  const [reply, setReply] = useState('');
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<string | null>(null);
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

  return (
    <AdminPage title="Inquiries" eyebrow="Head Quarters" accent={CC_ACCENT.main} onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => { setRefreshing(true); await reload(); setRefreshing(false); }}
    >
      {blocked ? (
        <Banner kind="error" message={error} />
      ) : error ? (
        <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} />
      ) : null}
      {actionError ? <Banner kind="error" message={actionError} /> : null}

      {!blocked ? (
        <ChipRow>
          <Chip label={`All (${data?.counts.all ?? 0})`} active={filter === 'all'} onPress={() => setFilter('all')} />
          <Chip label={`Unread (${data?.counts.unread ?? 0})`} active={filter === 'unread'} onPress={() => setFilter('unread')} />
        </ChipRow>
      ) : null}

      {loading && !data ? (
        <LoadingView label="Loading inquiries…" />
      ) : blocked ? (
        <EmptyView icon="lock-closed-outline" title="Head Quarters only" hint="Inquiries live on the HQ customer care desk." />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.inquiries.length === 0 ? (
        <EmptyView icon="chatbubble-ellipses-outline" title="No inquiries" hint="Customer messages land here." />
      ) : (
        data.inquiries.map((inq) => {
          const key = String(inq.id);
          const open = openId === key;
          return (
            <View key={key} style={styles.card}>
              <Pressable onPress={() => { setOpenId(open ? null : key); setReply(inq.reply_message ?? ''); }} style={styles.cardHead} accessibilityRole="button">
                <View style={{ flex: 1 }}>
                  <Text style={styles.title} numberOfLines={1}>
                    {inq.subject || inq.message?.slice(0, 60) || 'Inquiry'}
                  </Text>
                  <Text style={styles.meta}>
                    {[inq.user?.name ?? 'Customer', formatDateTime(inq.created_at)].filter(Boolean).join(' · ')}
                  </Text>
                </View>
                <View style={[styles.pill, inq.is_read ? styles.pillRead : styles.pillUnread]}>
                  <Text style={[styles.pillText, { color: inq.is_read ? COLORS.textMuted : COLORS.danger }]}>
                    {inq.is_read ? (inq.status ?? 'read') : 'unread'}
                  </Text>
                </View>
              </Pressable>

              {open ? (
                <View style={styles.body}>
                  {inq.message ? <Text style={styles.message}>{inq.message}</Text> : null}
                  {inq.reply_message ? (
                    <View style={styles.replyBox}>
                      <Text style={styles.replyLabel}>Your reply</Text>
                      <Text style={styles.replyText}>{inq.reply_message}</Text>
                    </View>
                  ) : null}

                  <AuthField label="Reply" value={reply} onChangeText={setReply} placeholder="Write a reply…" icon="arrow-undo-outline" autoCapitalize="sentences" />

                  <View style={styles.actions}>
                    <Act label={busyKey === key ? '…' : 'Send Reply'} disabled={!reply.trim() || busyKey === key} onPress={() => act(key, () => replyToInquiry(inq.id, reply.trim()))} />
                    {!inq.is_read ? (
                      <Act label="Mark Read" onPress={() => act(key, () => markInquiryRead(inq.id))} />
                    ) : null}
                    {!inq.is_featured ? (
                      <Act label="Feature" onPress={() => act(key, () => markInquiryFeatured(inq.id))} />
                    ) : null}
                    <Act label="Delete" tone="danger" onPress={() => setConfirmDelete(key)} />
                  </View>
                </View>
              ) : null}
            </View>
          );
        })
      )}

      <ConfirmDialog
        visible={confirmDelete !== null}
        title="Delete inquiry?"
        message="The message and any reply on it are removed for good."
        confirmLabel="Delete"
        danger
        loading={busyKey === confirmDelete}
        onConfirm={() => {
          const key = confirmDelete;
          setConfirmDelete(null);
          if (key) act(key, () => deleteInquiry(key));
        }}
        onCancel={() => setConfirmDelete(null)}
      />
      <BusyOverlay visible={refreshing || busyKey !== null} label="Working…" />
    </AdminPage>
  );
}

function Act({ label, onPress, tone = 'primary', disabled = false }: { label: string; onPress: () => void; tone?: 'primary' | 'danger'; disabled?: boolean }) {
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={({ pressed }) => [
        styles.act,
        tone === 'danger' && { borderColor: 'rgba(248, 113, 113, 0.4)' },
        pressed && { opacity: 0.7 },
        disabled && { opacity: 0.5 },
      ]}
      accessibilityRole="button"
    >
      <Text style={[styles.actText, tone === 'danger' && { color: COLORS.danger }]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: { backgroundColor: COLORS.bgRaised, borderColor: COLORS.border, borderWidth: 1, borderRadius: RADIUS.md, padding: 12, marginBottom: 10 },
  cardHead: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  title: { color: COLORS.textSecondary, fontWeight: '800', fontSize: 14 },
  meta: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  pill: { borderRadius: RADIUS.pill, paddingHorizontal: 10, paddingVertical: 4, borderWidth: 1 },
  pillUnread: { borderColor: 'rgba(248, 113, 113, 0.4)', backgroundColor: 'rgba(248, 113, 113, 0.08)' },
  pillRead: { borderColor: COLORS.border },
  pillText: { fontSize: 10, fontWeight: '800', textTransform: 'uppercase' },
  body: { marginTop: 10, borderTopWidth: 1, borderTopColor: COLORS.border, paddingTop: 10 },
  message: { color: COLORS.textSecondary, fontSize: 13, lineHeight: 19, marginBottom: 10 },
  replyBox: { backgroundColor: CC_ACCENT.soft, borderColor: CC_ACCENT.border, borderWidth: 1, borderRadius: RADIUS.md, padding: 10, marginBottom: 10 },
  replyLabel: { color: CC_ACCENT.light, fontSize: 10, fontWeight: '800', textTransform: 'uppercase', marginBottom: 4 },
  replyText: { color: COLORS.textSecondary, fontSize: 13 },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 4 },
  act: { borderWidth: 1, borderColor: CC_ACCENT.border, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8, backgroundColor: CC_ACCENT.soft },
  actText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 12 },
});
