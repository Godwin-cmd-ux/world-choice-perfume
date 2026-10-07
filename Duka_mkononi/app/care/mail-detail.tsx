import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, GroupLabel, useAsyncData } from '../../components/adminkit';
import { ErrorView, LoadingView } from '../../components/ui';
import {
  deleteMail,
  fetchMail,
  replyToMail,
  setMailStatus,
  toggleMailRead,
  toggleMailStar,
} from '../../lib/careApi';
import { formatDateTime } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../lib/theme';

/**
 * One info@ mail — HQ only. The thread and every earlier answer come from
 * the same InfoMailService the website reads; the reply box posts through
 * the same endpoint (text only — attaching files stays a website action,
 * since the phone would have to upload through the browser flow anyway).
 * Star, read toggle, status move and delete are the website's operations.
 */
export default function CareMailDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchMail(id!), [id]);

  const [body, setBody] = useState('');
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [confirmDelete, setConfirmDelete] = useState(false);
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

  const mail = data?.mail;
  const thread = data?.thread ?? [];
  const replies = (data?.replies ?? []) as { id?: number | string; body?: string; staff_name?: string; created_at?: string }[];
  const attachmentNames = (mail?.attachment_names as string | null) ?? null;

  return (
    <AdminPage title={mail?.subject ? String(mail.subject) : 'Mail'} eyebrow="info@ · Head Quarters" accent={CC_ACCENT.main} onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => { setRefreshing(true); await reload(); setRefreshing(false); }}
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {actionError ? <Banner kind="error" message={actionError} /> : null}

      {loading && !data ? (
        <LoadingView label="Loading mail…" />
      ) : error && !mail ? (
        <ErrorView message={error} onRetry={reload} />
      ) : mail ? (
        <>
          <View style={styles.head}>
            <View style={{ flex: 1 }}>
              <Text style={styles.from} numberOfLines={1}>
                {(mail.from_name as string | null) ?? (mail.from_email as string | null) ?? 'Sender'}
              </Text>
              <Text style={styles.meta} numberOfLines={1}>
                {[mail.from_email, formatDateTime(mail.received_at ?? mail.created_at)].filter(Boolean).join(' · ')}
              </Text>
            </View>
            <Pressable onPress={() => act('star', () => toggleMailStar(id!))} hitSlop={8} accessibilityLabel="Toggle star">
              <Text style={[styles.star, mail.is_starred && styles.starOn]}>{mail.is_starred ? '★' : '☆'}</Text>
            </Pressable>
          </View>

          <ChipRow>
            <Chip label={mail.is_read ? 'Read' : 'Unread'} active onPress={() => act('read', () => toggleMailRead(id!))} />
            {(['new', 'replied', 'closed'] as const).map((s) => (
              <Chip key={s} label={s} active={mail.status === s} onPress={() => act(`status-${s}`, () => setMailStatus(id!, s))} />
            ))}
          </ChipRow>

          <GroupLabel>Message</GroupLabel>
          <View style={styles.bodyCard}>
            <Text style={styles.bodyText}>{(mail.body_text as string | null) || '(empty body)'}</Text>
          </View>

          {thread.length > 1 ? (
            <>
              <GroupLabel>Thread</GroupLabel>
              {thread.map((t, i) => (
                <View key={String(t.id ?? i)} style={styles.threadRow}>
                  <Text style={styles.threadFrom} numberOfLines={1}>
                    {String(t.from_name ?? t.from_email ?? 'Sender')}
                  </Text>
                  <Text style={styles.threadSubject} numberOfLines={1}>
                    {String(t.subject ?? '')}
                  </Text>
                  <Text style={styles.threadDate}>{formatDateTime((t.received_at ?? t.created_at) as string | null)}</Text>
                </View>
              ))}
            </>
          ) : null}

          {attachmentNames ? (
            <>
              <GroupLabel>Attachments (open on the website)</GroupLabel>
              <View style={styles.bodyCard}>
                <Text style={styles.bodyText}>{attachmentNames}</Text>
              </View>
            </>
          ) : null}

          {replies.length > 0 ? (
            <>
              <GroupLabel>Answers sent</GroupLabel>
              {replies.map((r, i) => (
                <View key={String(r.id ?? i)} style={styles.replyRow}>
                  <Text style={styles.replyMeta}>
                    {[r.staff_name, formatDateTime(r.created_at)].filter(Boolean).join(' · ')}
                  </Text>
                  <Text style={styles.replyBody} numberOfLines={4}>{r.body}</Text>
                </View>
              ))}
            </>
          ) : null}

          <GroupLabel>Answer</GroupLabel>
          <TextInput
            value={body}
            onChangeText={setBody}
            placeholder={`Write your answer… (${data.reply_limits.total_mb} MB / ${data.reply_limits.files} files max on the website)`}
            placeholderTextColor={COLORS.textMuted}
            multiline
            style={styles.input}
            autoCapitalize="sentences"
          />
          <View style={styles.actions}>
            <Pressable
              onPress={() => act('reply', async () => {
                const text = body.trim();
                setBody('');
                return replyToMail(id!, text);
              })}
              disabled={!body.trim() || busyKey === 'reply'}
              style={({ pressed }) => [styles.act, (!body.trim() || busyKey === 'reply') && { opacity: 0.5 }, pressed && { opacity: 0.7 }]}
              accessibilityRole="button"
            >
              <Text style={styles.actText}>{busyKey === 'reply' ? 'Sending…' : 'Send Answer'}</Text>
            </Pressable>
            <Pressable onPress={() => setConfirmDelete(true)} style={({ pressed }) => [styles.act, styles.actDanger, pressed && { opacity: 0.7 }]} accessibilityRole="button">
              <Text style={[styles.actText, { color: COLORS.danger }]}>Delete</Text>
            </Pressable>
          </View>
        </>
      ) : null}

      <ConfirmDialog
        visible={confirmDelete}
        title="Delete mail?"
        message="The message and its metadata are removed from the mailbox."
        confirmLabel="Delete"
        danger
        loading={busyKey === 'delete'}
        onConfirm={() => {
          setConfirmDelete(false);
          act('delete', () => deleteMail(id!)).then(() => router.back());
        }}
        onCancel={() => setConfirmDelete(false)}
      />
      <BusyOverlay visible={refreshing || busyKey !== null} label="Working…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  head: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 10 },
  from: { color: COLORS.textSecondary, fontWeight: '800', fontSize: 15 },
  meta: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  star: { fontSize: 26, color: COLORS.textMuted },
  starOn: { color: COLORS.goldBright },
  bodyCard: { backgroundColor: COLORS.bgRaised, borderColor: COLORS.border, borderWidth: 1, borderRadius: RADIUS.md, padding: 12, marginBottom: 10 },
  bodyText: { color: COLORS.textSecondary, fontSize: 13, lineHeight: 20 },
  threadRow: { backgroundColor: COLORS.bgRaised, borderColor: COLORS.border, borderWidth: 1, borderRadius: RADIUS.md, padding: 10, marginBottom: 8 },
  threadFrom: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 12 },
  threadSubject: { color: COLORS.textSecondary, fontSize: 13, marginTop: 2 },
  threadDate: { color: COLORS.textMuted, fontSize: 10, marginTop: 2 },
  replyRow: { backgroundColor: CC_ACCENT.soft, borderColor: CC_ACCENT.border, borderWidth: 1, borderRadius: RADIUS.md, padding: 10, marginBottom: 8 },
  replyMeta: { color: CC_ACCENT.light, fontSize: 11, fontWeight: '700' },
  replyBody: { color: COLORS.textSecondary, fontSize: 13, marginTop: 4 },
  input: {
    minHeight: 120,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.bgRaised,
    borderRadius: RADIUS.md,
    padding: 12,
    color: COLORS.textSecondary,
    fontSize: 14,
    textAlignVertical: 'top',
  },
  actions: { flexDirection: 'row', gap: 10, marginTop: 12 },
  act: { flex: 1, alignItems: 'center', borderWidth: 1, borderColor: CC_ACCENT.border, backgroundColor: CC_ACCENT.soft, borderRadius: RADIUS.md, paddingVertical: 12 },
  actDanger: { borderColor: 'rgba(248,113,113,.4)', backgroundColor: 'rgba(248,113,113,.08)' },
  actText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 13 },
});
