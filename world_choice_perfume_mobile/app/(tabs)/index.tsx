/**
 * HOME — the default tab, right after the native splash.
 *
 * This is the restyled landing page: the website's landing blade
 * (resources/views/home.blade.php) section for section, in the blade's own
 * order — hero, trust badges, shop-by-category, featured brands, our story
 * + stats, our branches, customer reviews, call-to-action, contact us
 * (details AND the message form) — replacing the old menu-button landing.
 * The six navigations those buttons carried now live in the bottom tab bar
 * (app/(tabs)/_layout.tsx).
 *
 * Dynamic content (branches, reviews, brands, category images, contact
 * details) comes from GET /api/home, which serves the exact data the
 * website homepage renders.
 */
import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Linking, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ContactForm } from '../../components/contactform';
import { ErrorView, GoldButton, LoadingView, OutlineButton, SectionHeading } from '../../components/ui';
import { errorMessage, fetchHome, type Branch, type HomePayload } from '../../lib/api';
import { COLORS, RADIUS } from '../../lib/theme';

const CATEGORIES = [
  { key: 'male', name: "Men's Fragrances", subtitle: 'Bold, powerful, unforgettable', icon: 'male-outline', tint: 'rgba(96, 165, 250, 0.16)' },
  { key: 'female', name: "Women's Fragrances", subtitle: 'Elegant, seductive, timeless', icon: 'female-outline', tint: 'rgba(244, 114, 182, 0.16)' },
  { key: 'unisex', name: 'Unisex Fragrances', subtitle: 'For everyone, by everyone', icon: 'people-outline', tint: 'rgba(192, 132, 252, 0.16)' },
  { key: 'accessories', name: 'Accessories', subtitle: 'Bottles & more', icon: 'flower-outline', tint: 'rgba(45, 212, 191, 0.16)' },
] as const;

const TRUST = [
  { icon: 'checkmark-circle-outline', title: '100% Authentic', subtitle: 'Guaranteed genuine' },
  { icon: 'car-outline', title: 'Fast Delivery', subtitle: 'Across Tanzania' },
  { icon: 'shield-checkmark-outline', title: 'Secure Shopping', subtitle: 'Safe & reliable' },
] as const;

const CONTACT_INTRO =
  'Questions about an order, a fragrance, or anything else? Send us a message and our customer ' +
  'care team will get back to you as soon as possible.';

type Status = 'loading' | 'error' | 'ready';

