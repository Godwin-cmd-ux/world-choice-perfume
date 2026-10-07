import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { useCallback, useEffect, useState } from 'react';
import { Linking, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '../components/ScreenHeader';
import { EmptyView, ErrorView, LoadingView, SectionHeading } from '../components/ui';
import { errorMessage, fetchHome, type Branch, type HomePayload } from '../lib/api';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * BRANCHES — the website's "Our Branches" section as a dedicated mobile page.
 * Branch records come from GET /api/home (the same active-branch query the
 * website renders), so nothing is hard-coded; "Twende Dukani" opens the
 * branch location in the device's maps app, mirroring the website's
 * navigation page (/twende-dukani/{branch}).
 */
type Status = 'loading' | 'error' | 'ready';

function hasCoordinates(branch: Branch): boolean {
  const lat = Number(branch.latitude);
  const lng = Number(branch.longitude);
  return Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0);
}

function openMap(branch: Branch) {
  const lat = Number(branch.latitude);
  const lng = Number(branch.longitude);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
  const label = encodeURIComponent(branch.name);
  // Same destination as the website's "Twende Dukani" page, handed to the
  // device's maps app.
  const url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
  Linking.openURL(url).catch(() => {
    // Fall back to the plain geo: scheme before giving up.
    Linking.openURL(`geo:${lat},${lng}?q=${lat},${lng}(${label})`).catch(() => {});
  });
}

function BranchCard({ branch }: { branch: Branch }) {
  const navigable = hasCoordinates(branch);
  return (
    <View style={styles.card}>
      <View style={styles.cardImageWrap}>
        {branch.profile_picture ? (
          <Image
            source={{ uri: branch.profile_picture }}
            style={styles.cardImage}
            contentFit="cover"
            transition={200}
          />
        ) : (
          <View style={[styles.cardImage, styles.cardImageFallback]}>
            <Ionicons name="storefront-outline" size={30} color={COLORS.gold} />
          </View>
        )}
        <View style={styles.openDot} />
      </View>

      <View style={styles.cardBody}>
        <Text style={styles.branchName}>{branch.name}</Text>
        <View style={styles.addressRow}>
          <Ionicons name="location-outline" size={15} color={COLORS.gold} style={styles.addressIcon} />
          <Text style={styles.address}>{branch.address ?? 'Location details coming soon'}</Text>
        </View>

        {navigable ? (
          <Pressable
            onPress={() => openMap(branch)}
            style={({ pressed }) => [styles.navigate, pressed && styles.pressed]}
            accessibilityRole="button"
            accessibilityLabel={`Navigate to ${branch.name}`}
          >
            <Ionicons name="navigate-outline" size={14} color="#1A1400" />
            <Text style={styles.navigateText}>Twende Dukani</Text>
          </Pressable>
        ) : null}
      </View>
    </View>
  );
}

