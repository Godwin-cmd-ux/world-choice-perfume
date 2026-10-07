import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, ConfirmDialog, DataCard, SearchInput, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { deleteProduct, fetchProducts, type SmCatalogueProduct } from '../../lib/smApi';
import { COLORS, SM_ACCENT, RADIUS } from '../../lib/theme';

/**
 * Product catalogue management — the mobile twin of the website's
 * /stock-manager/products screens: search, active/inactive filter, create /
 * edit (with images) and Deactivate (the server never hard-deletes).
 */
export default function SmProducts() {
  const [search, setSearch] = useState('');
  const [includeInactive, setIncludeInactive] = useState(false);
  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchProducts({ search: search || undefined, include_inactive: includeInactive }),
    [search, includeInactive],
  );

  const [pendingDeactivate, setPendingDeactivate] = useState<SmCatalogueProduct | null>(null);
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const products = data?.products ?? [];

  return (
    <AdminPage
      title="Products"
      eyebrow="Stock Manager"
      action={
        <Pressable
          onPress={() => router.push('/sm/product-form')}
          style={({ pressed }) => [styles.newBtn, pressed && styles.pressed]}
          accessibilityRole="button"
        >
          <Text style={styles.newBtnText}>New</Text>
        </Pressable>
      }
      accent={SM_ACCENT.main}
      onBack={() => router.back()}
    >
      <SearchInput value={search} onChangeText={setSearch} placeholder="Search name, brand, category…" />
      <ChipRow>
        <Chip label="Active only" active={!includeInactive} onPress={() => setIncludeInactive(false)} />
        <Chip label="Include inactive" active={includeInactive} onPress={() => setIncludeInactive(true)} />
      </ChipRow>

      {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}

      {loading ? (
        <LoadingView label="Loading products…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : products.length === 0 ? (
        <EmptyView icon="pricetags-outline" title={search ? 'No matches' : 'No products yet'} hint="Tap New to add the first product." />
      ) : (
        products.map((p) => (
          <DataCard
            key={String(p.id)}
            title={p.name}
            subtitle={`${p.brand ?? 'No brand'} · ${p.category ?? ''}`}
            lines={[
              [p.sex_category, p.fundamental_ingredient].filter(Boolean).join(' · ') || null,
              p.images?.length ? `${p.images.length} image(s)` : null,
            ]}
            badge={p.is_active === false ? 'Inactive' : 'Active'}
            badgeTone={p.is_active === false ? 'muted' : 'success'}
            onPress={() => router.push({ pathname: '/sm/product-form', params: { id: String(p.id) } })}
          >
            {p.is_active === false ? null : (
              <View style={styles.rowActions}>
                <Pressable
                  onPress={() => router.push({ pathname: '/sm/product-form', params: { id: String(p.id) } })}
                  style={({ pressed }) => [styles.actionBtn, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <Text style={styles.actionText}>Edit</Text>
                </Pressable>
                <Pressable
                  onPress={() => setPendingDeactivate(p)}
                  style={({ pressed }) => [styles.actionBtn, styles.actionDanger, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <Text style={[styles.actionText, { color: COLORS.danger }]}>Deactivate</Text>
                </Pressable>
              </View>
            )}
          </DataCard>
        ))
      )}

      <ConfirmDialog
        visible={pendingDeactivate !== null}
        title="Deactivate product?"
        message={`“${pendingDeactivate?.name ?? ''}” will be hidden from the catalogue. Stock records are not deleted.`}
        confirmLabel="Deactivate"
        danger
        loading={busy}
        onCancel={() => setPendingDeactivate(null)}
        onConfirm={async () => {
          if (!pendingDeactivate) return;
          const p = pendingDeactivate;
          setBusy(true);
          try {
            await deleteProduct(String(p.id));
            setPendingDeactivate(null);
            await reload();
          } catch (e) {
            setActionError(messageOf(e));
          } finally {
            setBusy(false);
          }
        }}
      />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  newBtn: { backgroundColor: SM_ACCENT.main, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  newBtnText: { color: '#052E1B', fontWeight: '800', fontSize: 13 },
  pressed: { opacity: 0.7 },
  rowActions: { flexDirection: 'row', gap: 8, marginTop: 10 },
  actionBtn: {
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
