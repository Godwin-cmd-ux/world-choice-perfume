import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, SearchInput, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchMails } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

const BOXES = [
  { key: 'all', label: 'All' },
  { key: 'unread', label: 'Unread' },
  { key: 'starred', label: 'Starred' },
  { key: 'replied', label: 'Replied' },
  { key: 'closed', label: 'Closed' },
];

/**
 * Emails — the mobile twin of super-admin/emails/index.blade.php: the same
 * info@ mailbox (shared InfoMailService), the same boxes, search and 30-per
 * page paging, unread badge, tap-through to the thread with reply / read /
 * star / status / delete operations.
 */
export default function Emails() {
  const [box, setBox] = useState('all');
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(
    () => fetchMails({ box, search: query || undefined, page }),
    [box, query, page],
  );

  const mails = data?.mails ?? [];
  // A full page (30) means there may be older mail underneath.
  const hasMore = mails.length >= 30;

  return (
    <AdminPage title="Emails" eyebrow="info@ mailbox" onBack={() => router.back()} onMenu={adminMenu.open} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Loading mailbox…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}

          <ChipRow>
            {BOXES.map((b) => (
              <Chip
                key={b.key}
                label={b.key === 'unread' && data.unread_count > 0 ? `${b.label} (${data.unread_count})` : b.label}
                active={box === b.key}
                onPress={() => {
                  setBox(b.key);
                  setPage(1);
                }}
              />
            ))}
          </ChipRow>

          <SearchInput value={search} onChangeText={setSearch} placeholder="Sender, subject or content…" />
          {search !== query ? (
            <Text style={styles.applyHint} onPress={() => { setQuery(search); setPage(1); }}>
              Tap to apply “{search}”
            </Text>
          ) : null}

          <GroupLabel right={<Text style={styles.count}>Page {data.page}</Text>}>Inbox</GroupLabel>

          {mails.length === 0 ? (
            <EmptyView icon="mail-outline" title="No mail here" hint="This box is empty." />
          ) : (
            mails.map((m) => {
              const names = typeof m.attachment_names === 'string'
                ? m.attachment_names.split(',').filter(Boolean)
                : Array.isArray(m.attachment_names)
                  ? m.attachment_names
                  : [];
              return (
                <DataCard
                  key={String(m.id)}
                  title={m.subject || '(no subject)'}
                  subtitle={m.from_name ? `${m.from_name} <${m.from_email ?? ''}>` : m.from_email}
                  badge={m.is_read ? undefined : 'Unread'}
                  badgeTone="gold"
                  lines={[
                    m.body_text ? String(m.body_text).slice(0, 110) : null,
                    m.received_at ? new Date(m.received_at).toLocaleString() : null,
                    names.length > 0 ? `📎 ${names.join(', ')}` : null,
                  ]}
                  right={
                    m.is_starred ? <Text style={styles.star}>★</Text> : undefined
                  }
                  onPress={() => router.push({ pathname: '/admin/email-detail', params: { id: String(m.id) } })}
                />
              );
            })
          )}

          <View style={styles.pager}>
            <Pressable style={[styles.pageBtn, page <= 1 && styles.pageBtnOff]} disabled={page <= 1} onPress={() => setPage((p) => Math.max(1, p - 1))}>
              <Text style={styles.pageBtnText}>Newer</Text>
            </Pressable>
            <Pressable style={[styles.pageBtn, !hasMore && styles.pageBtnOff]} disabled={!hasMore} onPress={() => setPage((p) => p + 1)}>
              <Text style={styles.pageBtnText}>Older</Text>
            </Pressable>
          </View>
        </>
      ) : null}
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  applyHint: { color: COLORS.gold, fontSize: 13, fontWeight: '600' },
  count: { color: COLORS.textMuted, fontSize: 12 },
  star: { color: COLORS.goldBright, fontSize: 18 },
  pager: { flexDirection: 'row', justifyContent: 'center', gap: 12, marginTop: 6 },
  pageBtn: {
    paddingHorizontal: 18,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
  },
  pageBtnOff: { opacity: 0.4 },
  pageBtnText: { color: COLORS.goldLight, fontWeight: '700', fontSize: 13.5 },
});
