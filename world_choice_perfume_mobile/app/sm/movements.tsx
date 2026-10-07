import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchProductMovements } from '../../lib/smApi';
import { COLORS, SM_ACCENT } from '../../lib/theme';

const TYPES = ['entry', 'sale', 'transfer_in', 'transfer_out', 'adjustment'] as const;

/**
 * Product stock movements — the mobile twin of product-stock-movements:
 * the last 50 rows with the same type filter and performed-by names
 * (resolved server-side, since PostgREST expansions return 0 rows).
 */
export default function SmMovements() {
  const [type, setType] = useState<string>('');
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(
    () => fetchProductMovements(type ? { type } : {}),
    [type],
  );

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const movements = data?.movements ?? [];

  return (
    <AdminPage
      title="Movements"
      eyebrow="Stock Manager"
      accent={SM_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={refresh}
    >
      <ChipRow>
        <Chip label="All" active={type === ''} onPress={() => setType('')} />
        {TYPES.map((t) => (
          <Chip key={t} label={t.replace('_', ' ')} active={type === t} onPress={() => setType(t)} />
        ))}
      </ChipRow>

      {error ? <Banner kind="error" message={error} /> : null}

      {loading ? (
        <LoadingView label="Loading movements…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : movements.length === 0 ? (
        <EmptyView icon="time-outline" title="No movements" hint="Stock entries, sales and transfers show up here." />
      ) : (
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ gap: 10, paddingBottom: 30 }}>
          {movements.map((m) => (
            <DataCard
              key={String(m.id)}
              title={`${m.product?.name ?? 'Product'} · ${(m.type ?? '').replace('_', ' ')}`}
              lines={[
                m.notes ?? m.reason ?? null,
                m.performedBy?.name ? `By ${m.performedBy.name}` : null,
                m.created_at ? formatDate(m.created_at) : null,
              ]}
              badge={m.quantity != null ? String(m.quantity) : undefined}
              badgeTone={(m.quantity ?? 0) >= 0 ? 'success' : 'danger'}
            />
          ))}
          <View style={styles.moreNote}>
            <Ionicons name="information-circle-outline" size={14} color={COLORS.textMuted} />
            <Text style={styles.moreText}>Showing the latest 50 movements.</Text>
          </View>
        </ScrollView>
      )}
    </AdminPage>
  );
}

function formatDate(value?: string | null): string {
  if (!value) return '';
  const d = new Date(value);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleString('en-US', {
    timeZone: 'Africa/Dar_es_Salaam',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

const styles = StyleSheet.create({
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  moreNote: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, paddingVertical: 8 },
  moreText: { color: COLORS.textMuted, fontSize: 12 },
});
