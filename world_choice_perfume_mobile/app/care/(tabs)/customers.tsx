import { Ionicons } from '@expo/vector-icons';
import { router, useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel, SearchInput, useAsyncData } from '../../../components/adminkit';
import { careMenu } from '../../../components/caresidebar';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { errorMessage } from '../../../lib/api';
import { fetchCustomers, fetchLoyalCustomers, removeLoyalCustomer, type CcLoyalRow } from '../../../lib/careApi';
import { formatDateTime } from '../../../lib/format';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * Clients — the website's customer-care.customers index with its second tab,
 * Loyal Customers: search across name/phone/whatsapp, one chip per branch
 * (plus "All") with the client counters the website derives from the sales
 * history, and each row's last visit. Tapping a row opens the record with
 * their transactions.
 *
 * The Loyal segment shows every client customer care has flagged as loyal
 * (GET /care/loyal-customers) with a Remove action, and its header button
 * opens the picker that searches the available customers and adds one or
 * more as loyal — the website's Loyal tab, twin for twin.
 */
type Segment = 'clients' | 'loyal';

interface LoyalData {
  rows: CcLoyalRow[];
  total: number;
}

export default function CareCustomers() {
  const [view, setView] = useState<Segment>('clients');
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

  // The Loyal list (and the counter on its segment) reloads on every focus —
  // including the return from the picker after clients were added, which is
  // when the list has actually changed.
  const [loyal, setLoyal] = useState<LoyalData | null>(null);
  const [loyalError, setLoyalError] = useState('');
  const [loyalLoading, setLoyalLoading] = useState(false);

  const loadLoyal = useCallback(async () => {
    setLoyalLoading(true);
    try {
      const payload = await fetchLoyalCustomers();
      setLoyal({ rows: payload.loyal ?? [], total: payload.total ?? 0 });
      setLoyalError('');
    } catch (e) {
      setLoyalError(errorMessage(e));
    } finally {
      setLoyalLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      loadLoyal();
    }, [loadLoyal]),
  );

  const [refreshing, setRefreshing] = useState(false);

  const confirmRemove = (row: CcLoyalRow) => {
    const name = row.customer?.name?.trim() || 'this client';
    Alert.alert('Remove loyal customer', `Take ${name} off the loyal list?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Remove',
        style: 'destructive',
        onPress: async () => {
          try {
            await removeLoyalCustomer(row.customer_id);
            setLoyal((prev) =>
              prev
                ? {
                    rows: prev.rows.filter((r) => String(r.customer_id) !== String(row.customer_id)),
                    total: Math.max(0, prev.total - 1),
                  }
                : prev,
            );
          } catch (e) {
            setLoyalError(errorMessage(e));
          }
        },
      },
    ]);
  };

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  // The Loyal list answers its own search client-side: the rows are already
  // on screen, so a keystroke filters them without a round trip.
  const loyalQuery = q.trim().toLowerCase();
  const loyalRows = (loyal?.rows ?? []).filter((row) => {
    if (!loyalQuery) return true;
    const customer = row.customer;
    return [customer?.name, customer?.phone, customer?.whatsapp]
      .filter(Boolean)
      .some((field) => String(field).toLowerCase().includes(loyalQuery));
  });

  return (
    <AdminPage
      title={view === 'loyal' ? 'Loyal Clients' : 'Clients'}
      onMenu={careMenu.open}
      eyebrow="Customer Care"
      accent={CC_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await Promise.all([reload(), loadLoyal()]);
        setRefreshing(false);
      }}
      action={
        view === 'loyal' ? (
          <Pressable
            onPress={() => router.push('/care/loyal-new')}
            style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
            accessibilityRole="button"
            accessibilityLabel="Create loyal customer"
          >
            <Text style={styles.newBtnText}>+ Loyal</Text>
          </Pressable>
        ) : (
          <Pressable
            onPress={() => router.push('/care/customer-new')}
            style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
            accessibilityRole="button"
            accessibilityLabel="New client"
          >
            <Text style={styles.newBtnText}>+ New</Text>
          </Pressable>
        )
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}
      {view === 'loyal' && loyalError ? <Banner kind="error" message={loyalError} actionLabel="Retry" onAction={loadLoyal} /> : null}

      {/* The two tabs of the screen — the website's branch strip and Loyal
          Customers tab, side by side in one segmented control. */}
      <View style={styles.segment}>
        <Pressable
          onPress={() => setView('clients')}
          style={({ pressed }) => [styles.segmentBtn, view === 'clients' && styles.segmentBtnActive, pressed && { opacity: 0.8 }]}
          accessibilityRole="tab"
          accessibilityState={{ selected: view === 'clients' }}
        >
          <Text style={[styles.segmentText, view === 'clients' && styles.segmentTextActive]}>
            Clients ({data?.totalCustomers ?? 0})
          </Text>
        </Pressable>
        <Pressable
          onPress={() => setView('loyal')}
          style={({ pressed }) => [styles.segmentBtn, view === 'loyal' && styles.segmentBtnActive, pressed && { opacity: 0.8 }]}
          accessibilityRole="tab"
          accessibilityState={{ selected: view === 'loyal' }}
        >
          <Text style={[styles.segmentText, view === 'loyal' && styles.segmentTextActive]}>
            Loyal ({loyal?.total ?? 0})
          </Text>
        </Pressable>
      </View>

      <SearchInput
        value={q}
        onChangeText={setQ}
        placeholder={view === 'loyal' ? 'Search loyal clients by name or phone…' : 'Search name, phone or whatsapp…'}
      />

      {view === 'loyal' ? (
        <>
          {loyalLoading && !loyal ? (
            <LoadingView label="Loading loyal clients…" />
          ) : !loyal ? (
            <ErrorView message={loyalError || 'Could not load loyal clients.'} onRetry={loadLoyal} />
          ) : loyalRows.length === 0 ? (
            <EmptyView
              icon="star-outline"
              title={loyalQuery ? 'No matching loyal clients' : 'No loyal customers yet'}
              hint={
                loyalQuery
                  ? 'Try a shorter search.'
                  : 'Press + Loyal at the top right to search your clients and flag your regulars.'
              }
            />
          ) : (
            <>
              <GroupLabel right={loyalQuery ? <Text style={styles.muted}>{loyalRows.length} of {loyal.total}</Text> : undefined}>
                {loyalRows.length} loyal {loyalRows.length === 1 ? 'client' : 'clients'}
              </GroupLabel>
              {loyalRows.map((row) => {
                const customer = row.customer;
                return (
                  <View key={String(row.id ?? row.customer_id)} style={styles.rowWrap}>
                    <Pressable
                      onPress={() =>
                        router.push({ pathname: '/care/customer-detail', params: { id: String(customer.id) } })
                      }
                      style={({ pressed }) => [styles.row, pressed && { opacity: 0.75 }]}
                      accessibilityRole="button"
                      accessibilityLabel={`Open ${customer?.name ?? 'client'} record`}
                    >
                      <View style={[styles.avatar, styles.loyalAvatar]}>
                        <Ionicons name="star" size={17} color={CC_ACCENT.light} />
                      </View>
                      <View style={styles.rowBody}>
                        <Text style={styles.rowName} numberOfLines={1}>
                          {customer?.name || 'Unnamed client'}
                        </Text>
                        <Text style={styles.rowMeta} numberOfLines={1}>
                          {[customer?.phone, customer?.whatsapp].filter(Boolean).join(' · ') || 'No phone'}
                        </Text>
                        <Text style={styles.rowBranches} numberOfLines={1}>
                          Loyal since {formatDateTime(row.assigned_at ?? null)}
                          {row.assigned_by_name ? `  ·  by ${row.assigned_by_name}` : ''}
                        </Text>
                      </View>
                      <Pressable
                        onPress={() => confirmRemove(row)}
                        style={({ pressed }) => [styles.removeBtn, pressed && { opacity: 0.6 }]}
                        hitSlop={8}
                        accessibilityRole="button"
                        accessibilityLabel={`Remove ${customer?.name ?? 'client'} from loyal customers`}
                      >
                        <Ionicons name="close-circle-outline" size={14} color={COLORS.textMuted} />
                        <Text style={styles.removeText}>Remove</Text>
                      </Pressable>
                    </Pressable>
                  </View>
                );
              })}
            </>
          )}
        </>
      ) : (
        <>
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
              {data.truncated ? <Banner kind="connection" message="Older history is outside this scan — counts may exclude very old sales." /> : null}          {data.customers.map((c) => {
                const visit = data.visits[String(c.id)];
                const lastVisit = visit?.last_visit ?? null;
                const visited = visit
                  ? Array.isArray(visit.branches)
                    ? visit.branches
                    : Object.keys(visit.branches ?? {}).map(Number)
                  : [];
                const visitedNames = visited.map((bid) => data.branchNames[String(bid)] ?? `Branch #${bid}`);
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
                          {[c.phone, c.whatsapp].filter(Boolean).join(' · ') || 'No phone'}
                          {lastVisit ? `  ·  last visit ${formatDateTime(lastVisit)}` : ''}
                        </Text>
                        <Text style={styles.rowBranches} numberOfLines={1}>
                          {visitedNames.length > 0 ? `Visited: ${visitedNames.join(', ')}` : 'No visits yet'}
                        </Text>
                      </View>
                    </Pressable>
                  </View>
                );
              })}
            </>
          )}
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
  segment: {
    flexDirection: 'row',
    gap: 4,
    backgroundColor: COLORS.bgRaised,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.pill,
    padding: 4,
    marginBottom: 4,
  },
  segmentBtn: { flex: 1, paddingVertical: 8, borderRadius: RADIUS.pill, alignItems: 'center', justifyContent: 'center' },
  segmentBtnActive: { backgroundColor: CC_ACCENT.soft },
  segmentText: { color: COLORS.textMuted, fontWeight: '700', fontSize: 12 },
  segmentTextActive: { color: CC_ACCENT.light },
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
  loyalAvatar: { borderWidth: 1, borderColor: CC_ACCENT.border },
  avatarText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 16 },
  rowBody: { flex: 1 },
  rowName: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 14 },
  rowMeta: { color: COLORS.textMuted, fontSize: 12, marginTop: 2 },
  rowBranches: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  removeBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 9,
    paddingVertical: 5,
  },
  removeText: { color: COLORS.textMuted, fontWeight: '700', fontSize: 10 },
});
