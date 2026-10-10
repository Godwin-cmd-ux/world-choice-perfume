import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
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
import { Banner } from '../components/authkit';
import { GoldButton } from '../components/ui';
import {
  errorMessage,
  isApiError,
  resendRegistrationOtp,
  verifyRegistrationOtp,
} from '../lib/api';
import { staffSession } from '../lib/staffSession';
import { BUTTON, COLORS, RADIUS } from '../lib/theme';

/**
 * VERIFY YOUR EMAIL — the website's verify-otp step
 * (resources/views/auth/verify-otp.blade.php), shown after every successful
 * registration. The 6-digit code the backend emails is entered here and
 * verified server-side (POST /api/verify-otp); on success the pending
 * account state mirrors resources/views/auth/pending-approval.blade.php.
 */
type Phase = 'form' | 'working' | 'pending';

function first(value: string | string[] | undefined): string {
  return Array.isArray(value) ? (value[0] ?? '') : (value ?? '');
}

export default function OtpScreen() {
  const params = useLocalSearchParams<{
    email?: string;
    type?: string;
    user_id?: string;
    message?: string;
    signup?: string;
  }>();

  const email = first(params.email);
  const type = first(params.type) || 'registration';
  const userId = first(params.user_id);
  const message =
    first(params.message) || 'A verification code has been sent to your email.';

  const guarded = staffSession.isVerified();

  const [code, setCode] = useState('');
  const [phase, setPhase] = useState<Phase>('form');
  const [banner, setBanner] = useState<{ kind: 'error' | 'success' | 'connection'; text: string } | null>(null);
  const [fieldError, setFieldError] = useState('');
  const [pendingMessage, setPendingMessage] = useState('');

  useEffect(() => {
    if (!guarded || !email || !userId) router.replace('/staff');
  }, [guarded, email, userId]);

  if (!guarded || !email || !userId) return null;

  const verify = async () => {
    const value = code.trim();
    setBanner(null);
    if (!/^\d{6}$/.test(value)) {
      setFieldError('Please enter the 6-digit verification code.');
      return;
    }
    setFieldError('');
    setPhase('working');
    try {
      const result = await verifyRegistrationOtp({ email, otp: value, type, user_id: userId });
      if (result.pending) {
        setPendingMessage(result.message);
        setPhase('pending');
        return;
      }
      // Website: redirect to login with a success message.
      router.replace({ pathname: '/staff', params: { success: result.message } });
    } catch (e) {
      setPhase('form');
      if (isApiError(e)) {
        const otpError = e.fields.otp;
        if (otpError) setFieldError(otpError);
        setBanner({
          kind: e.kind === 'validation' || e.kind === 'auth' ? 'error' : 'connection',
          text: e.message,
        });
      } else {
        setBanner({ kind: 'connection', text: errorMessage(e) });
      }
    }
  };

  const resend = async () => {
    setBanner(null);
    setFieldError('');
    try {
      const result = await resendRegistrationOtp({ email, type, user_id: userId });
      setBanner({ kind: 'success', text: result.message });
    } catch (e) {
      setBanner({
        kind: isApiError(e) && (e.kind === 'network' || e.kind === 'timeout' || e.kind === 'server') ? 'connection' : 'error',
        text: errorMessage(e),
      });
    }
  };

  // ---- Pending-approval state (pending-approval.blade.php) ----
  if (phase === 'pending') {
    return (
      <SafeAreaView style={styles.safe} edges={['top']}>
        <ScrollView contentContainerStyle={styles.content}>
          <View style={styles.card}>
            <View style={styles.iconCircle}>
              <Ionicons name="time-outline" size={30} color={COLORS.goldBright} />
            </View>
            <Text style={styles.title}>Account Pending Approval</Text>
            <Text style={styles.lead}>{pendingMessage}</Text>
            <Text style={styles.notify}>
              We’ll notify you at <Text style={styles.notifyEmail}>{email}</Text> once approved.
            </Text>

            <Pressable
              onPress={() => router.replace('/staff')}
              style={({ pressed }) => [styles.pendingPrimary, pressed && styles.pressed]}
              accessibilityRole="button"
            >
              <Text style={styles.pendingPrimaryText}>Back to Login</Text>
            </Pressable>
            <Pressable
              onPress={() => router.replace('/')}
              style={({ pressed }) => [styles.pendingSecondary, pressed && styles.pressed]}
              accessibilityRole="button"
            >
              <Text style={styles.pendingSecondaryText}>Return to Homepage</Text>
            </Pressable>
          </View>
        </ScrollView>
      </SafeAreaView>
    );
  }

  // ---- Verify form ----
  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={24}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <View style={styles.card}>
            <View style={styles.iconCircle}>
              <Ionicons name="shield-checkmark-outline" size={30} color={COLORS.goldBright} />
            </View>
            <Text style={styles.title}>Verify Your Email</Text>
            <Text style={styles.lead}>{message}</Text>
            <Text style={styles.email}>{email}</Text>

            {banner ? <Banner kind={banner.kind} message={banner.text} /> : null}

            <Text style={styles.label}>Enter Verification Code</Text>
            <TextInput
              value={code}
              onChangeText={(text) => {
                setCode(text.replace(/[^0-9]/g, ''));
                if (fieldError) setFieldError('');
                if (banner) setBanner(null);
              }}
              placeholder="000000"
              placeholderTextColor={COLORS.textMuted}
              style={[styles.otpInput, fieldError ? styles.otpInputError : null]}
              keyboardType="number-pad"
              maxLength={6}
              accessibilityLabel="Verification code"
            />
            {fieldError ? <Text style={styles.fieldError}>{fieldError}</Text> : null}

            <GoldButton
              label="Verify Code"
              icon="checkmark-circle-outline"
              onPress={verify}
              loading={phase === 'working'}
              disabled={phase === 'working'}
              style={styles.verifyButton}
            />

            <Pressable onPress={resend} style={({ pressed }) => [styles.resend, pressed && styles.pressed]} hitSlop={8} accessibilityRole="button">
              <Ionicons name="refresh-outline" size={14} color={COLORS.textSecondary} />
              <Text style={styles.resendText}>
                Didn’t receive the code? <Text style={styles.resendGold}>Resend</Text>
              </Text>
            </Pressable>
          </View>

          <Pressable
            onPress={() => router.replace('/staff')}
            style={({ pressed }) => [styles.backLogin, pressed && styles.pressed]}
            accessibilityRole="button"
          >
            <Ionicons name="arrow-back" size={15} color={COLORS.textMuted} />
            <Text style={styles.backLoginText}>Back to Login</Text>
          </Pressable>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: COLORS.bg },
  flex: { flex: 1 },
  content: {
    flexGrow: 1,
    justifyContent: 'center',
    paddingHorizontal: 20,
    paddingVertical: 32,
    gap: 16,
  },
  pressed: { opacity: 0.85 },

  card: {
    backgroundColor: 'rgba(24, 24, 27, 0.85)',
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 22,
    alignItems: 'center',
    gap: 6,
  },
  iconCircle: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: COLORS.goldSoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  title: {
    color: COLORS.text,
    fontSize: 23,
    fontWeight: '800',
    textAlign: 'center',
  },
  lead: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    textAlign: 'center',
    lineHeight: 19,
  },
  email: {
    color: COLORS.goldBright,
    fontSize: 14.5,
    fontWeight: '700',
    marginBottom: 6,
  },

  label: {
    alignSelf: 'flex-start',
    color: COLORS.textSecondary,
    fontSize: 13.5,
    fontWeight: '600',
    marginTop: 6,
    marginBottom: 2,
  },
  otpInput: {
    width: '100%',
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '700',
    letterSpacing: 10,
    textAlign: 'center',
    paddingVertical: 12,
  },
  otpInputError: {
    borderColor: COLORS.dangerBorder,
  },
  fieldError: {
    color: COLORS.danger,
    fontSize: 12,
    alignSelf: 'flex-start',
  },

  verifyButton: {
    width: '100%',
    marginTop: 8,
    backgroundColor: BUTTON.fill,
  },
  resend: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 14,
    padding: 4,
  },
  resendText: {
    color: COLORS.textSecondary,
    fontSize: 13,
  },
  resendGold: {
    color: COLORS.goldBright,
    fontWeight: '700',
  },

  backLogin: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    padding: 6,
    alignSelf: 'center',
  },
  backLoginText: {
    color: COLORS.textMuted,
    fontSize: 13.5,
    fontWeight: '600',
  },

  notify: {
    color: COLORS.textMuted,
    fontSize: 12.5,
    textAlign: 'center',
    marginBottom: 10,
  },
  notifyEmail: {
    color: COLORS.textSecondary,
  },
  pendingPrimary: {
    width: '100%',
    backgroundColor: BUTTON.fill,
    borderRadius: RADIUS.md,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 6,
  },
  pendingPrimaryText: {
    color: BUTTON.text,
    fontSize: 15,
    fontWeight: '800',
  },
  pendingSecondary: {
    width: '100%',
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingVertical: 13,
    alignItems: 'center',
  },
  pendingSecondaryText: {
    color: COLORS.textSecondary,
    fontSize: 14,
    fontWeight: '600',
  },
});
