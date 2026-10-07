import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, StyleSheet, Text, TextInput, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, ConfirmDialog, GroupLabel, KV, useAsyncData } from '../../components/adminkit';
import { Card, ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { errorMessage } from '../../lib/api';
import {
  deleteMail,
  fetchMail,
  replyToMail,
  setMailStatus,
  toggleMailRead,
  toggleMailStar,
} from '../../lib/adminApi';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * Email detail — the mobile twin of super-admin/emails/show: the message +
 * its thread + prior replies, the reply composer (same server limits; file
 * attachments are not offered on the phone — see the parity report), and the
 * same operations: read/unread toggle, star, move to new/replied/closed, and
 * delete behind a confirmation.
 */
export default function EmailDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const mailId = String(id ?? '');

  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchMail(mailId), [mailId]);

  const [body, setBody] = useState('');
  const [busy, setBusy] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [confirmDelete, setConfirmDelete] = useState(false);

  const act = async (kind: string, fn: () => Promise<{ message: string }>, after?: () => void) => {
    setBusy(kind);
    setActionError(null);
    setNotice(null);
    try {
      const res = await fn();
      setNotice(res.message);
      if (after) after();
      await reload();
    } catch (e) {
      setActionError(errorMessage(e));
    } finally {
      setBusy(null);
    }
  };

  const send = async () => {
    if (!body.trim() || busy) return;
    await act('reply', () => replyToMail(mailId, body.trim()), () => setBody(''));
  };

  const mail = data?.mail;

  return (
    <AdminPage title="Message" eyebrow="info@ mailbox" onBack={() => router.back()}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={90}>
        {loading ? <LoadingView label="Loading message…" /> : null}
        {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
        {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

        {data && mail ? (
          <>
            {notice ? <Banner kind="success" message={notice} /> : null}
            {actionError ? <Banner kind="error" message={actionError} /> : null}

            <Card style={{ gap: 6 }}>
              <Text style={styles.subject}>{mail.subject || '(no subject)'}</Text>
              <View style={{ marginTop: 8, gap: 6 }}>
                <KV label="From:" value={mail.from_name ? `${mail.from_name} <${mail.from_email}>` : mail.from_email} />
                <KV label="Received:" value={mail.received_at ? new Date(mail.received_at).toLocaleString() : null} />
                <KV label="Status:" value={mail.status} />
                <KV label="Attachments:" value={(data.attachments as { file_name?: string }[]).map((a) => a.file_name).filter(Boolean).join(', ') || null} />
              </View>
              {mail.body_text ? <Text style={styles.body}>{String(mail.body_text)}</Text> : null}
            </Card>

            <GroupLabel>Actions</GroupLabel>
            <ChipRow>
              <Chip label={mail.is_read ? 'Mark unread' : 'Mark read'} onPress={() => act('read', () => toggleMailRead(mailId))} />
              <Chip label={mail.is_starred ? '★ Starred' : '☆ Star'} active={!!mail.is_starred} onPress={() => act('star', () => toggleMailStar(mailId))} />
              <Chip label="New" active={mail.status === 'new'} onPress={() => act('status', () => setMailStatus(mailId, 'new'))} />
              <Chip label="Replied" active={mail.status === 'replied'} onPress={() => act('status', () => setMailStatus(mailId, 'replied'))} />
              <Chip label="Closed" active={mail.status === 'closed'} onPress={() => act('status', () => setMailStatus(mailId, 'closed'))} />
            </ChipRow>
            <Text style={styles.deleteLink} onPress={() => setConfirmDelete(true)}>
              Delete this mail…
            </Text>

            {data.replies.length > 0 ? (
              <>
                <GroupLabel>Replies</GroupLabel>
                {data.replies.map((r, i) => {
                  const reply = r as { body?: string; body_text?: string; created_at?: string; staff_name?: string; sender_name?: string };
                  return (
                    <Card key={i} style={{ gap: 6 }}>
                      <Text style={styles.replyMeta}>
                        {reply.staff_name ?? reply.sender_name ?? data.signature ?? 'Staff'}
                        {reply.created_at ? ` · ${new Date(reply.created_at).toLocaleString()}` : ''}
                      </Text>
                      <Text style={styles.body}>{reply.body ?? reply.body_text ?? ''}</Text>
                    </Card>
                  );
                })}
              </>
            ) : null}

            <GroupLabel>Answer</GroupLabel>
            <TextInput
              value={body}
              onChangeText={setBody}
              placeholder={`Write your reply (max ${data.reply_limits.total_mb} MB of files from the website — text only here).`}
              placeholderTextColor={COLORS.textMuted}
              style={styles.composer}
              multiline
            />
            <GoldButton label="Send Reply" onPress={send} loading={busy === 'reply'} icon="send-outline" />
          </>
        ) : null}
      </KeyboardAvoidingView>

      <ConfirmDialog
        visible={confirmDelete}
        title="Delete this mail?"
        message="The message and its attachments are removed from the mailbox. This cannot be undone."
        confirmLabel="Delete"
        danger
        loading={busy === 'delete'}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={() => {
          setConfirmDelete(false);
          act('delete', () => deleteMail(mailId), () => setTimeout(() => router.back(), 700));
        }}
      />
      <BusyOverlay visible={busy !== null} label="Working…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  subject: { color: COLORS.text, fontSize: 18, fontWeight: '800' },
  body: { color: COLORS.textSecondary, fontSize: 14, lineHeight: 21 },
  replyMeta: { color: COLORS.gold, fontSize: 12.5, fontWeight: '700' },
  deleteLink: { color: COLORS.danger, fontSize: 13.5, fontWeight: '700', textAlign: 'center', paddingVertical: 6 },
  composer: {
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 13,
    minHeight: 110,
    color: COLORS.text,
    fontSize: 15,
    textAlignVertical: 'top',
  },
});
