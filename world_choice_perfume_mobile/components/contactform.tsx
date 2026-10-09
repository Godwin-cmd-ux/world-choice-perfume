/**
 * The message form from the website's Contact Us block (home.blade.php
 * #contact-us → `<form>`), extracted so both the Contacts tab and the home
 * page's contact section can render the exact same form.
 *
 * Self-contained: fields, validation mirroring the Laravel rules, and the
 * POST to the endpoint the website form uses (POST /contact, JSON twin at
 * /api/contact) so every inquiry lands in Head Quarters-Mikocheni's queue.
 */
import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { AuthField, Banner } from './authkit';
import { Card, GoldButton } from './ui';
import { errorMessage, isApiError, sendContact } from '../lib/api';
import { COLORS, RADIUS } from '../lib/theme';

const EMAIL_RE = /^\S+@\S+\.\S+$/;

type BannerKind = 'error' | 'success' | 'connection';

interface FieldErrors {
  email?: string;
  phone?: string;
  subject?: string;
  message?: string;
}

export function ContactForm() {
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [banner, setBanner] = useState<{ kind: BannerKind; message: string } | null>(null);
  const [sending, setSending] = useState(false);

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

  return (
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
  );
}

const styles = StyleSheet.create({
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
});
