import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { Chip, ChipRow, ConfirmDialog, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../../components/ui';
import { deleteGdBrand, fetchGdBrands, type GdBrand } from '../../../lib/gdApi';
import { COLORS, GD_ACCENT, RADIUS } from '../../../lib/theme';
import { GdMenuButton } from '../../../components/gdsidebar';

type Filter = 'all' | 'active' | 'inactive';

/**
 * Brands list — the mobile twin of graphic-designer/brands/index.blade.php:
 * the same counts (Total / Active / Inactive / With Logo), then every brand
 * as a card with logo (or the 2-letter purple initials), name + added date,
 * product count, Active/Inactive pill and edit / delete actions. Delete is
 * disabled while a brand is still used by products (server returns 422 too).
 */
export default function GdBrands() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData<{
    brands: GdBrand[];
    counts: { total: number; active: number; inactive: number; with_logo: number };
  }>(() => fetchGdBrands(), []);

  const [filter, setFilter] = useState<Filter>('all');
  const [pendingDelete, setPendingDelete] = useState<GdBrand | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const confirmDelete = async () => {
    if (!pendingDelete || deleting) return;
    setDeleting(true);
    setActionError(null);
    try {
      await deleteGdBrand(String(pendingDelete.id));
      setPendingDelete(null);
      await reload();
    } catch (e) {
      setActionError(e instanceof Error ? e.message : 'Something went wrong. Please try again.');
      setPendingDelete(null);
    } finally {
      setDeleting(false);
    }
  };

  if (loading) return <LoadingView label="Loading brands…" />;
  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }
  if (error && !data) return <ErrorView message={error} onRetry={reload} />;

  const brands = (data?.brands ?? []).filter((b) =>
    filter === 'all' ? true : filter === 'active' ? b.is_active !== false : b.is_active === false,
  );
  const c = data?.counts;

  return (
    <View style={styles.root}>
      <View style={styles.scroll}>
        <View style={styles.headRow}>
          <View>
            <GdMenuButton />
            <Text style={[styles.eyebrow, { color: GD_ACCENT.main }]}>Graphic Designer</Text>
            <Text style={styles.h1}>Brands</Text>
          </View>
          <Pressable
            onPress={() => router.push('/gd/brand-form')}
            style={({ pressed }) => [styles.addBtn, pressed && styles.pressed]}
            accessibilityRole="button"
            accessibilityLabel="Create brand"
          >
            <Ionicons name="add" size={22} color="#1A1400" />
          </Pressable>
        </View>

        {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

        {c ? (
          <StatGrid>
            <StatTile label="Total" value={c.total} />
            <StatTile label="Active" value={c.active} tone="success" />
            <StatTile label="Inactive" value={c.inactive} tone="warning" />
            <StatTile label="With Logo" value={c.with_logo} tone="info" />
          </StatGrid>
        ) : null}

        <ChipRow>
          <Chip label="All" active={filter === 'all'} onPress={() => setFilter('all')} />
          <Chip label="Active" active={filter === 'active'} onPress={() => setFilter('active')} />
          <Chip label="Inactive" active={filter === 'inactive'} onPress={() => setFilter('inactive')} />
        </ChipRow>

        {brands.length === 0 ? (
          <EmptyView
            icon="pricetag-outline"
            title={filter === 'all' ? 'No brands yet' : `No ${filter} brands`}
            hint={filter === 'all' ? 'Tap + to add the first brand.' : 'Try another filter.'}
          />
        ) : (
          brands.map((brand) => {
            const inUse = (brand.product_count ?? 0) > 0;
            return (
              <DataCard
                key={String(brand.id)}
                title={brand.name}
                lines={[
                  `Added ${formatDate(brand.created_at)}`,
                  `${brand.product_count ?? 0} product${(brand.product_count ?? 0) === 1 ? '' : 's'}`,
                ]}
                badge={brand.is_active === false ? 'Inactive' : 'Active'}
                badgeTone={brand.is_active === false ? 'muted' : 'success'}
                right={
                  brand.logo_url ? (
                    <Image source={{ uri: brand.logo_url }} style={styles.logo} />
                  ) : (
                    <View style={styles.initials}>
                      <Text style={styles.initialsText}>{initialsOf(brand.name)}</Text>
                    </View>
                  )
                }
              >
                <View style={styles.rowActions}>
                  <Pressable
                    onPress={() => router.push({ pathname: '/gd/brand-form', params: { id: String(brand.id) } })}
                    style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                    accessibilityRole="button"
                  >
                    <Ionicons name="create-outline" size={15} color={GD_ACCENT.light} />
                    <Text style={styles.actionText}>Edit</Text>
                  </Pressable>
                  <Pressable
                    onPress={() => setPendingDelete(brand)}
                    disabled={inUse}
                    style={({ pressed }) => [
                      styles.actionBtn,
                      styles.actionDanger,
                      inUse && styles.actionDisabled,
                      pressed && styles.pressed,
                    ]}
                    accessibilityRole="button"
                    accessibilityState={{ disabled: inUse }}
                    accessibilityLabel={inUse ? 'Brand in use — cannot delete' : 'Delete brand'}
                  >
                    <Ionicons name="trash-outline" size={15} color={COLORS.danger} />
                    <Text style={[styles.actionText, { color: COLORS.danger }]}>Delete</Text>
                  </Pressable>
                </View>
                {inUse ? <Text style={styles.inUseHint}>Brand in use — cannot delete</Text> : null}
              </DataCard>
            );
          })
        )}

        <GroupLabel>Active brands appear in Featured Brands on the home page</GroupLabel>
      </View>

      <ConfirmDialog
        visible={pendingDelete !== null}
        title="Delete brand?"
        message={`“${pendingDelete?.name ?? ''}” will be removed permanently. This cannot be undone.`}
        confirmLabel="Delete"
        danger
        loading={deleting}
        onCancel={() => setPendingDelete(null)}
        onConfirm={confirmDelete}
      />
    </View>
  );
}

function initialsOf(name: string): string {
  const parts = (name ?? '').trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '??';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
}

function formatDate(value?: string | null): string {
  if (!value) return '';
  const d = new Date(value);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleDateString('en-US', { timeZone: 'Africa/Dar_es_Salaam', month: 'short', day: 'numeric', year: 'numeric' });
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  scroll: { padding: 16, paddingBottom: 40, gap: 12 },
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  headRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  eyebrow: { fontSize: 11, fontWeight: '700', letterSpacing: 3, textTransform: 'uppercase' },
  h1: { color: COLORS.text, fontSize: 25, fontWeight: '800', marginTop: 4 },
  addBtn: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: GD_ACCENT.main,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: { width: 40, height: 40, borderRadius: 8, backgroundColor: COLORS.surfaceHigh },
  initials: {
    width: 40,
    height: 40,
    borderRadius: 8,
    backgroundColor: GD_ACCENT.soft,
    borderWidth: 1,
    borderColor: GD_ACCENT.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  initialsText: { color: GD_ACCENT.light, fontSize: 14, fontWeight: '800' },
  rowActions: { flexDirection: 'row', gap: 8 },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: RADIUS.md,
    backgroundColor: GD_ACCENT.soft,
    borderWidth: 1,
    borderColor: GD_ACCENT.border,
  },
  actionDanger: { backgroundColor: COLORS.dangerBg, borderColor: COLORS.dangerBorder },
  actionDisabled: { opacity: 0.45 },
  actionText: { color: GD_ACCENT.light, fontSize: 13, fontWeight: '700' },
  inUseHint: { color: COLORS.textMuted, fontSize: 11.5 },
  pressed: { opacity: 0.75 },
});