export default function HomeScreen() {
  const [status, setStatus] = useState<Status>('loading');
  const [data, setData] = useState<HomePayload | null>(null);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const scrollRef = useRef<ScrollView>(null);
  const storyY = useRef(0);

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

  const openBranch = (branch: Branch) => {
    const lat = Number(branch.latitude);
    const lng = Number(branch.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
    // Same Twende Dukani target as the website: Google Maps in the device
    // browser, geo: scheme as the offline fallback.
    openUrl(`https://www.google.com/maps/search/?api=1&query=${lat},${lng}`).catch(() => {
      openUrl(`geo:${lat},${lng}?q=${lat},${lng}(${branch.name})`);
    });
  };

  const scrollToStory = () => {
    scrollRef.current?.scrollTo({ y: Math.max(storyY.current - 12, 0), animated: true });
  };

  /** The brand header — logo medallion + wordmark, in place of a back bar. */
  const brandBar = (
    <View style={styles.brandBar}>
      <View style={styles.brandLogoRing}>
        <Image
          source={require('../../assets/images/logo.jpeg')}
          style={styles.brandLogo}
          contentFit="contain"
          accessibilityLabel="World Choice Perfume logo"
        />
      </View>
      <View style={styles.brandText}>
        <Text style={styles.brandTitle}>
          World Choice <Text style={styles.brandTitleGold}>Perfume</Text>
        </Text>
        <Text style={styles.brandTagline}>BE SMART, NUKIA KIJANJA</Text>
      </View>
    </View>
  );

  if (status === 'loading') {
    return (
      <SafeAreaView style={styles.safe}>
        {brandBar}
        <LoadingView label="Loading the homepage…" />
      </SafeAreaView>
    );
  }

  if (status === 'error' || !data) {
    return (
      <SafeAreaView style={styles.safe}>
        {brandBar}
        <ErrorView message={error} onRetry={() => load()} />
      </SafeAreaView>
    );
  }

  const branchCount = data.branches.length;
  const contact = data.contact;
  const reviews = data.reviews.filter((review) => (review.message ?? review.subject ?? '').trim().length > 0);

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      {brandBar}
      <ScrollView
        ref={scrollRef}
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
        {/* Hero — the blade's hero section: ambience, badge, headline,
            tagline quote, lead, Shop Now / Our Story actions. */}
        <View style={styles.hero}>
          <View pointerEvents="none" style={styles.haloTop} />
          <View pointerEvents="none" style={styles.haloBottom} />

          <View style={styles.badge}>
            <View style={styles.badgeDot} />
            <Text style={styles.badgeText}>TANZANIA’S #1 FRAGRANCE STORE</Text>
          </View>
          <Text style={styles.heroTitle}>
            World Choice{'\n'}
            <Text style={styles.heroTitleGold}>Perfume</Text>
          </Text>
          <Text style={styles.heroQuote}>“Be Smart, Nukia Kijanja”</Text>
          <Text style={styles.heroLead}>
            From the world’s most iconic perfume houses to niche artisanal scents — find your
            signature fragrance at World Choice Perfume. Be smart, choose the best.
          </Text>
          <View style={styles.heroActions}>
            <GoldButton
              label="Shop Now"
              icon="bag-handle-outline"
              onPress={() => router.push('/shop')}
              style={styles.heroPrimary}
            />
            <OutlineButton label="Our Story" icon="sparkles-outline" onPress={scrollToStory} />
          </View>
        </View>

        {/* Trust badges */}
        <View style={styles.trustCard}>
          {TRUST.map((item) => (
            <View key={item.title} style={styles.trustRow}>
              <View style={styles.trustIcon}>
                <Ionicons name={item.icon} size={17} color={COLORS.gold} />
              </View>
              <View style={styles.trustText}>
                <Text style={styles.trustTitle}>{item.title}</Text>
                <Text style={styles.trustSubtitle}>{item.subtitle}</Text>
              </View>
            </View>
          ))}
        </View>

        {/* Shop by category */}
        <View style={styles.section}>
          <SectionHeading eyebrow="Our Collection" title="Shop by" accent="Category" />
          <Text style={styles.sectionLead}>
            From bold masculine scents to elegant feminine fragrances and everything in between.
          </Text>
          <View style={styles.categoryGrid}>
            {CATEGORIES.map((cat) => {
              const image = (data.category_images?.[cat.key] ?? []).find(Boolean);
              return (
                <Pressable
                  key={cat.key}
                  onPress={() => router.push({ pathname: '/shop', params: { sex_category: cat.key } })}
                  style={({ pressed }) => [styles.categoryCard, pressed && styles.pressed]}
                  accessibilityRole="button"
                  accessibilityLabel={cat.name}
                >
                  {image ? (
                    <Image source={{ uri: image }} style={StyleSheet.absoluteFill} contentFit="cover" transition={200} />
                  ) : (
                    <View style={[StyleSheet.absoluteFill, { backgroundColor: COLORS.surfaceHigh }]} />
                  )}
                  <View style={styles.categoryShade} />
                  <View style={[styles.categoryTint, { backgroundColor: cat.tint }]} />
                  <View style={styles.categoryBody}>
                    <View style={styles.categoryIcon}>
                      <Ionicons name={cat.icon} size={16} color={COLORS.goldBright} />
                    </View>
                    <Text style={styles.categoryName}>{cat.name}</Text>
                    <Text style={styles.categorySubtitle}>{cat.subtitle}</Text>
                    <View style={styles.explore}>
                      <Text style={styles.exploreText}>Explore</Text>
                      <Ionicons name="arrow-forward" size={12} color={COLORS.gold} />
                    </View>
                  </View>
                </Pressable>
              );
            })}
          </View>
        </View>

        {/* Featured brands */}
        <View style={[styles.section, styles.sectionRaised]}>
          <SectionHeading eyebrow="World-Class Houses" title="Featured" accent="Brands" />
          {data.featured_brands.length > 0 ? (
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.brandRow}
            >
              {data.featured_brands.map((brand) => (
                <Pressable
                  key={String(brand.id)}
                  onPress={() => router.push({ pathname: '/shop', params: { brand: brand.name } })}
                  style={({ pressed }) => [styles.brandCard, pressed && styles.pressed]}
                  accessibilityRole="button"
                  accessibilityLabel={brand.name}
                >
                  {brand.logo_url ? (
                    <Image source={{ uri: brand.logo_url }} style={styles.brandLogoImg} contentFit="cover" transition={200} />
                  ) : (
                    <View style={styles.brandInitial}>
                      <Text style={styles.brandInitialText}>
                        {brand.name.slice(0, 2).toUpperCase()}
                      </Text>
                    </View>
                  )}
                  <Text style={styles.brandName} numberOfLines={2}>
                    {brand.name}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>
          ) : (
            <View style={styles.brandEmpty}>
              <Ionicons name="diamond-outline" size={22} color={COLORS.textMuted} />
              <Text style={styles.brandEmptyText}>Brands are being added. Check back soon!</Text>
            </View>
          )}
          <Pressable
            onPress={() => router.push({ pathname: '/shop' })}
            style={({ pressed }) => [styles.viewAll, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Text style={styles.viewAllText}>View All Brands</Text>
            <Ionicons name="arrow-forward" size={13} color={COLORS.gold} />
          </Pressable>
        </View>

        {/* Our story */}
        <View style={styles.section} onLayout={(e) => (storyY.current = e.nativeEvent.layout.y)}>
          <SectionHeading eyebrow="Our Story" title="Tanzania’s Trusted" accent="Fragrance House" />
          <Text style={styles.paragraph}>
            <Text style={styles.paragraphGold}>“Be Smart, Nukia Kijanja”</Text> — World Choice
            Perfume was founded with a singular vision: to bring the world’s finest fragrances to
            Tanzania. What started as a passion for scent has grown into Tanzania’s most trusted
            destination for authentic, premium perfumes.
          </Text>
          <Text style={styles.paragraph}>
            Every bottle we carry is sourced directly from authorized distributors and
            manufacturers, ensuring you receive only genuine products.
          </Text>
          <Text style={styles.paragraph}>
            With {branchCount} branch{branchCount === 1 ? '' : 'es'} across Tanzania, we’re always
            close to you. Our expert consultants are trained to help you find your perfect
            signature scent.
          </Text>

          <View style={styles.stats}>
            <View style={styles.stat}>
              <Text style={styles.statValue}>200+</Text>
              <Text style={styles.statLabel}>Fragrances</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.stat}>
              <Text style={styles.statValue}>{branchCount}</Text>
              <Text style={styles.statLabel}>Location{branchCount === 1 ? '' : 's'}</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.stat}>
              <Text style={styles.statValue}>10K+</Text>
              <Text style={styles.statLabel}>Happy Customers</Text>
            </View>
          </View>

          <View style={styles.trusted}>
            <Text style={styles.stars}>★★★★★</Text>
            <Text style={styles.trustedText}>Trusted by 10,000+ customers · 4.9/5</Text>
          </View>
        </View>

        {/* Our branches */}
        <View style={styles.section}>
          <SectionHeading eyebrow="Visit Us" title="Our" accent="Branches" />
          <Text style={styles.sectionLead}>
            Find us across Tanzania. Walk into any of our branches and let our experts help you
            find your perfect scent.
          </Text>
          {branchCount > 0 ? (
            data.branches.map((branch) => (
              <View key={String(branch.id)} style={styles.branchCard}>
                {branch.profile_picture ? (
                  <View style={styles.branchMedia}>
                    <Image
                      source={{ uri: branch.profile_picture }}
                      style={StyleSheet.absoluteFill}
                      contentFit="cover"
                      transition={200}
                    />
                    <View style={styles.branchMediaShade} />
                  </View>
                ) : (
                  <View style={styles.branchMediaEmpty}>
                    <Ionicons name="storefront-outline" size={22} color={COLORS.gold} />
                  </View>
                )}
                <View style={styles.branchBody}>
                  <View style={styles.branchHead}>
                    <Text style={styles.branchName} numberOfLines={1}>
                      {branch.name}
                    </Text>
                    <View style={styles.branchDot} />
                  </View>
                  <View style={styles.branchAddressRow}>
                    <Ionicons name="location-outline" size={14} color={COLORS.gold} />
                    <Text style={styles.branchAddress} numberOfLines={2}>
                      {branch.address ?? 'Location details coming soon'}
                    </Text>
                  </View>
                  {branch.latitude != null && branch.longitude != null ? (
                    <Pressable
                      onPress={() => openBranch(branch)}
                      style={({ pressed }) => [styles.twende, pressed && styles.pressed]}
                      accessibilityRole="button"
                      accessibilityLabel={`Twende Dukani — ${branch.name} on the map`}
                    >
                      <Ionicons name="walk-outline" size={13} color={COLORS.goldBright} />
                      <Text style={styles.twendeText}>Twende Dukani</Text>
                    </Pressable>
                  ) : null}
                </View>
              </View>
            ))
          ) : (
            <View style={styles.brandEmpty}>
              <Ionicons name="storefront-outline" size={22} color={COLORS.textMuted} />
              <Text style={styles.brandEmptyText}>Our branches are being set up. Stay tuned!</Text>
            </View>
          )}
        </View>

        {/* Customer reviews */}
        {reviews.length > 0 ? (
          <View style={[styles.section, styles.sectionRaised]}>
            <SectionHeading eyebrow="What Our Customers Say" title="Customer" accent="Reviews" />
            {reviews.slice(0, 6).map((review, index) => (
              <View key={`${review.email ?? 'review'}-${index}`} style={styles.reviewCard}>
                <Text style={styles.stars}>★★★★★</Text>
                <Text style={styles.reviewQuote}>
                  “{(review.message ?? review.subject ?? '').trim()}”
                </Text>
                <Text style={styles.reviewAuthor}>{review.email ?? 'A happy customer'}</Text>
              </View>
            ))}
          </View>
        ) : null}

        {/* Call to action */}
        <View style={[styles.section, styles.cta]}>
          <Text style={styles.ctaTitle}>
            Find Your <Text style={styles.ctaTitleGold}>Signature Scent</Text>
          </Text>
          <Text style={styles.sectionLead}>
            Visit any of our branches across Tanzania or shop online. Our fragrance experts are
            ready to help you discover your perfect match.
          </Text>
          <View style={styles.ctaActions}>
            <GoldButton
              label="Browse Collection"
              icon="bag-handle-outline"
              onPress={() => router.push('/shop')}
              style={styles.heroPrimary}
            />
            <OutlineButton label="Track Your Order" icon="cube-outline" onPress={() => router.push('/track')} />
          </View>
        </View>

        {/* Contact us — details and the blade's message form */}
        <View style={styles.section}>
          <SectionHeading eyebrow="We're Here to Help" title="Contact" accent="Us" />
          <Text style={styles.sectionLead}>{CONTACT_INTRO}</Text>
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
                <View>
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
                <View>
                  <Text style={styles.contactLabel}>WhatsApp</Text>
                  <Text style={styles.contactValue}>{contact.whatsapp}</Text>
                </View>
              </Pressable>
            ) : null}
            <View style={styles.contactRow}>
              <View style={styles.contactIcon}>
                <Ionicons name="time-outline" size={17} color={COLORS.gold} />
              </View>
              <View>
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
                <View>
                  <Text style={styles.contactLabel}>Email us</Text>
                  <Text style={styles.contactValue}>{contact.email}</Text>
                </View>
              </Pressable>
            ) : null}
            <View style={styles.contactRow}>
              <View style={styles.contactIcon}>
                <Ionicons name="headset-outline" size={17} color={COLORS.gold} />
              </View>
              <View>
                <Text style={styles.contactLabel}>Customer care</Text>
                <Text style={styles.contactValue}>Use the form — replies go to your email</Text>
              </View>
            </View>
          </View>

          <ContactForm />
        </View>

        <Text style={styles.copyright}>
          © {new Date().getFullYear()} World Choice Perfume · worldchoiceperfume.com
        </Text>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  content: {
    paddingHorizontal: 20,
    paddingBottom: 40,
    gap: 26,
  },
  pressed: { opacity: 0.85 },

  /* Brand bar — the landing's logo header, kept above the scroll. */
  brandBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
    backgroundColor: COLORS.bg,
  },
  brandLogoRing: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 1.5,
    borderColor: COLORS.goldBorder,
    backgroundColor: COLORS.surface,
    padding: 3,
  },
  brandLogo: {
    width: '100%',
    height: '100%',
    borderRadius: 19,
  },
  brandText: { flex: 1 },
  brandTitle: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '800',
    letterSpacing: 0.4,
  },
  brandTitleGold: {
    color: COLORS.gold,
  },
  brandTagline: {
    color: COLORS.goldBright,
    opacity: 0.75,
    fontSize: 9.5,
    letterSpacing: 2.6,
    fontWeight: '600',
    marginTop: 2,
  },

  /* Hero — badge, headline, quote, lead, actions, with gold halos. */
  hero: {
    overflow: 'hidden',
    backgroundColor: COLORS.bgRaised,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    padding: 20,
    marginTop: 4,
  },
  haloTop: {
    position: 'absolute',
    top: -70,
    right: -50,
    width: 190,
    height: 190,
    borderRadius: 95,
    backgroundColor: COLORS.goldHalo,
  },
  haloBottom: {
    position: 'absolute',
    bottom: -80,
    left: -60,
    width: 230,
    height: 230,
    borderRadius: 115,
    backgroundColor: 'rgba(66, 52, 14, 0.20)',
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 8,
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: RADIUS.pill,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    marginBottom: 16,
  },
  badgeDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: COLORS.goldBright,
  },
  badgeText: {
    color: COLORS.goldBright,
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 1.4,
  },
  heroTitle: {
    color: COLORS.text,
    fontSize: 38,
    lineHeight: 44,
    fontWeight: '800',
  },
  heroTitleGold: {
    color: COLORS.gold,
  },
  heroQuote: {
    color: COLORS.gold,
    fontSize: 16,
    fontStyle: 'italic',
    letterSpacing: 0.5,
    marginTop: 8,
  },
  heroLead: {
    color: COLORS.textSecondary,
    fontSize: 14.5,
    lineHeight: 22,
    marginTop: 12,
  },
  heroActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginTop: 18,
  },
  heroPrimary: {
    flexGrow: 1,
    minWidth: 150,
  },

  trustCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    gap: 12,
  },
  trustRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  trustIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: COLORS.goldSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  trustText: { flex: 1 },
  trustTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  trustSubtitle: {
    color: COLORS.textMuted,
    fontSize: 12,
    marginTop: 1,
  },

  section: {
    gap: 4,
  },
  sectionRaised: {
    backgroundColor: COLORS.bgRaised,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    marginTop: 0,
  },
  sectionLead: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 20,
    marginBottom: 12,
  },

  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  categoryCard: {
    width: '47.5%',
    minHeight: 150,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    justifyContent: 'flex-end',
  },
  categoryShade: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(13, 13, 13, 0.55)',
  },
  categoryTint: {
    ...StyleSheet.absoluteFillObject,
  },
  categoryBody: {
    padding: 12,
    gap: 2,
  },
  categoryIcon: {
    width: 30,
    height: 30,
    borderRadius: 9,
    backgroundColor: 'rgba(0, 0, 0, 0.35)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  categoryName: {
    color: COLORS.text,
    fontSize: 13.5,
    fontWeight: '800',
  },
  categorySubtitle: {
    color: COLORS.textSecondary,
    fontSize: 11,
  },
  explore: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: 6,
  },
  exploreText: {
    color: COLORS.gold,
    fontSize: 12,
    fontWeight: '600',
  },

  brandRow: {
    gap: 10,
    paddingVertical: 4,
    paddingRight: 4,
  },
  brandCard: {
    width: 110,
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 10,
    alignItems: 'center',
    gap: 8,
  },
  brandLogoImg: {
    width: '100%',
    height: 54,
    borderRadius: 8,
    backgroundColor: COLORS.surfaceHigh,
  },
  brandInitial: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: COLORS.surfaceHigh,
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandInitialText: {
    color: COLORS.gold,
    fontWeight: '800',
    fontSize: 15,
  },
  brandName: {
    color: COLORS.textSecondary,
    fontSize: 11.5,
    fontWeight: '600',
    textAlign: 'center',
  },
  brandEmpty: {
    alignItems: 'center',
    gap: 8,
    paddingVertical: 26,
  },
  brandEmptyText: {
    color: COLORS.textMuted,
    fontSize: 13,
  },
  viewAll: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 7,
    alignSelf: 'center',
    marginTop: 12,
    paddingHorizontal: 18,
    paddingVertical: 10,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    backgroundColor: COLORS.goldSoft,
  },
  viewAllText: {
    color: COLORS.gold,
    fontSize: 13,
    fontWeight: '700',
  },

  paragraph: {
    color: COLORS.textSecondary,
    fontSize: 14,
    lineHeight: 22,
    marginBottom: 8,
  },
  paragraphGold: {
    color: COLORS.gold,
    fontWeight: '700',
  },
  stats: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 14,
    marginTop: 6,
  },
  stat: {
    flex: 1,
    alignItems: 'center',
    gap: 3,
  },
  statDivider: {
    width: 1,
    height: 30,
    backgroundColor: COLORS.border,
  },
  statValue: {
    color: COLORS.gold,
    fontSize: 21,
    fontWeight: '800',
  },
  statLabel: {
    color: COLORS.textMuted,
    fontSize: 11,
  },
  trusted: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginTop: 12,
  },
  stars: {
    color: COLORS.goldBright,
    fontSize: 13,
    letterSpacing: 2,
  },
  trustedText: {
    color: COLORS.textSecondary,
    fontSize: 12,
  },

  /* Branches */
  branchCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
    marginBottom: 8,
  },
  branchMedia: {
    height: 92,
    width: '100%',
    backgroundColor: COLORS.surfaceHigh,
  },
  branchMediaShade: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(13, 13, 13, 0.25)',
  },
  branchMediaEmpty: {
    height: 72,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.bgRaised,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  branchBody: {
    padding: 14,
    gap: 7,
  },
  branchHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  branchName: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
    flexShrink: 1,
  },
  branchDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: COLORS.success,
  },
  branchAddressRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 7,
  },
  branchAddress: {
    color: COLORS.textSecondary,
    fontSize: 13,
    lineHeight: 18,
    flex: 1,
  },
  twende: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    backgroundColor: COLORS.goldSoft,
  },
  twendeText: {
    color: COLORS.goldBright,
    fontSize: 12,
    fontWeight: '700',
  },

  /* Reviews */
  reviewCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 8,
    gap: 7,
  },
  reviewQuote: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 20,
    fontStyle: 'italic',
  },
  reviewAuthor: {
    color: COLORS.gold,
    fontSize: 12.5,
    fontWeight: '700',
  },

  cta: {
    backgroundColor: COLORS.bgRaised,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    padding: 20,
    alignItems: 'center',
  },
  ctaTitle: {
    color: COLORS.text,
    fontSize: 23,
    fontWeight: '800',
    textAlign: 'center',
    marginBottom: 8,
  },
  ctaTitleGold: {
    color: COLORS.gold,
  },
  ctaActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    justifyContent: 'center',
    width: '100%',
  },

  contactCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    gap: 6,
    marginBottom: 12,
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

  copyright: {
    color: COLORS.textMuted,
    fontSize: 11,
    textAlign: 'center',
    marginTop: 4,
  },
});
