import { router } from 'expo-router';
import { Image, Linking, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, GroupLabel, KV, useAsyncData } from '../../components/adminkit';
import { ErrorView, LoadingView, OutlineButton } from '../../components/ui';
import { fetchGdQrCode, type GdQrCodePayload } from '../../lib/gdApi';
import { BASE_URL } from '../../lib/config';
import { COLORS, GD_ACCENT, RADIUS } from '../../lib/theme';
import { gdMenu } from '../../components/gdsidebar';

/**
 * QR Code — the mobile twin of graphic-designer/qr-code.blade.php. The
 * website draws the QR with QRCodeJS on a canvas and offers Download (PNG) /
 * Print buttons; on mobile the server renders the same URL with the same
 * settings (ECC H, PNG) and returns it as a data URI, so the QR itself is
 * identical. Download/Print stay browser-only actions on the website.
 */
export default function GdQrCode() {
  const { data, error, loading, reload } = useAsyncData<GdQrCodePayload>(() => fetchGdQrCode(), []);

  return (
    <AdminPage title="QR Code" eyebrow="Graphic Designer" accent={GD_ACCENT.main} onBack={() => router.back()} onMenu={gdMenu.open}>
      {loading ? <LoadingView label="Generating QR code…" /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          <Text style={styles.lead}>Customers scan this code to open the World Choice Perfume online shop.</Text>

          <View style={styles.card}>
            {data.image ? (
              <Image source={{ uri: data.image }} style={styles.qr} resizeMode="contain" accessibilityLabel="QR code to the online shop" />
            ) : (
              <View style={[styles.qr, styles.qrFallback]}>
                <Text style={styles.muted}>The QR image could not be generated. Pull to refresh.</Text>
              </View>
            )}
          </View>

          <GroupLabel>Link</GroupLabel>
          <KV label="Encoded URL:" value={data.url} />

          <OutlineButton
            label="Download / Print on the Website"
            icon="open-outline"
            onPress={() => {
              Linking.openURL(`${BASE_URL}/graphic-designer/qr-code`).catch(() => {
                // Nothing sensible to do if the device cannot open links.
              });
            }}
            style={{ marginTop: 8 }}
          />

          <Banner kind="connection" message="Downloading the PNG or printing the QR code happens in a browser on the website’s QR Code page." />
        </>
      ) : null}
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  lead: { color: COLORS.textSecondary, fontSize: 14, lineHeight: 21 },
  card: {
    alignSelf: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: RADIUS.lg,
    padding: 16,
  },
  qr: { width: 256, height: 256 },
  qrFallback: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.md,
    padding: 16,
  },
  muted: { color: COLORS.textMuted, fontSize: 13, textAlign: 'center' },
});
