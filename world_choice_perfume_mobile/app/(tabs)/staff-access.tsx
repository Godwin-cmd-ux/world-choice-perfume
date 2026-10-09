import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { GoldButton } from '../../components/ui';
import { errorMessage, isApiError, verifyStaffAccess } from '../../lib/api';
import { staffSession } from '../../lib/staffSession';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * STAFF LOGIN — the secret-code prompt.
 *
 * Mirrors the website's Staff Access modal: the staff member enters the
 * company secret code, the app POSTs it to /api/verify-staff-access and the
 * SERVER decides (CompanySettingService) whether it is correct. The code is
 * never compared, stored, or logged inside the app — only `verified` comes
 * back. An invalid code, a connection failure and a server fault each get
 * their own message, so a dead network is never reported as a wrong code.
 */
type Phase = 'idle' | 'loading';

export default function StaffAccessScreen() {
  const [code, setCode] = useState('');
  const [phase, setPhase] = useState<Phase>('idle');
  const [fieldError, setFieldError] = useState('');
  const [banner, setBanner] = useState<{ kind: 'invalid' | 'connection'; message: string } | null>(null);

  // Like the website (the code flag lives for the whole session), a staff
  // member who already verified on this app launch skips straight to the
  // staff area instead of being asked for the code again.
  useEffect(() => {
    if (staffSession.isVerified()) router.replace('/staff');
  }, []);

  const submit = async () => {
    const value = code.trim();
    if (!value) {
      setBanner(null);
      setFieldError('Please enter the company secret code.');
      return;
    }
    setFieldError('');
    setBanner(null);
    setPhase('loading');
    try {
      const result = await verifyStaffAccess(value);
      if (result.verified) {
        // Only the server's verdict + its encrypted grant are kept —
        // never the code itself.
        staffSession.setVerified(true);
        staffSession.setAccessToken(result.access_token ?? null);
        setCode('');
        router.replace('/staff');
        return;
      }
      setBanner({ kind: 'invalid', message: 'Invalid company secret code. Please contact your administrator.' });
      setPhase('idle');
    } catch (e) {
      const kind = isApiError(e) ? e.kind : 'server';
      if (kind === 'validation' || kind === 'auth') {
        const fields = isApiError(e) ? e.fields : {};
        setBanner({ kind: 'invalid', message: fields.secret_code ?? errorMessage(e) });
      } else {
        // Network / timeout / server problems — explicitly NOT "wrong code".
        setBanner({ kind: 'connection', message: errorMessage(e) });
      }
      setPhase('idle');
    }
  };

  const loading = phase === 'loading';

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        {/* A ScrollView (instead of a fixed centered View) keeps the whole
            panel reachable when the keyboard covers the lower half of a small
            screen — content scrolls instead of being clipped at the top. */}
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.iconCircle}>
            <Ionicons name="lock-closed" size={26} color={COLORS.gold} />
          </View>
          <Text style={styles.title}>Staff Access</Text>
          <Text style={styles.subtitle}>Enter the company secret code to access staff pages</Text>

          <View style={styles.panel}>
            {banner ? (
              <View
                style={[
                  styles.banner,
                  banner.kind === 'invalid' ? styles.bannerInvalid : styles.bannerConnection,
                ]}
              >
                <Ionicons
                  name={banner.kind === 'invalid' ? 'alert-circle-outline' : 'cloud-offline-outline'}
                  size={16}
                  color={banner.kind === 'invalid' ? COLORS.danger : COLORS.warning}
                />
                <Text
                  style={[
                    styles.bannerText,
                    { color: banner.kind === 'invalid' ? COLORS.danger : COLORS.warning },
                  ]}
                >
                  {banner.message}
                </Text>
              </View>
            ) : null}

            <Text style={styles.label}>Company Secret Code</Text>
            <View style={[styles.inputWrap, fieldError ? styles.inputWrapError : null]}>
              <Ionicons name="key-outline" size={17} color={COLORS.textMuted} />
              <TextInput
                value={code}
                onChangeText={(text) => {
                  setCode(text);
                  if (fieldError) setFieldError('');
                  if (banner) setBanner(null);
                }}
                placeholder="Enter code"
                placeholderTextColor={COLORS.textMuted}
                style={styles.input}
                secureTextEntry
                autoCapitalize="none"
                autoCorrect={false}
                editable={!loading}
                onSubmitEditing={submit}
                returnKeyType="go"
                accessibilityLabel="Company secret code"
              />
            </View>
            {fieldError ? <Text style={styles.fieldError}>{fieldError}</Text> : null}

            <GoldButton label="Verify" icon="lock-open-outline" onPress={submit} loading={loading} disabled={loading} />

            <Text style={styles.note}>
              The staff login page is restricted. Contact your administrator if you do not have the
              code.
            </Text>
          </View>

          <Pressable
            onPress={() => router.back()}
            style={({ pressed }) => [styles.cancel, pressed && styles.pressed]}
            hitSlop={8}
            accessibilityRole="button"
          >
            <Ionicons name="arrow-back" size={15} color={COLORS.textSecondary} />
            <Text style={styles.cancelText}>Back to Home</Text>
          </Pressable>
        </ScrollView>
      </KeyboardAvoidingView>
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
    flexGrow: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    paddingVertical: 24,
  },
  pressed: { opacity: 0.7 },
  iconCircle: {
    width: 68,
    height: 68,
    borderRadius: 34,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  title: {
    color: COLORS.text,
    fontSize: 25,
    fontWeight: '800',
  },
  subtitle: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    textAlign: 'center',
    marginTop: 6,
    marginBottom: 20,
    maxWidth: 300,
    lineHeight: 19,
  },
  panel: {
    width: '100%',
    maxWidth: 380,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 18,
    gap: 10,
  },
  banner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    padding: 11,
  },
  bannerInvalid: {
    backgroundColor: COLORS.dangerBg,
    borderColor: COLORS.dangerBorder,
  },
  bannerConnection: {
    backgroundColor: 'rgba(252, 211, 77, 0.08)',
    borderColor: 'rgba(252, 211, 77, 0.30)',
  },
  bannerText: {
    flex: 1,
    fontSize: 12.5,
    lineHeight: 18,
  },
  label: {
    color: COLORS.textSecondary,
    fontSize: 12.5,
    fontWeight: '600',
  },
  inputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.bgRaised,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    height: 48,
  },
  inputWrapError: {
    borderColor: COLORS.dangerBorder,
  },
  input: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15.5,
    paddingVertical: 0,
    letterSpacing: 3,
  },
  fieldError: {
    color: COLORS.danger,
    fontSize: 12.5,
    marginTop: -4,
  },
  note: {
    color: COLORS.textMuted,
    fontSize: 11.5,
    textAlign: 'center',
    lineHeight: 16,
    marginTop: 2,
  },
  cancel: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 20,
    padding: 6,
  },
  cancelText: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    fontWeight: '600',
  },
});
