import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  errorMessage,
  fetchHome,
  isApiError,
  registerStaff,
  type Branch,
  type PhotoAttachment,
  type StaffSignupType,
} from '../lib/api';
import { staffSession } from '../lib/staffSession';
import { COLORS, GOLD_SHADOW, RADIUS } from '../lib/theme';
import { AuthField, AuthHeader, Banner } from './authkit';

/**
 * SignupForm — the engine behind the six dedicated signup pages.
 *
 * Each page passes the exact shape its Blade counterpart has (title,
 * subtitle, button label/colour, branch selector, secret-code field, photo
 * uploader, approval note), so the real functional differences between the
 * six types are preserved while layout/styling stay consistent. Validation
 * mirrors the Laravel rules for immediate feedback; the server's 422
 * response is the authority and its messages are shown per field.
 */
export interface SignupConfig {
  type: StaffSignupType;
  title: string;
  subtitle: string;
  buttonLabel: string;
  /** Solid stand-in for the Blade button gradient (no gradient lib in app). */
  buttonColor: string;
  buttonTextColor: string;
  nameLabel: string;
  /** Present only for the four types with a branch selector (branch-admin shows the fancy label). */
  branchLabel?: string;
  /** Five of six forms carry the Company Secret Code field (cashier does not). */
  secretCode: boolean;
  /** Cashier only — optional profile photo, multipart like the Blade form. */
  photo: boolean;
  /** Info line under the button (approval expectations) — branch-admin's Blade has none. */
  note?: string;
}

interface FieldErrors {
  name?: string;
  email?: string;
  phone?: string;
  branch_id?: string;
  secret_code?: string;
  password?: string;
  password_confirmation?: string;
}

type BannerState = { kind: 'error' | 'connection'; message: string; reverify?: boolean } | null;

const EMAIL_RE = /^\S+@\S+\.\S+$/;

