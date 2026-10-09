import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import {
  Image,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type KeyboardTypeOptions,
  type TextInputProps,
} from 'react-native';
import { COLORS, RADIUS } from '../lib/theme';

/**
 * Shared building blocks for the staff auth screens (login, the six signup
 * pages and OTP). Styling mirrors the Blade auth views: logo + display title
 * on top, `dark-700` inputs with a leading icon inside `dark-600` borders,
 * red/green banners at 10% alpha — all sized for phone screens.
 */

export function AuthHeader({ title, subtitle }: { title: string; subtitle: string }) {
  return (
    <View style={styles.header}>
      <View style={styles.logoRing}>
        <Image source={require('../assets/images/logo.jpeg')} style={styles.logo} resizeMode="contain" />
      </View>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.subtitle}>{subtitle}</Text>
    </View>
  );
}

export function AuthField({
  label,
  value,
  onChangeText,
  placeholder,
  icon,
  secure = false,
  keyboardType,
  autoCapitalize = 'none',
  autoComplete,
  editable = true,
  error,
  maxLength,
  returnKeyType,
  onSubmitEditing,
}: {
  label: string;
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
  icon: keyof typeof Ionicons.glyphMap;
  secure?: boolean;
  keyboardType?: KeyboardTypeOptions;
  autoCapitalize?: TextInputProps['autoCapitalize'];
  autoComplete?: TextInputProps['autoComplete'];
  editable?: boolean;
  error?: string;
  maxLength?: number;
  returnKeyType?: TextInputProps['returnKeyType'];
  onSubmitEditing?: () => void;
}) {
  const [visible, setVisible] = useState(false);

  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <View style={[styles.inputWrap, error ? styles.inputWrapError : null, !editable && styles.inputDisabled]}>
        <Ionicons name={icon} size={17} color={COLORS.textMuted} />
        <TextInput
          value={value}
          onChangeText={onChangeText}
          placeholder={placeholder}
          placeholderTextColor={COLORS.textMuted}
          style={styles.input}
          secureTextEntry={secure && !visible}
          keyboardType={keyboardType}
          autoCapitalize={autoCapitalize}
          autoComplete={autoComplete}
          editable={editable}
          maxLength={maxLength}
          returnKeyType={returnKeyType}
          onSubmitEditing={onSubmitEditing}
        />
        {secure ? (
          <Pressable onPress={() => setVisible((v) => !v)} hitSlop={8} accessibilityLabel={visible ? 'Hide password' : 'Show password'}>
            <Ionicons name={visible ? 'eye-off-outline' : 'eye-outline'} size={17} color={COLORS.textMuted} />
          </Pressable>
        ) : null}
      </View>
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

export function Banner({
  kind,
  message,
  actionLabel,
  onAction,
}: {
  kind: 'error' | 'success' | 'connection';
  message: string;
  actionLabel?: string;
  onAction?: () => void;
}) {
  const palette =
    kind === 'success'
      ? { fg: COLORS.success, bg: COLORS.successBg, border: COLORS.successBorder, icon: 'checkmark-circle-outline' as const }
      : kind === 'connection'
        ? { fg: COLORS.warning, bg: 'rgba(252, 211, 77, 0.08)', border: 'rgba(252, 211, 77, 0.30)', icon: 'cloud-offline-outline' as const }
        : { fg: COLORS.danger, bg: COLORS.dangerBg, border: COLORS.dangerBorder, icon: 'alert-circle-outline' as const };

  return (
    <View style={[styles.banner, { backgroundColor: palette.bg, borderColor: palette.border }]}>
      <Ionicons name={palette.icon} size={16} color={palette.fg} />
      <View style={styles.bannerBody}>
        <Text style={[styles.bannerText, { color: palette.fg }]}>{message}</Text>
        {actionLabel && onAction ? (
          <Pressable onPress={onAction} hitSlop={6} accessibilityRole="button">
            <Text style={[styles.bannerAction, { color: palette.fg }]}>{actionLabel}</Text>
          </Pressable>
        ) : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    alignItems: 'center',
    marginBottom: 22,
  },
  logoRing: {
    width: 68,
    height: 68,
    borderRadius: 34,
    borderWidth: 1.5,
    borderColor: COLORS.goldBorder,
    backgroundColor: COLORS.surface,
    padding: 3,
    marginBottom: 14,
  },
  logo: {
    width: '100%',
    height: '100%',
    borderRadius: 31,
  },
  title: {
    color: COLORS.text,
    fontSize: 27,
    fontWeight: '800',
    textAlign: 'center',
  },
  subtitle: {
    color: COLORS.textSecondary,
    fontSize: 14,
    textAlign: 'center',
    marginTop: 6,
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
  inputWrap: {
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
  inputWrapError: {
    borderColor: COLORS.dangerBorder,
  },
  inputDisabled: {
    opacity: 0.6,
  },
  input: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15,
    paddingVertical: 0,
  },
  error: {
    color: COLORS.danger,
    fontSize: 12,
  },

  banner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 9,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    padding: 12,
    marginBottom: 14,
  },
  bannerBody: {
    flex: 1,
    gap: 6,
  },
  bannerText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
  },
  bannerAction: {
    fontSize: 13,
    fontWeight: '700',
    textDecorationLine: 'underline',
  },
});
