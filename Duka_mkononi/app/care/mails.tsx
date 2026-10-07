import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, SearchInput, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchMails } from '../../lib/careApi';
import { formatDateTime } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../lib/theme';

const BOXES = ['all', 'unread', 'starred', 'replied', 'closed'] as const;

/**
 * info@ mailbox — HQ only, exactly the website's box tabs (all / unread /
 * starred / replied / closed) with search and paging over the shared
 * InfoMailService. Tap opens the thread; attachments are listed there by
 * name (opening/saving files stays a website action).
 */
export default function CareMails() {
  const [box, setBox] = useState<(typeof BOXES)[number]>('all');
  const [search, setSearch] = useState('');
  const [debounced, setDebounced] = useState('');
  const [page, setPage] = useState(1);

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchMails({ box, search: debounced || undefined, page }),
    [box, debounced, page],
  );

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const blocked = !!error && /Head Quarters/i.test(error);

  return (
    <AdminPage title="info@ Mails" eyebrow="Head Quarters" accent={CC_ACCENT.main} onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => { setRefreshing(true); await reload(); setRefreshing(false); }}
    >
      {blocked ? <Banner kind="error" message={error} /> : error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

      {!blocked ? (
        <>
          <SearchInput
            value={search}
            onChangeText={(t) => { setSearch(t); setDebounced(t); setPage(1); }}
            placeholder="Search sender or subject…"
          />
          <ChipRow>
            {BOXES.map((b) => (
              <Chip
                key={b}
                label={b === 'all' && data ? `All (${data.unread_count} unread)` : b}
                active={box === b}
                onPress={() => { setBox(b); setPage(1); }}
              />
            ))}
          </ChipRow>
        </>
      ) : null}

      {loading && !data ? (
        <LoadingView label="Loading mailbox…" />
      ) : blocked ? (
        <EmptyView icon="lock-closed-outline" title="Head Quarters only" hint="info@ is company-wide — the HQ desk answers it." />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.mails.length === 0 ? (
        <EmptyView icon="mail-outline" title="Nothing here" hint="Mail sent to info@worldchoiceperfume.com lands in this box." />
      ) : (
        <>
          <GroupLabel right={<Text style={styles.muted}>page {data.page}</Text>}>{data.shown} shown</GroupLabel>
          {data.mails.map((mail) => (
            <Pressable
              key={String(mail.id)}
              onPress={() => router.push({ pathname: '/care/mail-detail', params: { id: String(mail.id) } })}
              style={({ pressed }) => [styles.row, !mail.is_read && styles.rowUnread, pressed && { opacity: 0.75 }]}
              accessibilityRole="button"
            >
              <View style={[styles.dot, !mail.is_read && styles.dotUnread]} />
              <View style={{ flex: 1 }}>
                <Text style={[styles.subject, !mail.is_read && styles.subjectUnread]} numberOfLines={1}>
                  {mail.subject || '(no subject)'}
                </Text>
                <Text style={styles.meta} numberOfLines={1}>
                  {[mail.from_name ?? mail.from_email, formatDateTime(mail.received_at ?? mail.created_at)].filter(Boolean).join(' · ')}
                </Text>
              </View>
              {mail.is_starred ? <Text style={styles.star}>★</Text> : null}
            </Pressable>
          ))}
          {data.page > 1 || data.mails.length >= data.shown ? (
            <View style={styles.pager}>
              {data.page > 1 ? (
                <Pressable onPress={() => setPage((p) => Math.max(1, p - 1))} style={styles.pageBtn} accessibilityRole="button">
                  <Text style={styles.pageBtnText}>← Newer</Text>
                </Pressable>
              ) : null}
              {data.mails.length >= data.shown ? (
                <Pressable onPress={() => setPage((p) => p + 1)} style={styles.pageBtn} accessibilityRole="button">
                  <Text style={styles.pageBtnText}>Older →</Text>
                </Pressable>
              ) : null}
            </View>
          ) : null}
        </>
      )}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  muted: { color: COLORS.textMuted, fontSize: 11 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.bgRaised,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
    marginBottom: 8,
  },
  rowUnread: { borderColor: CC_ACCENT.border },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: COLORS.border },
  dotUnread: { backgroundColor: CC_ACCENT.main },
  subject: { color: COLORS.textMuted, fontWeight: '700', fontSize: 14 },
  subjectUnread: { color: COLORS.textSecondary },
  meta: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  star: { color: COLORS.goldBright, fontSize: 16 },
  pager: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 6 },
  pageBtn: { borderWidth: 1, borderColor: CC_ACCENT.border, backgroundColor: CC_ACCENT.soft, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  pageBtnText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 12 },
});