export function SignupForm({ config }: { config: SignupConfig }) {
  const guarded = staffSession.isVerified();

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [secret, setSecret] = useState('');
  const [branchId, setBranchId] = useState('');
  const [branchOpen, setBranchOpen] = useState(false);
  const [branches, setBranches] = useState<Branch[] | null>(null);
  const [photo, setPhoto] = useState<PhotoAttachment | null>(null);

  const [errors, setErrors] = useState<FieldErrors>({});
  const [banner, setBanner] = useState<BannerState>(null);
  const [loading, setLoading] = useState(false);

  // Same gate as the website: registration is only reachable after the
  // staff secret code was verified.
  useEffect(() => {
    if (!guarded) router.replace('/staff-access');
  }, [guarded]);

  // Branch options (same active-branch query the Blade controllers pass).
  useEffect(() => {
    if (!config.branchLabel) return;
    let cancelled = false;
    fetchHome()
      .then((home) => {
        if (!cancelled) setBranches(home.branches);
      })
      .catch(() => {
        if (!cancelled) {
          setBranches([]);
          setBanner({
            kind: 'connection',
            message: "Can't reach World Choice Perfumes. Check your internet connection and try again.",
          });
        }
      });
    return () => {
      cancelled = true;
    };
  }, [config.branchLabel]);

  if (!guarded) return null;

  const clearField = (key: keyof FieldErrors) =>
    setErrors((prev) => (prev[key] ? { ...prev, [key]: undefined } : prev));

  /** Client-side mirror of the Laravel rules (server stays authoritative). */
  const validate = (): boolean => {
    const next: FieldErrors = {};
    if (!name.trim()) next.name = 'The name field is required.';
    else if (name.trim().length > 255) next.name = 'The name may not be greater than 255 characters.';

    if (!email.trim()) next.email = 'The email field is required.';
    else if (!EMAIL_RE.test(email.trim())) next.email = 'The email field must be a valid email address.';

    if (!phone.trim()) next.phone = 'The phone field is required.';
    else if (phone.trim().length > 20) next.phone = 'The phone may not be greater than 20 characters.';

    if (config.branchLabel && !branchId) next.branch_id = 'The branch field is required.';
    if (config.secretCode && !secret.trim()) next.secret_code = 'The secret code field is required.';

    if (!password) next.password = 'The password field is required.';
    else if (password.length < 8) next.password = 'The password must be at least 8 characters.';

    if (!confirm) next.password_confirmation = 'The password confirmation field is required.';
    else if (password !== confirm) next.password_confirmation = 'The password confirmation does not match.';

    setErrors(next);
    return Object.values(next).every((v) => !v);
  };

  const pickPhoto = async () => {
    setBanner(null);
    try {
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        quality: 0.8,
      });
      if (result.canceled) return;
      const asset = result.assets[0];
      setPhoto({
        uri: asset.uri,
        name: asset.fileName ?? 'profile-picture.jpg',
        type: asset.mimeType ?? 'image/jpeg',
      });
    } catch {
      setBanner({ kind: 'error', message: 'Could not open the photo library. Please try again.' });
    }
  };

  const submit = async () => {
    setBanner(null);
    if (!validate()) return;

    setLoading(true);
    try {
      const challenge = await registerStaff(
        config.type,
        {
          name: name.trim(),
          email: email.trim(),
          phone: phone.trim(),
          password,
          password_confirmation: confirm,
          ...(config.branchLabel ? { branch_id: branchId } : {}),
          ...(config.secretCode ? { secret_code: secret.trim() } : {}),
        },
        photo ?? undefined,
      );
      // Website behaviour: registration opens the "Verify Your Email" step.
      router.push({
        pathname: '/otp',
        params: {
          email: challenge.email,
          type: challenge.type,
          user_id: String(challenge.user_id),
          message: challenge.message,
          signup: config.type,
        },
      });
    } catch (e) {
      if (isApiError(e)) {
        const fieldKeys = Object.keys(e.fields) as (keyof FieldErrors)[];
        const next: FieldErrors = {};
        for (const key of fieldKeys) next[key] = e.fields[key];
        if (fieldKeys.length) setErrors(next);
        setBanner({
          kind: e.kind === 'validation' || e.kind === 'auth' ? 'error' : 'connection',
          message: e.message,
          reverify: e.status === 403,
        });
      } else {
        setBanner({ kind: 'connection', message: errorMessage(e) });
      }
    } finally {
      setLoading(false);
    }
  };

  const selectedBranch = branches?.find((b) => String(b.id) === branchId);
  const branchDisplay = selectedBranch
    ? `${selectedBranch.name}${selectedBranch.address ? ` (${selectedBranch.address})` : ''}`
    : '-- Select Branch --';

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={24}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>
          <AuthHeader title={config.title} subtitle={config.subtitle} />

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
              label={config.nameLabel}
              value={name}
              onChangeText={(t) => {
                setName(t);
                clearField('name');
              }}
              placeholder="John Doe"
              icon="person-outline"
              autoCapitalize="words"
              error={errors.name}
              editable={!loading}
            />
            <AuthField
              label="Email Address"
              value={email}
              onChangeText={(t) => {
                setEmail(t);
                clearField('email');
              }}
              placeholder="you@example.com"
              icon="mail-outline"
              keyboardType="email-address"
              error={errors.email}
              editable={!loading}
            />
            <AuthField
              label="Phone Number"
              value={phone}
              onChangeText={(t) => {
                setPhone(t);
                clearField('phone');
              }}
              placeholder="+255 7XX XXX XXX"
              icon="call-outline"
              keyboardType="phone-pad"
              maxLength={20}
              error={errors.phone}
              editable={!loading}
            />

            {config.branchLabel ? (
              <View style={styles.field}>
                <Text style={styles.label}>{config.branchLabel}</Text>
                <Pressable
                  onPress={() => setBranchOpen((o) => !o)}
                  style={[styles.select, errors.branch_id && styles.selectError]}
                  disabled={loading}
                  accessibilityRole="button"
                >
                  <Ionicons name="storefront-outline" size={16} color={COLORS.textMuted} />
                  <Text style={[styles.selectText, !selectedBranch && styles.selectPlaceholder]} numberOfLines={1}>
                    {branches === null ? 'Loading branches…' : branchDisplay}
                  </Text>
                  <Ionicons name={branchOpen ? 'chevron-up' : 'chevron-down'} size={16} color={COLORS.textMuted} />
                </Pressable>
                {branchOpen ? (
                  <View style={styles.dropdown}>
                    <ScrollView style={styles.dropdownScroll} nestedScrollEnabled keyboardShouldPersistTaps="handled">
                      {branches === null ? (
                        <Text style={styles.dropdownHint}>Loading…</Text>
                      ) : branches.length === 0 ? (
                        <Text style={styles.dropdownHint}>
                          No branches available yet. Please contact the super admin to create a branch first.
                        </Text>
                      ) : (
                        branches.map((branch) => (
                          <Pressable
                            key={String(branch.id)}
                            onPress={() => {
                              setBranchId(String(branch.id));
                              setBranchOpen(false);
                              clearField('branch_id');
                            }}
                            style={({ pressed }) => [styles.option, pressed && styles.optionPressed]}
                          >
                            <Text style={styles.optionText} numberOfLines={2}>
                              {branch.name}
                              {branch.address ? ` (${branch.address})` : ''}
                            </Text>
                          </Pressable>
                        ))
                      )}
                    </ScrollView>
                  </View>
                ) : null}
                {errors.branch_id ? <Text style={styles.error}>{errors.branch_id}</Text> : null}
              </View>
            ) : null}

            {config.secretCode ? (
              <AuthField
                label="Company Secret Code"
                value={secret}
                onChangeText={(t) => {
                  setSecret(t);
                  clearField('secret_code');
                }}
                placeholder="Enter the company secret code"
                icon="key-outline"
                error={errors.secret_code}
                editable={!loading}
              />
            ) : null}

            <AuthField
              label="Password"
              value={password}
              onChangeText={(t) => {
                setPassword(t);
                clearField('password');
              }}
              placeholder="Min. 8 characters"
              icon="lock-closed-outline"
              secure
              error={errors.password}
              editable={!loading}
            />
            <AuthField
              label="Confirm Password"
              value={confirm}
              onChangeText={(t) => {
                setConfirm(t);
                clearField('password_confirmation');
              }}
              placeholder="Repeat your password"
              icon="lock-closed-outline"
              secure
              error={errors.password_confirmation}
              editable={!loading}
              returnKeyType="go"
              onSubmitEditing={submit}
            />

            {config.photo ? (
              <View style={styles.field}>
                <Text style={styles.label}>
                  Profile Photo <Text style={styles.optional}>(optional)</Text>
                </Text>
                <Pressable onPress={pickPhoto} style={styles.photoPicker} disabled={loading} accessibilityRole="button">
                  <Ionicons name="camera-outline" size={17} color={COLORS.gold} />
                  <Text style={styles.photoPickerText} numberOfLines={1}>
                    {photo ? 'Change photo' : 'Choose a photo'}
                  </Text>
                </Pressable>
                {photo ? (
                  <View style={styles.photoRow}>
                    <View style={styles.photoThumbWrap}>
                      <Ionicons name="image-outline" size={20} color={COLORS.gold} />
                    </View>
                    <Text style={styles.photoName} numberOfLines={1}>
                      {photo.name}
                    </Text>
                    <Pressable onPress={() => setPhoto(null)} hitSlop={8} accessibilityLabel="Remove photo">
                      <Text style={styles.photoRemove}>Remove</Text>
                    </Pressable>
                  </View>
                ) : null}
              </View>
            ) : null}

            <Pressable
              onPress={submit}
              disabled={loading}
              style={({ pressed }) => [
                styles.submit,
                { backgroundColor: config.buttonColor },
                GOLD_SHADOW,
                pressed && styles.pressed,
                loading && styles.disabled,
              ]}
              accessibilityRole="button"
            >
              {loading ? (
                <Text style={[styles.submitText, { color: config.buttonTextColor }]}>Submitting…</Text>
              ) : (
                <>
                  <Ionicons name="person-add-outline" size={17} color={config.buttonTextColor} />
                  <Text style={[styles.submitText, { color: config.buttonTextColor }]}>{config.buttonLabel}</Text>
                </>
              )}
            </Pressable>

            {config.note ? (
              <View style={styles.note}>
                <Ionicons name="information-circle-outline" size={13} color={COLORS.textMuted} />
                <Text style={styles.noteText}>{config.note}</Text>
              </View>
            ) : null}
          </View>

          {/* Back to Login — required on every signup page. */}
          <View style={styles.backBlock}>
            <Text style={styles.haveAccount}>Already have an account?</Text>
            <Pressable
              onPress={() => router.replace('/staff')}
              style={({ pressed }) => [styles.backLogin, pressed && styles.pressed]}
              accessibilityRole="button"
            >
              <Ionicons name="arrow-back" size={16} color={COLORS.gold} />
              <Text style={styles.backLoginText}>Back to Login</Text>
            </Pressable>
            <Pressable onPress={() => router.replace('/')} hitSlop={8} accessibilityRole="button">
              <Text style={styles.backHome}>Back to Home</Text>
            </Pressable>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: COLORS.bg },
  flex: { flex: 1 },
  content: {
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 44,
  },
  pressed: { opacity: 0.85 },
  disabled: { opacity: 0.65 },

  card: {
    backgroundColor: 'rgba(26, 26, 26, 0.5)',
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 18,
  },

  field: {
    gap: 6,
    marginBottom: 14,
  },
  label: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    fontWeight: '600',
  },
  optional: {
    color: COLORS.textMuted,
    fontWeight: '400',
  },
  error: {
    color: COLORS.danger,
    fontSize: 12,
  },

  select: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 14,
    height: 48,
  },
  selectError: {
    borderColor: COLORS.dangerBorder,
  },
  selectText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 14.5,
  },
  selectPlaceholder: {
    color: COLORS.textMuted,
  },
  dropdown: {
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.surface,
    overflow: 'hidden',
  },
  dropdownScroll: {
    maxHeight: 200,
  },
  option: {
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: COLORS.border,
  },
  optionPressed: {
    backgroundColor: COLORS.goldSoft,
  },
  optionText: {
    color: COLORS.textSecondary,
    fontSize: 14,
  },
  dropdownHint: {
    color: COLORS.textMuted,
    fontSize: 12.5,
    padding: 14,
    lineHeight: 18,
  },

  photoPicker: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
  },
  photoPickerText: {
    color: COLORS.gold,
    fontSize: 14,
    fontWeight: '600',
  },
  photoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 2,
  },
  photoThumbWrap: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoName: {
    flex: 1,
    color: COLORS.textSecondary,
    fontSize: 12.5,
  },
  photoRemove: {
    color: COLORS.danger,
    fontSize: 12.5,
    fontWeight: '600',
  },

  submit: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 9,
    borderRadius: RADIUS.md,
    paddingVertical: 15,
    marginTop: 6,
  },
  submitText: {
    fontSize: 15.5,
    fontWeight: '800',
  },

  note: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'center',
    gap: 6,
    marginTop: 12,
  },
  noteText: {
    flex: 1,
    color: COLORS.textMuted,
    fontSize: 12,
    textAlign: 'center',
    lineHeight: 17,
  },

  backBlock: {
    alignItems: 'center',
    gap: 10,
    marginTop: 20,
  },
  haveAccount: {
    color: COLORS.textMuted,
    fontSize: 13,
  },
  backLogin: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    width: '100%',
    maxWidth: 340,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.md,
    paddingVertical: 13,
    backgroundColor: COLORS.goldSoft,
  },
  backLoginText: {
    color: COLORS.gold,
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.4,
  },
  backHome: {
    color: COLORS.textMuted,
    fontSize: 13,
  },
});
