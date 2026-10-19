import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, SearchInput, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { errorMessage } from '../../lib/api';
import { addLoyalCustomers, fetchCustomers, fetchLoyalCustomers } from '../../lib/careApi';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../lib/theme';

/**
 * Create Loyal — the picker behind the Loyal segment's + Loyal button (the
 * website's Loyal tab, ?create=1 panel): search the available customers,
 * tick one or more, add them as loyal in a single action.
 *
 * Clients already on the loyal list are shown disabled rather than hidden,
 * so a regular who is missing from the tab is visibly missing and someone
 * who is already flagged can see why the row will not tick.
 */
export default function CareLoyalNew() {
  const [q, setQ] = useState('');
  const [debouncedQ, setDebouncedQ] = useState('');
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [already, setAlready] = useState<Set<string>>(new Set());
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebouncedQ(q.trim().length >= 2 || q.trim() === '' ? q.trim() : ''), 350);
    return () => clearTimeout(t);
  }, [q]);

  const { data, error: listError, loading, sessionExpired, reload } = useAsyncData(
    () => fetchCustomers({ q: debouncedQ || undefined }),
    [debouncedQ],
  );

  // Who is already loyal, so the picker can disable those rows. If this
  // read fails the picker still works: the server skips anyone already
  // flagged and the response says how many were skipped.
  useEffect(() => {
    let alive = true;
    fetchLoyalCustomers()
      .then((payload) => {
        if (alive) {
          setAlready(new Set((payload.loyal ?? []).map((row) => String(row.customer_id))));
        }
      })
      .catch(() => {});
    return () => {
      alive = false;
    };
  }, []);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const visible = data?.customers ?? [];

  const toggle = (id: string) => {
    if (already.has(id)) return;
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const selectAllShown = () => {
    setSelected((prev) => {
      const next = new Set(prev);
      for (const customer of visible) {
        const id = String(customer.id);
        if (!already.has(id)) next.add(id);
      }
      return next;
    });
  };

  const submit = async () => {
    if (selected.size === 0 || busy) return;
    setBusy(true);
    setError(null);
    try {
      const res = await addLoyalCustomers([...selected]);
      Alert.alert('Loyal customers', res.message);
      setSelected(new Set());
      router.back();
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  const tickable = visible.filter((customer) => !already.has(String(customer.id)));

  return (
    <AdminPage
      title="Create Loyal"
      eyebrow="Customer Care"
      accent={CC_ACCENT.main}
      onBack={() => router.back()}
      action={
        <Pressable
          onPress={submit}
          disabled={selected.size === 0}
          style={({ pressed }) => [
            styles.addBtn,
            selected.size === 0 && styles.addBtnDisabled,
            pressed && { opacity: 0.7 },
          ]}
          accessibilityRole="button"
          accessibilityLabel={`Add ${selected.size} selected as loyal`}
        >
          <Text style={styles.addBtnText}>Add ({selected.size})</Text>
        </Pressable>
      }
    >
      {error ? <Banner kind="error" message={error} /> : null}
      {listError ? <Banner kind="error" message={listError} actionLabel="Retry" onAction={reload} /> : null}

      <SearchInput value={q} onChangeText={setQ} placeholder="Search customers by name, phone or whatsapp…" />

      {loading && !data ? (
        <LoadingView label="Loading customers…" />
      ) : listError && !data ? (
        <ErrorView message={listError} onRetry={reload} />
      ) : !data || visible.length === 0 ? (
        <EmptyView
          icon="search-outline"
          title={debouncedQ ? 'No matching customers' : 'No customers yet'}
          hint={debouncedQ ? 'Try a shorter search.' : 'Clients appear here as they are created on the Clients tab.'}
        />
      ) : (
        <>
          <GroupLabel
            right={
              tickable.length > 1 ? (
                <Pressable onPress={selectAllShown} hitSlop={8}>
                  <Text style={styles.selectAll}>Select all shown</Text>
                </Pressable>
              ) : undefined
            }
          >
            {visible.length} {visible.length === 1 ? 'customer' : 'customers'}
          </GroupLabel>

          {visible.map((customer) => {
            const id = String(customer.id);
            const isLoyal = already.has(id);
            const isSelected = selected.has(id);
            return (
              <Pressable
                key={id}
                onPress={() => toggle(id)}
                disabled={isLoyal}
                style={({ pressed }) => [
                  styles.row,
                  isLoyal && styles.rowMuted,
                  pressed && !isLoyal && { opacity: 0.75 },
                ]}
                accessibilityRole="checkbox"
                accessibilityState={{ checked: isSelected, disabled: isLoyal }}
              >
                <View style={styles.avatar}>
                  <Text style={styles.avatarText}>{(customer.name ?? '?').charAt(0).toUpperCase()}</Text>
                </View>
                <View style={styles.rowBody}>
                  <Text style={styles.rowName} numberOfLines={1}>
                    {customer.name || 'Unnamed client'}
                  </Text>
                  <Text style={styles.rowMeta} numberOfLines={1}>
                    {[customer.phone, customer.whatsapp].filter(Boolean).join(' · ') || 'No phone'}
                  </Text>
                </View>
                {isLoyal ? (
                  <View style={styles.loyalPill}>
                    <Ionicons name="star" size={9} color={CC_ACCENT.light} />
                    <Text style={styles.loyalPillText}>Already loyal</Text>
                  </View>
                ) : (
                  <Ionicons
                    name={isSelected ? 'checkmark-circle' : 'ellipse-outline'}
                    size={24}
                    color={isSelected ? CC_ACCENT.main : COLORS.border}
                  />
                )}
              </Pressable>
            );
          })}

          {selected.size > 0 ? (
            <View style={styles.summary}>
              <Text style={styles.summaryText}>
                {selected.size} selected
              </Text>
              <Pressable
                onPress={submit}
                style={({ pressed }) => [styles.addBtn, pressed && { opacity: 0.7 }]}
                accessibilityRole="button"
              >
                <Ionicons name="star" size={12} color="#1a1200" />
                <Text style={styles.addBtnText}>Add as Loyal</Text>
              </Pressable>
            </View>
          ) : null}
        </>
      )}

      <BusyOverlay visible={busy} label="Adding as loyal…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  addBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: CC_ACCENT.soft,
    borderColor: CC_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  addBtnDisabled: { opacity: 0.45 },
  addBtnText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 12 },
  selectAll: { color: CC_ACCENT.main, fontWeight: '700', fontSize: 11 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: COLORS.bgRaised,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
    marginBottom: 8,
  },
  rowMuted: { opacity: 0.65 },
  avatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: CC_ACCENT.soft, alignItems: 'center', justifyContent: 'center' },
  avatarText: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 16 },
  rowBody: { flex: 1 },
  rowName: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 14 },
  rowMeta: { color: COLORS.textMuted, fontSize: 12, marginTop: 2 },
  loyalPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: CC_ACCENT.soft,
    borderColor: CC_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
  loyalPillText: { color: CC_ACCENT.light, fontWeight: '700', fontSize: 9 },
  summary: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: COLORS.bgRaised,
    borderColor: CC_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginTop: 4,
  },
  summaryText: { color: COLORS.textSecondary, fontWeight: '700', fontSize: 13 },
});
