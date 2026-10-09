import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { ConfirmDialog, DataCard, SearchInput, StatGrid, StatTile, useAsyncData, messageOf } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import {
  deleteProductStock,
  deleteStockVariety,
  fetchProductStock,
  type SmStockRow,
} from '../../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../../lib/theme';
import { SmMenuButton } from '../../../components/smsidebar';

/**
 * Product Stock tab — the mobile twin of stock-manager/product-stock.blade.php:
 * the same flattened listing where a variety row replaces its parent aggregate,
 * the same total value, search, and per-row Edit / Delete. Variety rows carry
 * their own quantity+price edit; everything routes through the stock screens.
 */
export default function SmStock() {
  const [search, setSearch] = useState('');
  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(() => fetchProductStock({ search }), [search]);

  const [pendingDelete, setPendingDelete] = useState<SmStockRow | null>(null);
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const confirmDelete = async () => {
    if (!pendingDelete || busy) return;
    setBusy(true);
    setActionError(null);
    try {
      if (pendingDelete.kind === 'variety' && pendingDelete.variety && typeof pendingDelete.variety === 'object') {
        const id = (pendingDelete.variety as { id?: number | string }).id;
        if (id == null) throw new Error('This variety row cannot be deleted from here.');
        await deleteStockVariety(id);
      } else if (pendingDelete.stock_id != null) {
        await deleteProductStock(pendingDelete.stock_id);
      } else {
        throw new Error('Stock record not found.');
      }
      setPendingDelete(null);
      await reload();
    } catch (e) {
      setActionError(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const rows = data?.rows ?? [];

  return (
    <View style={styles.root}>
      <View style={styles.head}>
        <SmMenuButton />
        <Text style={[styles.eyebrow, { color: SM_ACCENT.main }]}>World Choice Perfumes</Text>
        <Text style={styles.h1}>Product Stock</Text>
        <SearchInput value={search} onChangeText={setSearch} placeholder="Search product, brand, category…" />
        <View style={styles.headActions}>
          <Pressable
            onPress={() => router.push('/sm/stock-entry')}
            style={({ pressed }) => [styles.primaryBtn, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Ionicons name="add-circle-outline" size={16} color="#052E1B" />
            <Text style={styles.primaryBtnText}>Stock In</Text>
          </Pressable>
          <Pressable
            onPress={() => router.push('/sm/low-stock')}
            style={({ pressed }) => [styles.secondaryBtn, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Ionicons name="alert-circle-outline" size={16} color={SM_ACCENT.light} />
            <Text style={styles.secondaryBtnText}>Low Stock</Text>
          </Pressable>
          <Pressable
            onPress={() => router.push('/sm/movements')}
            style={({ pressed }) => [styles.secondaryBtn, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Ionicons name="time-outline" size={16} color={SM_ACCENT.light} />
            <Text style={styles.secondaryBtnText}>Movements</Text>
          </Pressable>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={SM_ACCENT.main} />}
        keyboardShouldPersistTaps="handled"
      >
        {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

        {loading ? (
          <LoadingView label="Loading stock…" />
        ) : error && !data ? (
          <ErrorView message={error} onRetry={reload} />
        ) : rows.length === 0 ? (
          <EmptyView
            icon="cube-outline"
            title={search ? 'No matching stock' : 'No stock recorded yet'}
            hint={search ? 'Try another search term.' : 'Use Stock In to record your first entry.'}
          />
        ) : (
          <>
            <StatGrid>
              <StatTile label="Lines" value={rows.length} />
              <StatTile label="Total Value" value={formatMoney(data?.totalValue ?? 0)} tone="gold" />
            </StatGrid>

            {rows.map((row, i) => {
              const varietyId =
                row.variety && typeof row.variety === 'object' ? ((row.variety as { id?: number | string }).id ?? null) : null;
              const key = `${row.kind}-${row.stock_id ?? i}-${varietyId ?? i}`;
              const isVariety = row.kind === 'variety';
              return (
                <DataCard
                  key={key}
                  title={row.label}
                  subtitle={row.category ?? undefined}
                  lines={[
                    `${row.quantity} units · ${formatMoney(row.selling_price)} TZS each`,
                    row.date_received ? `Received ${row.date_received}` : null,
                  ]}
                  badge={isVariety ? 'Variety' : undefined}
                  badgeTone={isVariety ? 'muted' : 'gold'}
                >
                  <View style={styles.rowActions}>
                    <Pressable
                      onPress={() =>
                        router.push({
                          pathname: '/sm/stock-edit',
                          params: {
                            kind: row.kind,
                            stock_id: String(row.stock_id ?? ''),
                            variety_id: String(varietyId ?? ''),
                            label: row.label,
                            quantity: String(row.quantity),
                            price: String(row.selling_price),
                          },
                        })
                      }
                      style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                      accessibilityRole="button"
                    >
                      <Ionicons name="create-outline" size={15} color={SM_ACCENT.light} />
                      <Text style={styles.actionText}>Edit</Text>
                    </Pressable>
                    <Pressable
                      onPress={() => setPendingDelete(row)}
                      style={({ pressed }) => [styles.actionBtn, styles.actionDanger, pressed && styles.pressed]}
                      accessibilityRole="button"
                    >
                      <Ionicons name="trash-outline" size={15} color={COLORS.danger} />
                      <Text style={[styles.actionText, { color: COLORS.danger }]}>Delete</Text>
                    </Pressable>
                  </View>
                </DataCard>
              );
            })}
          </>
        )}
      </ScrollView>

      <ConfirmDialog
        visible={pendingDelete !== null}
        title="Delete stock record?"
        message={`“${pendingDelete?.label ?? ''}” and its variety breakdown at this branch will be removed.`}
        confirmLabel="Delete"
        danger
        loading={busy}
        onCancel={() => setPendingDelete(null)}
        onConfirm={confirmDelete}
      />
    </View>
  );
}

function formatMoney(value: number): string {
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
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
    backgroundColor: SM_ACCENT.main,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  primaryBtnText: { color: '#052E1B', fontSize: 13.5, fontWeight: '800' },
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
  rowActions: { flexDirection: 'row', gap: 8, marginTop: 10 },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  actionDanger: { backgroundColor: COLORS.dangerBg, borderColor: COLORS.dangerBorder },
  actionText: { color: SM_ACCENT.light, fontSize: 12.5, fontWeight: '700' },
});
