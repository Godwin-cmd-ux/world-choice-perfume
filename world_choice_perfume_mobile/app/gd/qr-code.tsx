import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Image, Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, GroupLabel, KV, SearchInput, messageOf, useAsyncData } from '../../components/adminkit';
import { ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { fetchGdQrCode, type GdQrCodePayload, type GdQrProduct } from '../../lib/gdApi';
import { BASE_URL } from '../../lib/config';
import { COLORS, GD_ACCENT, RADIUS } from '../../lib/theme';
import { gdMenu } from '../../components/gdsidebar';

type Tab = 'general' | 'product';

/**
 * QR Code — the mobile twin of graphic-designer/qr-code.blade.php: the same
 * two tabs (General / Product), the same copy, the same purple selection
 * state the website paints with `.qr-tab[aria-selected="true"]`. The website
 * draws the code with QRCodeJS and offers Download/Print buttons; on mobile
 * the server renders the same URL with the same settings (ECC H, PNG data
 * URI) and the two buttons hand off to the website's QR page, which is where
 * a PNG download or a printout actually happens.
 */
export default function GdQrCode() {
  const { data, error, loading, reload } = useAsyncData<GdQrCodePayload>(() => fetchGdQrCode(), []);

  const [tab, setTab] = useState<Tab>('general');
  const [query, setQuery] = useState('');
  const [selected, setSelected] = useState<GdQrProduct | null>(null);
  const [productQr, setProductQr] = useState<GdQrCodePayload | null>(null);
  const [qrBusy, setQrBusy] = useState(false);
  const [qrError, setQrError] = useState('');

  const products = useMemo(() => data?.products ?? [], [data]);

  // Same search field the website's picker has (name · brand · category).
  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return products;
    return products.filter((p) =>
      `${p.name} ${p.brand ?? ''} ${p.category ?? ''}`.toLowerCase().includes(q),
    );
  }, [products, query]);

  const pickProduct = (product: GdQrProduct) => {
    setSelected(product);
    setProductQr(null);
    setQrError('');
    setQrBusy(true);
    fetchGdQrCode(product.id)
      .then(setProductQr)
      .catch((e) => setQrError(messageOf(e)))
      .finally(() => setQrBusy(false));
  };

  // Download and Print stay browser-only, exactly as documented for the app:
  // the website's QR page is the only place a file can be saved or a code
  // sent to a printer.
  const openWebsitePage = () => {
    Linking.openURL(`${BASE_URL}/graphic-designer/qr-code`).catch(() => {
      // Nothing sensible to do if the device cannot open links.
    });
  };

  const qrTile = (image: string | null, label: string) => (
    <View style={styles.qrTile}>
      {image ? (
        <Image source={{ uri: image }} style={styles.qr} resizeMode="contain" accessibilityLabel={label} />
      ) : (
        <View style={[styles.qr, styles.qrFallback]}>
          <Text style={styles.muted}>The QR image could not be generated. Pull to refresh.</Text>
        </View>
      )}
    </View>
  );

  const tabChip = (id: Tab, label: string, icon: keyof typeof Ionicons.glyphMap) => {
    const active = tab === id;
    return (
      <Pressable
        onPress={() => setTab(id)}
        style={({ pressed }) => [
          styles.tab,
          active && { backgroundColor: GD_ACCENT.main, borderColor: GD_ACCENT.main },
          pressed && { opacity: 0.85 },
        ]}
        accessibilityRole="tab"
        accessibilityState={{ selected: active }}
      >
        <Ionicons name={icon} size={15} color={active ? '#FFFFFF' : COLORS.textSecondary} />
        <Text style={[styles.tabText, active && styles.tabTextActive]}>{label}</Text>
      </Pressable>
    );
  };

  return (
    <AdminPage title="QR Code" eyebrow="Graphic Designer" accent={GD_ACCENT.main} onBack={() => router.back()} onMenu={gdMenu.open}>
      <View style={styles.tablist} accessibilityRole="tablist">
        {tabChip('general', 'Create General QR Code', 'globe-outline')}
        {tabChip('product', 'Create Product QR Code', 'sparkles-outline')}
      </View>

      {tab === 'general' ? (
        <View style={styles.panel}>
          <View style={[styles.medallion, { backgroundColor: GD_ACCENT.soft }]}>
            <Ionicons name="qr-code-outline" size={26} color={GD_ACCENT.main} />
          </View>
          <Text style={styles.panelTitle}>Website QR Code</Text>
          <Text style={styles.panelSub}>Scan this QR code to visit the World Choice Perfume website</Text>

          {loading ? <LoadingView label="Generating QR code…" /> : null}
          {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

          {data ? (
            <>
              {qrTile(data.image, 'QR code to the online shop')}

              <GroupLabel>Link</GroupLabel>
              <KV label="Encoded URL:" value={data.url} />

              <GoldButton label="Download / Print on the Website" icon="open-outline" onPress={openWebsitePage} style={styles.actionBtn} />
              <Banner kind="connection" message="Downloading the PNG or printing the QR code happens in a browser on the website’s QR Code page." />
            </>
          ) : null}
        </View>
      ) : (
        <View style={styles.panel}>
          <Text style={styles.panelTitle}>Product QR Code</Text>
          <Text style={styles.panelSub}>
            Choose a perfume and the code opens its order page — the customer lands on the product, not the shop.
          </Text>

          <SearchInput value={query} onChangeText={setQuery} placeholder="Search by name, brand or category…" />

          {loading ? <LoadingView label="Loading products…" /> : null}
          {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

          {data ? (
            products.length === 0 ? (
              <Text style={styles.emptyList}>No active products to link to yet.</Text>
            ) : filtered.length === 0 ? (
              <Text style={styles.emptyList}>No product matches that search.</Text>
            ) : (
              <View style={styles.list}>
                {filtered.map((product) => {
                  const active = selected != null && String(selected.id) === String(product.id);
                  return (
                    <Pressable
                      key={String(product.id)}
                      onPress={() => pickProduct(product)}
                      style={({ pressed }) => [
                        styles.row,
                        active && { borderColor: GD_ACCENT.main, backgroundColor: GD_ACCENT.soft },
                        pressed && styles.rowPressed,
                      ]}
                      accessibilityRole="button"
                      accessibilityState={{ selected: active }}
                    >
                      <View style={styles.rowBody}>
                        <Text style={styles.rowName} numberOfLines={1}>
                          {product.name}
                        </Text>
                        <Text style={styles.rowMeta} numberOfLines={1}>
                          {product.brand || '—'}
                          {product.category ? ` · ${product.category}` : ''}
                        </Text>
                      </View>
                      <Ionicons name="qr-code-outline" size={18} color={active ? GD_ACCENT.main : COLORS.textMuted} />
                    </Pressable>
                  );
                })}
              </View>
            )
          ) : null}

          {/* Filled in when a product is chosen; hidden until then so the page
              never shows an empty white square — same as the website. */}
          {selected ? (
            <View style={styles.result}>
              {qrBusy ? <LoadingView label="Generating QR code…" /> : null}
              {!qrBusy && qrError ? <Banner kind="error" message={qrError} /> : null}
              {!qrBusy && !qrError && productQr ? (
                <>
                  {qrTile(productQr.image, `QR code for ${selected.name}`)}
                  <Text style={styles.resultName}>{selected.name}</Text>
                  <Text style={styles.resultUrl}>{productQr.url}</Text>
                  <GoldButton label="Download / Print on the Website" icon="open-outline" onPress={openWebsitePage} style={styles.actionBtn} />
                  <Banner kind="connection" message="Downloading or printing this code happens in a browser on the website’s QR Code page." />
                </>
              ) : null}
            </View>
          ) : null}
        </View>
      )}
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  tablist: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 14,
  },
  tab: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
  },
  tabText: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '600',
  },
  tabTextActive: {
    color: '#FFFFFF',
  },
  panel: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },
  medallion: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginBottom: 12,
  },
  panelTitle: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '800',
    textAlign: 'center',
    marginBottom: 4,
  },
  panelSub: {
    color: COLORS.textSecondary,
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
    marginBottom: 14,
  },
  qrTile: {
    alignSelf: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: RADIUS.md,
    padding: 14,
    marginBottom: 8,
  },
  qr: { width: 232, height: 232 },
  qrFallback: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.md,
    padding: 16,
  },
  muted: { color: COLORS.textMuted, fontSize: 13, textAlign: 'center' },
  actionBtn: { marginTop: 4, marginBottom: 10 },
  list: {
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    overflow: 'hidden',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 12,
    paddingVertical: 11,
    backgroundColor: COLORS.bgRaised,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: COLORS.border,
  },
  rowPressed: { opacity: 0.8 },
  rowBody: { flex: 1 },
  rowName: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  rowMeta: {
    color: COLORS.textMuted,
    fontSize: 12,
    marginTop: 1,
  },
  emptyList: {
    color: COLORS.textMuted,
    fontSize: 13,
    textAlign: 'center',
    paddingVertical: 16,
  },
  result: {
    marginTop: 14,
    paddingTop: 14,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: COLORS.border,
  },
  resultName: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
    textAlign: 'center',
    marginTop: 6,
    marginBottom: 2,
  },
  resultUrl: {
    color: COLORS.textMuted,
    fontSize: 11.5,
    textAlign: 'center',
    marginBottom: 10,
  },
});
