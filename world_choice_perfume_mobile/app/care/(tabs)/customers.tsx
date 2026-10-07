import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, SearchInput, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchCustomers } from '../../../lib/careApi';
import { formatDateTime } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * Clients — the website's customer-care.customers index: search across
 * name/phone/whatsapp, one chip per branch (plus "All") with the client
 * counters the website derives from the sales history, and each row's last
 * visit. Tapping a row opens the record with their transactions.
 */
export default function CareCustomers() {
  const [q, setQ] = useState('');
  const [debouncedQ, setDebouncedQ] = useState('');
  const [branch, setBranch] = useState<number | undefined>(undefined);

  useEffect(() => {
    const t = setTimeout(() => setDebouncedQ(q.trim().length >= 2 || q.trim() === '' ? q.trim() : ''), 350);
    return () => clearTimeout(t);
  }, [q]);

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchCustomers({ q: debouncedQ || undefined, branch }),
    [debouncedQ, branch],
  );

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  return (
    <AdminPage
      title="Clients"
      eyebrow="Customer Care"
      accent={CC_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        <Pressable
          onPress={() => router.push('/care/customer-new')}
          style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
          accessibilityRole="button"
          accessibilityLabel="New client"
        >
          <Text style={styles.newBtnText}>+ New</Text>
        </Pressable>
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

      <SearchInput value={q} onChangeText={setQ} placeholder="Search name, phone or whatsapp…" />

      <ChipRow>
        <Chip label={`All (${data?.totalCustomers ?? 0})`} active={!branch} onPress={() => setBranch(undefined)} />
        {(data?.tabs ?? []).map((tab) => (
          <Chip key={tab.id} label={`${tab.name} (${tab.count})`} active={branch === tab.id} onPress={() => setBranch(tab.id)} />
        ))}
      </ChipRow>

      {loading && !data ? (
        <LoadingView label="Loading clients…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.customers.length === 0 ? (
        <EmptyView
          icon="people-outline"
          title={debouncedQ ? 'No matching clients' : 'No clients yet'}
          hint={debouncedQ ? 'Try a shorter search, or another branch tab.' : 'New clients appear here as they are created.'}
        />
      ) : (
        <>
          <GroupLabel right={<Text style={styles.muted}>{data.tabName}</Text>}>
            {data.customers.length} shown
          </GroupLabel>
          {data.truncated ? <Banner kind="connection" message="Older history is outside this scan — counts may exclude very old sales." /> : null}
          {data.customers.map((c) => {
            const visit = data.visits[String(c.id)];
            const lastVisit = visit?.last_visit ?? null;
            return (
              <View key={String(c.id)} style={styles.rowWrap}>
                <Pressable
                  onPress={() => router.push({ pathname: '/care/customer-detail', params: { id: String(c.id) } })}
                  style={({ pressed }) => [styles.row, pressed && { opacity: 0.75 }]}
                  accessibilityRole="button"
                >
                  <View style={styles.avatar}>
                    <Text style={styles.avatarText}>{(c.name ?? '?').charAt(0).toUpperCase()}</Text>
                  </View>
                  <View style={styles.rowBody}>
                    <Text style={styles.rowName} numberOfLines={1}>
                      {c.name || 'Unnamed client'}
                    </Text>
                    <Text style={styles.rowMeta} numberOfLines={1}>
                      {c.phone || 'No phone'}
                      {lastVisit ? `  ·  last visit ${formatDateTime(lastVisit)}` : ''}
                    </Text>
                  </View>
                </Pressable>
              </View>
            );
          })}
        </>
      )}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  muted: { color: COLORS.textMuted, fontSize: 11 },
  newBtn: { backgroundColor: CC_ACCENT.soft, borderColor: CC_ACCENT.border, borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 12, paddingVertical: 6 },
  newBtnText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 12 },
  rowWrap: { marginBottom: 8 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: COLORS.bgRaised,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
  },
  avatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: CC_ACCENT.soft, alignItems: 'center', justifyContent: 'center' },
  avatarText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 16 },
  rowBody: { flex: 1 },
  rowName: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 14 },
  rowMeta: { color: COLORS.textMuted, fontSize: 12, marginTop: 2 },
});
