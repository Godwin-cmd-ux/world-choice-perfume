import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useState } from 'react';
import {
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner } from '../../components/authkit';
import { ContactForm } from '../../components/contactform';
import { ScreenHeader } from '../../components/ScreenHeader';
import { Card, LoadingView, SectionHeading } from '../../components/ui';
import { errorMessage, fetchHome, type Contact } from '../../lib/api';
import { COLORS } from '../../lib/theme';

/**
 * CONTACTS — the website's Contact Us section (home.blade.php #contact-us)
 * as a dedicated mobile page: the same eyebrow/heading/intro, the same
 * Call us / WhatsApp / Working hours / Email us / Customer care rows, and
 * the same message form (email, phone, heading, message) posting to the
 * contact endpoint the website form uses (POST /contact, JSON twin at
 * /api/contact) so every inquiry lands in Head Quarters-Mikocheni's queue.
 */
type Status = 'loading' | 'error' | 'ready';

const INTRO =
  'Questions about an order, a fragrance, or anything else? Send us a message and our customer ' +
  'care team will get back to you as soon as possible.';

export default function ContactsScreen() {
  const [status, setStatus] = useState<Status>('loading');
  const [contact, setContact] = useState<Contact | null>(null);
  const [infoError, setInfoError] = useState('');
  const insets = useSafeAreaInsets();

  const load = useCallback(async () => {
    setStatus('loading');
    try {
      const payload = await fetchHome();
      setContact(payload.contact);
      setInfoError('');
      setStatus('ready');
    } catch (e) {
      // The contact details live on the home payload; without them the form
      // still works, so the page degrades to the form instead of a dead end.
      setInfoError(errorMessage(e));
      setStatus('error');
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const openUrl = (url: string) => {
    Linking.openURL(url).catch(() => {
      // Nothing sensible to do if the device cannot open links.
    });
  };

  const infoRow = (
    icon: keyof typeof Ionicons.glyphMap,
    label: string,
    value: string | null,
    onPress?: () => void,
  ) => {
    if (!value) return null;
    const content = (
      <>
        <View style={styles.infoIcon}>
          <Ionicons name={icon} size={17} color={COLORS.gold} />
        </View>
        <View>
          <Text style={styles.infoLabel}>{label}</Text>
          <Text style={styles.infoValue}>{value}</Text>
        </View>
      </>
    );
    return onPress ? (
      <Pressable onPress={onPress} style={({ pressed }) => [styles.infoRow, pressed && styles.pressed]} accessibilityRole="button">
        {content}
      </Pressable>
    ) : (
      <View style={styles.infoRow}>{content}</View>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="Contacts" subtitle="Contact Us" />
      {status === 'loading' ? (
        <LoadingView label="Loading contact details…" />
      ) : (
        // Lift the form above the keyboard on iOS (Android resizes the
        // window itself) so the Send Message button stays reachable.
        <KeyboardAvoidingView
          style={styles.flex}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          keyboardVerticalOffset={insets.top + 60}
        >
          <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
          <SectionHeading eyebrow="We're Here to Help" title="Contact" accent="Us" />
          <Text style={styles.intro}>{INTRO}</Text>

          {status === 'error' ? (
            <Banner kind="connection" message={infoError} actionLabel="Try again" onAction={load} />
          ) : null}

          {contact ? (
            <Card style={styles.infoCard}>
              {infoRow('call-outline', 'Call us', contact.phone, contact.dial ? () => openUrl(`tel:${contact.dial}`) : undefined)}
              {infoRow('logo-whatsapp', 'WhatsApp', contact.whatsapp, contact.whatsapp_link ? () => openUrl(contact.whatsapp_link as string) : undefined)}
              {infoRow('time-outline', 'Working hours', contact.hours)}
              {infoRow('mail-outline', 'Email us', contact.email, contact.email ? () => openUrl(`mailto:${contact.email as string}`) : undefined)}
              <View style={styles.infoRow}>
                <View style={styles.infoIcon}>
                  <Ionicons name="headset-outline" size={17} color={COLORS.gold} />
                </View>
                <View>
                  <Text style={styles.infoLabel}>Customer care</Text>
                  <Text style={styles.infoValue}>Use the form — replies go to your email</Text>
                </View>
              </View>
            </Card>
          ) : null}

          <ContactForm />
          </ScrollView>
        </KeyboardAvoidingView>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  flex: { flex: 1 },
  content: {
    paddingHorizontal: 18,
    paddingBottom: 28,
    gap: 12,
  },
  intro: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 21,
    marginBottom: 2,
  },
  infoCard: {
    gap: 4,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 8,
  },
  infoIcon: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  infoLabel: {
    color: COLORS.textMuted,
    fontSize: 11.5,
  },
  infoValue: {
    color: COLORS.text,
    fontSize: 14.5,
    fontWeight: '600',
    marginTop: 1,
  },
  pressed: { opacity: 0.82 },
});