export default function BranchesScreen() {
  const [status, setStatus] = useState<Status>('loading');
  const [data, setData] = useState<HomePayload | null>(null);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async (mode: 'initial' | 'refresh' = 'initial') => {
    if (mode === 'refresh') setRefreshing(true);
    else setStatus('loading');
    try {
      const payload = await fetchHome();
      setData(payload);
      setStatus('ready');
    } catch (e) {
      setError(errorMessage(e));
      if (mode !== 'refresh') setStatus('error');
    } finally {
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const openUrl = async (url: string) => {
    try {
      await Linking.openURL(url);
    } catch {
      // Nothing sensible to do if the device cannot open links.
    }
  };

  const body = () => {
    if (status === 'loading') return <LoadingView label="Loading branches…" />;
    if (status === 'error' || !data) return <ErrorView message={error} onRetry={() => load()} />;

    if (data.branches.length === 0) {
      return (
        <EmptyView
          icon="storefront-outline"
          title="Our branches are being set up. Stay tuned!"
          hint="New locations will appear here as soon as they open."
        />
      );
    }

    const contact = data.contact;

    return (
      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={COLORS.gold}
            colors={[COLORS.gold]}
          />
        }
      >
        <SectionHeading eyebrow="Visit Us" title="Our" accent="Branches" />
        <Text style={styles.lead}>
          Find us across Tanzania. Walk into any of our branches and let our experts help you find
          your perfect scent.
        </Text>

        <View style={styles.grid}>
          {data.branches.map((branch) => (
            <BranchCard key={String(branch.id)} branch={branch} />
          ))}
        </View>

        <View style={styles.contactBlock}>
          <SectionHeading eyebrow="We're Here to Help" title="Contact" accent="Us" />
          <View style={styles.contactCard}>
            {contact.dial ? (
              <Pressable
                onPress={() => openUrl(`tel:${contact.dial}`)}
                style={({ pressed }) => [styles.contactRow, pressed && styles.pressed]}
                accessibilityRole="button"
              >
                <View style={styles.contactIcon}>
                  <Ionicons name="call-outline" size={17} color={COLORS.gold} />
                </View>
                <View style={styles.flex}>
                  <Text style={styles.contactLabel}>Call us</Text>
                  <Text style={styles.contactValue}>{contact.phone}</Text>
                </View>
              </Pressable>
            ) : null}
            {contact.whatsapp_link ? (
              <Pressable
                onPress={() => openUrl(contact.whatsapp_link as string)}
                style={({ pressed }) => [styles.contactRow, pressed && styles.pressed]}
                accessibilityRole="button"
              >
                <View style={styles.contactIcon}>
                  <Ionicons name="logo-whatsapp" size={17} color={COLORS.gold} />
                </View>
                <View style={styles.flex}>
                  <Text style={styles.contactLabel}>WhatsApp</Text>
                  <Text style={styles.contactValue}>{contact.whatsapp}</Text>
                </View>
              </Pressable>
            ) : null}
            <View style={styles.contactRow}>
              <View style={styles.contactIcon}>
                <Ionicons name="time-outline" size={17} color={COLORS.gold} />
              </View>
              <View style={styles.flex}>
                <Text style={styles.contactLabel}>Working hours</Text>
                <Text style={styles.contactValue}>{contact.hours}</Text>
              </View>
            </View>
            {contact.email ? (
              <Pressable
                onPress={() => openUrl(`mailto:${contact.email}`)}
                style={({ pressed }) => [styles.contactRow, pressed && styles.pressed]}
                accessibilityRole="button"
              >
                <View style={styles.contactIcon}>
                  <Ionicons name="mail-outline" size={17} color={COLORS.gold} />
                </View>
                <View style={styles.flex}>
                  <Text style={styles.contactLabel}>Email us</Text>
                  <Text style={styles.contactValue}>{contact.email}</Text>
                </View>
              </Pressable>
            ) : null}
          </View>
        </View>
      </ScrollView>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="Branches" subtitle="World Choice Perfume" />
      {body()}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  flex: { flex: 1 },
  pressed: { opacity: 0.85 },
  content: {
    paddingHorizontal: 18,
    paddingTop: 6,
    paddingBottom: 40,
    gap: 4,
  },
  lead: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 20,
    marginBottom: 12,
  },
  grid: {
    gap: 14,
  },
  card: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
  },
  cardImageWrap: {
    width: '100%',
    height: 150,
    backgroundColor: COLORS.bgRaised,
  },
  cardImage: {
    width: '100%',
    height: '100%',
  },
  cardImageFallback: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.bgRaised,
  },
  openDot: {
    position: 'absolute',
    top: 12,
    right: 12,
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: COLORS.success,
  },
  cardBody: {
    padding: 14,
    gap: 8,
  },
  branchName: {
    color: COLORS.text,
    fontSize: 16.5,
    fontWeight: '800',
  },
  addressRow: {
    flexDirection: 'row',
    gap: 6,
  },
  addressIcon: {
    marginTop: 2,
  },
  address: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 19,
    flex: 1,
  },
  navigate: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 6,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  navigateText: {
    color: COLORS.goldBright,
    fontSize: 12.5,
    fontWeight: '700',
  },

  contactBlock: {
    marginTop: 18,
  },
  contactCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    gap: 4,
  },
  contactRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 8,
  },
  contactIcon: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  contactLabel: {
    color: COLORS.textMuted,
    fontSize: 11.5,
  },
  contactValue: {
    color: COLORS.text,
    fontSize: 14.5,
    fontWeight: '600',
    marginTop: 1,
  },
});
