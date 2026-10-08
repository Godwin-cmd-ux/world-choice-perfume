import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useState } from 'react';
import { Linking, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { AuthField, Banner } from '../components/authkit';
import { ScreenHeader } from '../components/ScreenHeader';
import { Card, GoldButton, LoadingView, SectionHeading } from '../components/ui';
import { errorMessage, fetchHome, isApiError, sendContact, type Contact } from '../lib/api';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * CONTACTS — the website's Contact Us section (home.blade.php #contact-us)
 * as a dedicated mobile page: the same eyebrow/heading/intro, the same
 * Call us / WhatsApp / Working hours / Email us / Customer care rows, and
 * the same message form (email, phone, heading, message) posting to the
 * contact endpoint the website form uses (POST /contact, JSON twin at
 * /api/contact) so every inquiry lands in Head Quarters-Mikocheni's queue.
 */
const EMAIL_RE = /^\S+@\S+\.\S+$/;

type Status = 'loading' | 'error' | 'ready';
type BannerKind = 'error' | 'success' | 'connection';

interface FieldErrors {
  email?: string;
  phone?: string;
  subject?: string;
  message?: string;
}

const INTRO =
  'Questions about an order, a fragrance, or anything else? Send us a message and our customer ' +
  'care team will get back to you as soon as possible.';

export default function ContactsScreen() {
  const [status, setStatus] = useState<Status>('loading');
  const [contact, setContact] = useState<Contact | null>(null);
  const [infoError, setInfoError] = useState('');

  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [banner, setBanner] = useState<{ kind: BannerKind; message: string } | null>(null);
  const [sending, setSending] = useState(false);

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

  const submit = async () => {
    const next: FieldErrors = {};
    const trimmedEmail = email.trim();
    const trimmedPhone = phone.trim();
    const trimmedSubject = subject.trim();
    const trimmedMessage = message.trim();

    if (!trimmedEmail) next.email = 'The email field is required.';
    else if (!EMAIL_RE.test(trimmedEmail)) next.email = 'The email field must be a valid email address.';
    if (!trimmedPhone) next.phone = 'The phone number field is required.';
    else if (trimmedPhone.length > 20) next.phone = 'The phone number may not be greater than 20 characters.';
    if (!trimmedSubject) next.subject = 'The heading field is required.';
    else if (trimmedSubject.length > 255) next.subject = 'The heading may not be greater than 255 characters.';
    if (!trimmedMessage) next.message = 'The message field is required.';

    if (Object.keys(next).length > 0) {
      setFieldErrors(next);
      setBanner(null);
      return;
    }

    setFieldErrors({});
    setBanner(null);
    setSending(true);
    try {
      const result = await sendContact({
        email: trimmedEmail,
        phone: trimmedPhone,
        subject: trimmedSubject,
        message: trimmedMessage,
      });
      setBanner({ kind: 'success', message: result.message });
      setEmail('');
      setPhone('');
      setSubject('');
      setMessage('');
    } catch (e) {
      if (isApiError(e) && e.kind === 'validation') {
        setFieldErrors(e.fields);
      }
      const connection = isApiError(e) && (e.kind === 'network' || e.kind === 'timeout');
      setBanner({ kind: connection ? 'connection' : 'error', message: errorMessage(e) });
    } finally {
      setSending(false);
    }
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

          <Card style={styles.formCard}>
            <View style={styles.note}>
              <Ionicons name="business-outline" size={14} color={COLORS.goldBright} />
              <Text style={styles.noteText}>
                Your message goes straight to our Head Quarters – Mikocheni customer care team.
              </Text>
            </View>

            {banner ? <Banner kind={banner.kind} message={banner.message} /> : null}

            <AuthField
              label="Email Address *"
              value={email}
              onChangeText={setEmail}
              placeholder="you@example.com"
              icon="mail-outline"
              keyboardType="email-address"
              error={fieldErrors.email}
            />
            <AuthField
              label="Phone Number *"
              value={phone}
              onChangeText={setPhone}
              placeholder="+255 7XX XXX XXX"
              icon="call-outline"
              keyboardType="phone-pad"
              error={fieldErrors.phone}
            />
            <AuthField
              label="Heading *"
              value={subject}
              onChangeText={setSubject}
              placeholder="What is your message about?"
              icon="pricetag-outline"
              autoCapitalize="sentences"
              error={fieldErrors.subject}
            />

            <View style={styles.field}>
              <Text style={styles.label}>Message *</Text>
              <View style={[styles.textAreaWrap, fieldErrors.message ? styles.textAreaError : null]}>
                <TextInput
                  value={message}
                  onChangeText={setMessage}
                  placeholder="Type your message here..."
                  placeholderTextColor={COLORS.textMuted}
                  style={styles.textArea}
                  multiline
                  textAlignVertical="top"
                />
              </View>
              {fieldErrors.message ? <Text style={styles.fieldError}>{fieldErrors.message}</Text> : null}
            </View>

            <GoldButton label="Send Message" icon="paper-plane-outline" onPress={submit} loading={sending} />
          </Card>
        </ScrollView>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
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
  formCard: {
    gap: 2,
  },
  note: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 12,
  },
  noteText: {
    flex: 1,
    color: COLORS.goldBright,
    fontSize: 12,
    lineHeight: 17,
  },
  field: {
    gap: 6,
    marginBottom: 14,
  },
  label: {
    color: '#D1D5DB',
    fontSize: 13.5,
    fontWeight: '600',
  },
  textAreaWrap: {
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    paddingVertical: 12,
    minHeight: 110,
  },
  textAreaError: {
    borderColor: COLORS.dangerBorder,
  },
  textArea: {
    color: COLORS.text,
    fontSize: 15,
    minHeight: 86,
  },
  fieldError: {
    color: COLORS.danger,
    fontSize: 12,
  },
  pressed: { opacity: 0.82 },
});
