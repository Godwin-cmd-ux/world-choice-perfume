import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
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
import { SafeAreaView } from 'react-native-safe-area-context';
import { AuthField, AuthHeader, Banner } from '../components/authkit';
import { ScreenHeader } from '../components/ScreenHeader';
import { GoldButton, OutlineButton } from '../components/ui';
import { errorMessage, isApiError, staffLogin, type StaffIdentityPayload } from '../lib/api';
import { BASE_URL } from '../lib/config';
import { roleInfo, staffSession, type StaffIdentity } from '../lib/staffSession';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * STAFF LOGIN PAGE — mobile counterpart of resources/views/auth/login.blade.php,
 * opened after the secret-code verification (the Blade page's
 * `session('staff_access_verified')` gate). Reproduces its structure:
 * logo + "Welcome Back", email/password card with Sign In and Forgot
 * Password?, then "Don't have an account?" with the exact six signup links
 * the Blade page shows, and Back to Home.
 *
 * After a successful login the user lands in the authenticated staff area
 * with their real role (POST /api/staff/login — the same backend checks the
 * website's AuthController::login performs).
 */
const SIGNUP_TILES = [
  { label: 'Cashier Sign Up', icon: 'person-outline', href: '/signup/cashier' },
  { label: 'Admin Sign Up', icon: 'briefcase-outline', href: '/signup/branch-admin' },
  { label: 'Stock Manager', icon: 'clipboard-outline', href: '/signup/stock-manager' },
  { label: 'Customer Care', icon: 'chatbubbles-outline', href: '/signup/customer-care' },
  { label: 'Seller Sign Up', icon: 'heart-outline', href: '/signup/seller' },
  { label: 'Graphic Designer', icon: 'color-palette-outline', href: '/signup/graphic-designer' },
] as const;

const EMAIL_RE = /^\S+@\S+\.\S+$/;

interface BannerState {
  kind: 'error' | 'success' | 'connection';
  message: string;
  reverify?: boolean;
}

export default function StaffScreen() {
  const params = useLocalSearchParams<{ success?: string }>();
  const successMessage = Array.isArray(params.success) ? params.success[0] : params.success;

  // Guard: only reachable through the secret-code prompt (same as the
  // website's staff_access_verified flag). Direct opens go back to landing.
  const [allowed] = useState(() => staffSession.isVerified());
  const [identity, setIdentity] = useState<StaffIdentity | null>(() => staffSession.getIdentity());
  const [phase, setPhase] = useState<'signin' | 'signedIn'>(() =>
    staffSession.getIdentity() ? 'signedIn' : 'signin',
  );

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fieldErrors, setFieldErrors] = useState<{ email?: string; password?: string }>({});
  const [banner, setBanner] = useState<BannerState | null>(
    successMessage ? { kind: 'success', message: successMessage } : null,
  );
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!allowed) router.replace('/');
  }, [allowed]);

  if (!allowed) return null;

  const openUrl = async (url: string) => {
    try {
      await Linking.openURL(url);
    } catch {
      // Nothing sensible to do if the device cannot open links.
    }
  };

  const submit = async () => {
    const nextErrors: { email?: string; password?: string } = {};
    const trimmed = email.trim();
    if (!trimmed) nextErrors.email = 'The email field is required.';
    else if (!EMAIL_RE.test(trimmed)) nextErrors.email = 'The email field must be a valid email address.';
    if (!password) nextErrors.password = 'The password field is required.';
    if (nextErrors.email || nextErrors.password) {
      setFieldErrors(nextErrors);
      setBanner(null);
      return;
    }

    setFieldErrors({});
    setBanner(null);
    setLoading(true);
    try {
      const payload: StaffIdentityPayload = await staffLogin(trimmed, password);
      setPassword('');
      staffSession.setIdentity(payload.user);
      // The server-issued session token unlocks the mobile Super Admin module
      // (every /api/admin call re-verifies the role server-side).
      staffSession.setSessionToken(payload['X-Staff-Session'] ?? null);
      setIdentity(payload.user);
      setPhase('signedIn');
    } catch (e) {
      if (isApiError(e)) {
        if (e.kind === 'validation') {
          setFieldErrors(e.fields);
          setBanner({ kind: 'error', message: e.message });
        } else {
          // auth (401/403) vs connection/server stay distinct — a dead
          // network is never reported as a wrong password.
          setBanner({
            kind: e.kind === 'auth' ? 'error' : 'connection',
            message: e.message,
            reverify: e.status === 403,
          });
        }
      } else {
        setBanner({ kind: 'connection', message: errorMessage(e) });
      }
    } finally {
      setLoading(false);
    }
  };

  const signOut = () => {
    staffSession.clear();
    setIdentity(null);
    setEmail('');
    setPassword('');
    setBanner(null);
    setFieldErrors({});
    setPhase('signin');
  };

  const role = roleInfo(identity?.role);

  // ---------------- Authenticated staff area ----------------
  if (phase === 'signedIn' && identity) {
    return (
      <SafeAreaView style={styles.safe} edges={['top']}>
        <ScreenHeader title="Staff Area" subtitle="World Choice Perfume" />
        <ScrollView contentContainerStyle={styles.signedInContent} showsVerticalScrollIndicator={false}>
          <View style={styles.identityCard}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{initialsOf(identity.name)}</Text>
            </View>
            <Text style={styles.identityName}>{identity.name ?? 'Staff member'}</Text>
            <View style={styles.roleChip}>
              <Ionicons name="briefcase-outline" size={13} color={COLORS.goldBright} />
              <Text style={styles.roleText}>{role.label}</Text>
            </View>
            {identity.branch?.name ? (
              <View style={styles.roleChip}>
                <Ionicons name="storefront-outline" size={13} color={COLORS.goldBright} />
                <Text style={styles.roleText}>{identity.branch.name}</Text>
              </View>
            ) : null}
            {identity.email ? <Text style={styles.identityEmail}>{identity.email}</Text> : null}
          </View>

          <View style={styles.actions}>
            {identity.role === 'super_admin' ? (
              <>
                <GoldButton
                  label="Open Super Admin Module"
                  icon="shield-checkmark-outline"
                  onPress={() => router.push('/admin')}
                />
                <OutlineButton
                  label="Open Website Dashboard"
                  icon="open-outline"
                  onPress={() => openUrl(`${BASE_URL}${role.path}`)}
                />
              </>
            ) : identity.role === 'graphic_designer' ? (
              <>
                <GoldButton
                  label="Open Graphic Designer Module"
                  icon="color-palette-outline"
                  onPress={() => router.push('/gd')}
                />
                <OutlineButton
                  label="Open Website Dashboard"
                  icon="open-outline"
                  onPress={() => openUrl(`${BASE_URL}${role.path}`)}
                />
              </>
            ) : identity.role === 'stock_manager' ? (
              <>
                <GoldButton
                  label="Open Stock Manager Module"
                  icon="cube-outline"
                  onPress={() => router.push('/sm')}
                />
                <OutlineButton
                  label="Open Website Dashboard"
                  icon="open-outline"
                  onPress={() => openUrl(`${BASE_URL}${role.path}`)}
                />
              </>
            ) : identity.role === 'customer_care' ? (
              <>
                <GoldButton
                  label="Open Customer Care Module"
                  icon="chatbubbles-outline"
                  onPress={() => router.push('/care')}
                />
                <OutlineButton
                  label="Open Website Dashboard"
                  icon="open-outline"
                  onPress={() => openUrl(`${BASE_URL}${role.path}`)}
                />
              </>
            ) : identity.role === 'branch_admin' ? (
              <>
                <GoldButton
                  label="Open Branch Admin Module"
                  icon="business-outline"
                  onPress={() => router.push('/ba')}
                />
                <OutlineButton
                  label="Open Website Dashboard"
                  icon="open-outline"
                  onPress={() => openUrl(`${BASE_URL}${role.path}`)}
                />
              </>
            ) : identity.role === 'seller' ? (
              <>
                <GoldButton
                  label="Open Seller Module"
                  icon="pricetag-outline"
                  onPress={() => router.push('/seller')}
                />
                <OutlineButton
                  label="Open Website Dashboard"
                  icon="open-outline"
                  onPress={() => openUrl(`${BASE_URL}${role.path}`)}
                />
              </>
            ) : identity.role === 'cashier' ? (
              <>
                <GoldButton
                  label="Open Cashier Module"
                  icon="cash-outline"
                  onPress={() => router.push('/cashier')}
                />
                <OutlineButton
                  label="Open Website Dashboard"
                  icon="open-outline"
                  onPress={() => openUrl(`${BASE_URL}${role.path}`)}
                />
              </>
            ) : (
              <GoldButton
                label="Open Staff Dashboard"
                icon="open-outline"
                onPress={() => openUrl(`${BASE_URL}${role.path}`)}
              />
            )}
            <OutlineButton label="Sign Out" icon="log-out-outline" onPress={signOut} style={styles.signOut} />
          </View>

          <Text style={styles.note}>
            The dashboard opens the live staff system at worldchoiceperfume.com, where every action
            stays protected by your staff permissions.
          </Text>
        </ScrollView>
      </SafeAreaView>
    );
  }

  // ---------------- Login page (login.blade.php) ----------------
  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={24}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>
          <AuthHeader title="Welcome Back" subtitle="Sign in to your staff account" />

          <View style={styles.card}>
            {banner ? (
              <Banner
                kind={banner.kind}
                message={banner.message}
                actionLabel={banner.reverify ? 'Verify Staff Code Again' : undefined}
                onAction={
                  banner.reverify
                    ? () => {
                        staffSession.invalidateAccess();
                        router.replace('/staff-access');
                      }
                    : undefined
                }
              />
            ) : null}

            <AuthField
              label="Email Address"
              value={email}
              onChangeText={(t) => {
                setEmail(t);
                if (fieldErrors.email) setFieldErrors({});
                if (banner) setBanner(null);
              }}
              placeholder="you@example.com"
              icon="mail-outline"
              keyboardType="email-address"
              error={fieldErrors.email}
              editable={!loading}
            />
            <AuthField
              label="Password"
              value={password}
              onChangeText={(t) => {
                setPassword(t);
                if (fieldErrors.password) setFieldErrors({});
                if (banner) setBanner(null);
              }}
              placeholder="••••••••"
              icon="lock-closed-outline"
              secure
              error={fieldErrors.password}
              editable={!loading}
              returnKeyType="go"
              onSubmitEditing={submit}
            />

            <GoldButton label="Sign In" icon="log-in-outline" onPress={submit} loading={loading} disabled={loading} />

            <View style={styles.forgotWrap}>
              <Text
                onPress={() => openUrl(`${BASE_URL}/forgot-password`)}
                style={styles.forgot}
                accessibilityRole="link"
              >
                <Ionicons name="key-outline" size={14} color={COLORS.textSecondary} /> Forgot Password?
              </Text>
            </View>
          </View>

          {/* Six signup links — exactly the ones the Blade login page shows. */}
          <View style={styles.registerBlock}>
            <Text style={styles.haveAccount}>Don’t have an account?</Text>
            <View style={styles.tileGrid}>
              {SIGNUP_TILES.map((tile) => (
                <Pressable
                  key={tile.href}
                  onPress={() => router.push(tile.href)}
                  style={({ pressed }) => [styles.tile, pressed && styles.tilePressed]}
                  accessibilityRole="button"
                  accessibilityLabel={tile.label}
                >
                  <Ionicons name={tile.icon} size={15} color={COLORS.textSecondary} />
                  <Text style={styles.tileLabel}>{tile.label}</Text>
                </Pressable>
              ))}
            </View>
          </View>

          <Text
            onPress={() => router.replace('/')}
            style={styles.backHome}
            accessibilityRole="link"
          >
            ← Back to Home
          </Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function initialsOf(name?: string | null): string {
  const parts = (name ?? '').trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: COLORS.bg },
  flex: { flex: 1 },
  content: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 44,
  },
  card: {
    backgroundColor: 'rgba(26, 26, 26, 0.5)',
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 18,
  },
  forgotWrap: {
    alignItems: 'center',
    marginTop: 14,
  },
  forgot: {
    color: COLORS.textSecondary,
    fontSize: 14,
    padding: 6,
  },

  registerBlock: {
    marginTop: 20,
  },
  haveAccount: {
    color: COLORS.textMuted,
    fontSize: 13.5,
    textAlign: 'center',
    marginBottom: 10,
  },
  tileGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  tile: {
    width: '47.5%',
    minHeight: 54,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 7,
    paddingVertical: 12,
    paddingHorizontal: 8,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: 'rgba(26, 26, 26, 0.5)',
  },
  tilePressed: {
    opacity: 0.8,
    borderColor: COLORS.goldBorder,
  },
  tileLabel: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '600',
    flexShrink: 1,
    textAlign: 'center',
  },
  backHome: {
    color: COLORS.textMuted,
    fontSize: 13.5,
    textAlign: 'center',
    marginTop: 22,
    padding: 6,
  },

  // Authenticated staff area
  signedInContent: {
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 40,
    gap: 14,
  },
  identityCard: {
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    padding: 24,
    gap: 8,
  },
  avatar: {
    width: 76,
    height: 76,
    borderRadius: 38,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1.5,
    borderColor: COLORS.goldBorder,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  avatarText: {
    color: COLORS.gold,
    fontSize: 24,
    fontWeight: '800',
    letterSpacing: 1,
  },
  identityName: {
    color: COLORS.text,
    fontSize: 19,
    fontWeight: '800',
    textAlign: 'center',
  },
  roleChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 5,
  },
  roleText: {
    color: COLORS.goldBright,
    fontSize: 12.5,
    fontWeight: '700',
  },
  identityEmail: {
    color: COLORS.textMuted,
    fontSize: 12.5,
    marginTop: 4,
  },
  actions: {
    gap: 10,
  },
  signOut: {
    width: '100%',
  },
  note: {
    color: COLORS.textMuted,
    fontSize: 12,
    textAlign: 'center',
    lineHeight: 17,
    paddingHorizontal: 10,
  },
});
