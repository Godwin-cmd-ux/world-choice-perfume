import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner } from '../../../components/authkit';
import { Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { fetchReturns, fetchTransfers, type SmReturnRow, type SmTransfer } from '../../../lib/smApi';
import { BUTTON, COLORS, RADIUS, SM_ACCENT } from '../../../lib/theme';
import { SmMenuButton } from '../../../components/smsidebar';

/**
 * Transfers tab — outgoing transfers, with quick segments into Incoming,
 * Returns and Lost items (the website sidebar's transfer block). Products-
 * only branches may only move Product Stock; the server enforces that on
 * every call (403 with the website's message) and the New Transfer screen
 * only offers the product type for them.
 */
type Segment = 'out' | 'incoming' | 'returns';

export default function SmTransfers() {
  // Screens draw edge-to-edge, so the page keeps its own header clear of the status bar.
  const insets = useSafeAreaInsets();
  const [segment, setSegment] = useState<Segment>('out');

  const out = useAsyncData(() => fetchTransfers(), [segment]);
  const incoming = useAsyncData(() => fetchTransfers(), [segment]);
  const returns = useAsyncData(() => fetchReturns(), [segment]);

  const loader = segment === 'out' ? out : segment === 'incoming' ? incoming : returns;
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = loader;

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const transfers = segment === 'out' || segment === 'incoming' ? ((data as { transfers?: SmTransfer[] })?.transfers ?? []) : [];
  const returnRows = segment === 'returns' ? ((data as { rows?: SmReturnRow[] })?.rows ?? []) : [];
  const scope = (data as { scope?: { is_products_only?: boolean; in_cross_branch?: boolean } })?.scope;

  // Incoming is a filtered view of the same list: only rows destined here.
  const visibleTransfers =
    segment === 'incoming'
      ? transfers.filter((t) => (t.status ?? '') === 'in_transit')
      : transfers;

  return (
    <View style={[styles.root, { paddingTop: insets.top }]}>
      <View style={styles.head}>
        <SmMenuButton />
        <Text style={[styles.eyebrow, { color: SM_ACCENT.main }]}>World Choice Perfumes</Text>
        <Text style={styles.h1}>Stock Transfers</Text>
        <ChipRow>
          <Chip label="Outgoing" active={segment === 'out'} onPress={() => setSegment('out')} />
          <Chip label="Incoming" active={segment === 'incoming'} onPress={() => setSegment('incoming')} />
          <Chip label="Returns" active={segment === 'returns'} onPress={() => setSegment('returns')} />
        </ChipRow>
        <View style={styles.headActions}>
          <Pressable
            onPress={() => router.push('/sm/transfer-new')}
            style={({ pressed }) => [styles.primaryBtn, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Ionicons name="add-circle-outline" size={16} color={BUTTON.text} />
            <Text style={styles.primaryBtnText}>New Transfer</Text>
          </Pressable>
          <Pressable
            onPress={() => router.push('/sm/incoming')}
            style={({ pressed }) => [styles.secondaryBtn, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Ionicons name="download-outline" size={16} color={SM_ACCENT.light} />
            <Text style={styles.secondaryBtnText}>Receive</Text>
          </Pressable>
          <Pressable
            onPress={() => router.push('/sm/returns')}
            style={({ pressed }) => [styles.secondaryBtn, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Ionicons name="return-down-back-outline" size={16} color={SM_ACCENT.light} />
            <Text style={styles.secondaryBtnText}>Returned items</Text>
          </Pressable>
        </View>
        {scope?.is_products_only ? (
          <View style={styles.note}>
            <Ionicons name="information-circle-outline" size={14} color={COLORS.info} />
            <Text style={styles.noteText}>Products-only branch: Product Stock transfers only.</Text>
          </View>
        ) : null}
        {scope?.in_cross_branch ? (
          <View style={styles.note}>
            <Ionicons name="eye-outline" size={14} color={COLORS.warning} />
            <Text style={styles.noteText}>Monitoring another branch — read-only.</Text>
          </View>
        ) : null}
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={SM_ACCENT.main} />}
      >
        {error ? <Banner kind="error" message={error} /> : null}

        {loading ? (
          <LoadingView label="Loading transfers…" />
        ) : error && !data ? (
          <ErrorView message={error} onRetry={reload} />
        ) : segment === 'returns' ? (
          returnRows.length === 0 ? (
            <EmptyView icon="return-down-back-outline" title="No returned items" hint="Items rejected by a receiving branch appear here." />
          ) : (
            returnRows.map((row, i) => (
              <DataCard
                key={String(row.item?.id ?? i)}
                title={row.item_label}
                subtitle={row.transfer_number ?? undefined}
                lines={[`To ${row.to_branch_name ?? '—'}`, row.returned_at ? `Returned ${formatDate(row.returned_at)}` : null]}
                badge={row.item?.return_status ?? 'returned'}
                badgeTone={row.item?.return_status === 'resent' ? 'success' : row.item?.return_status === 'written_off' ? 'danger' : 'warning'}
                onPress={() => router.push('/sm/returns')}
              />
            ))
          )
        ) : visibleTransfers.length === 0 ? (
          <EmptyView
            icon="swap-horizontal-outline"
            title={segment === 'incoming' ? 'No incoming transfers' : 'No transfers yet'}
            hint={segment === 'incoming' ? 'Transfers destined for your branch appear here.' : 'Use New Transfer to send stock to another branch.'}
          />
        ) : (
          visibleTransfers.map((t) => (
            <DataCard
              key={String(t.id)}
              title={t.transfer_number ?? `Transfer #${t.id}`}
              subtitle={t.stock_type_label ?? t.stock_type ?? undefined}
              lines={[
                segment === 'incoming' ? `From ${t.from_branch_name ?? '—'}` : `To ${t.to_branch_name ?? '—'}`,
                t.officer_name ? `Officer: ${t.officer_name}` : null,
                t.created_at ? formatDate(t.created_at) : null,
              ]}
              badge={t.status ?? 'in_transit'}
              badgeTone={t.status === 'received' ? 'success' : t.status === 'in_transit' ? 'warning' : 'muted'}
              onPress={() => router.push({ pathname: '/sm/transfer-detail', params: { id: String(t.id) } })}
            >
              {t.items_total != null ? (
                <Text style={styles.itemCounts}>
                  {t.items_total} item(s) · {t.items_pending ?? 0} pending{t.items_returned ? ` · ${t.items_returned} returned` : ''}
                </Text>
              ) : null}
            </DataCard>
          ))
        )}

        <GroupLabel>Transfer tools</GroupLabel>
        <View style={styles.toolRow}>
          <ToolTile icon="document-text-outline" label="Declare lost item" path="/sm/returns" />
          <ToolTile icon="shield-checkmark-outline" label="Returned Stock report" path="/sm/returned-stock" />
        </View>
      </ScrollView>
    </View>
  );
}

function ToolTile({ icon, label, path }: { icon: keyof typeof Ionicons.glyphMap; label: string; path: string }) {
  return (
    <Pressable onPress={() => router.push(path as never)} style={({ pressed }) => [styles.toolTile, pressed && styles.pressed]} accessibilityRole="button">
      <Ionicons name={icon} size={17} color={SM_ACCENT.main} />
      <Text style={styles.toolTileText}>{label}</Text>
    </Pressable>
  );
}

function formatDate(value?: string | null): string {
  if (!value) return '';
  const d = new Date(value);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleDateString('en-US', { timeZone: 'Africa/Dar_es_Salaam', month: 'short', day: 'numeric', year: 'numeric' });
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  head: { paddingHorizontal: 16, paddingTop: 16, gap: 10 },
  eyebrow: { fontSize: 11, fontWeight: '700', letterSpacing: 3, textTransform: 'uppercase' },
  h1: { color: COLORS.text, fontSize: 24, fontWeight: '800' },
  headActions: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  primaryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: BUTTON.fill,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  primaryBtnText: { color: BUTTON.text, fontSize: 13.5, fontWeight: '800' },
  secondaryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  secondaryBtnText: { color: SM_ACCENT.light, fontSize: 13, fontWeight: '700' },
  pressed: { opacity: 0.7 },
  scroll: { padding: 16, paddingBottom: 40, gap: 10 },
  itemCounts: { color: COLORS.textMuted, fontSize: 12, marginTop: 6 },
  note: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    backgroundColor: 'rgba(96, 165, 250, 0.08)',
    borderWidth: 1,
    borderColor: 'rgba(96, 165, 250, 0.25)',
    borderRadius: RADIUS.md,
    padding: 9,
  },
  noteText: { color: COLORS.textSecondary, fontSize: 12, flex: 1 },
  toolRow: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  toolTile: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  toolTileText: { color: COLORS.text, fontSize: 13, fontWeight: '700' },
});
