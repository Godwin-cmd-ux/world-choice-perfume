import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { COLORS, RADIUS } from '../lib/theme';
import { isApiError } from '../lib/api';
import { staffSession } from '../lib/staffSession';

/**
 * Shared building blocks for the Super Admin module. Together with
 * components/ui.tsx (LoadingView / ErrorView / EmptyView / buttons /
 * Card) and authkit's Banner, these give all 20+ admin screens one visual
 * language: dark-800 cards, gold accents, 10%-alpha status colours — the
 * same palette the website's super-admin Blade views use.
 */

/** Page shell: brand eyebrow + title + optional back/action, scrollable body.
 * `accent` recolours the eyebrow, back chevron and refresh spinner — the
 * Graphic Designer module passes purple; every other screen keeps the gold
 * default. */
export function AdminPage({
  title,
  eyebrow = 'Super Admin',
  accent,
  onBack,
  onMenu,
  action,
  refreshing,
  onRefresh,
  children,
}: {
  title: string;
  eyebrow?: string;
  accent?: string;
  onBack?: () => void;
  /** Opens the module's sidebar navigation (hamburger, left of the title). */
  onMenu?: () => void;
  action?: ReactNode;
  refreshing?: boolean;
  onRefresh?: () => void;
  children: ReactNode;
}) {
  const accentColor = accent ?? COLORS.goldDark;
  return (
    <View style={styles.page}>
      <View style={styles.topBar}>
        {onBack ? (
          <Pressable onPress={onBack} style={({ pressed }) => [styles.backBtn, pressed && styles.pressed]} hitSlop={10} accessibilityRole="button" accessibilityLabel="Go back">
            <Ionicons name="chevron-back" size={22} color={accent ?? COLORS.gold} />
          </Pressable>
        ) : null}
        {onMenu ? (
          <Pressable onPress={onMenu} style={({ pressed }) => [styles.backBtn, pressed && styles.pressed]} hitSlop={10} accessibilityRole="button" accessibilityLabel="Open navigation menu">
            <Ionicons name="menu-outline" size={22} color={accent ?? COLORS.gold} />
          </Pressable>
        ) : null}
        <View style={styles.titleWrap}>
          <Text style={[styles.eyebrow, { color: accentColor }]}>{eyebrow}</Text>
          <Text style={styles.title} numberOfLines={1}>
            {title}
          </Text>
        </View>
        {action}
      </View>
      <ScrollView
        style={styles.body}
        contentContainerStyle={styles.bodyContent}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
        refreshControl={
          onRefresh ? <RefreshControl refreshing={refreshing ?? false} onRefresh={onRefresh} tintColor={accent ?? COLORS.gold} /> : undefined
        }
      >
        {children}
      </ScrollView>
    </View>
  );
}

/** Dashboard statistic tile (website stat card, phone-sized). */
export function StatTile({ label, value, hint, tone = 'gold' }: { label: string; value: string | number; hint?: string; tone?: 'gold' | 'danger' | 'success' | 'info' | 'warning' }) {
  const color = tone === 'danger' ? COLORS.danger : tone === 'success' ? COLORS.success : tone === 'info' ? COLORS.info : tone === 'warning' ? COLORS.warning : COLORS.gold;
  return (
    <View style={styles.stat}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={[styles.statValue, { color }]} numberOfLines={1} adjustsFontSizeToFit>
        {value}
      </Text>
      {hint ? <Text style={styles.statHint}>{hint}</Text> : null}
    </View>
  );
}

/** Two-column grid of stat tiles. */
export function StatGrid({ children }: { children: ReactNode }) {
  return <View style={styles.statGrid}>{children}</View>;
}

/** Horizontal filter chip. */
export function Chip({ label, active = false, onPress, badge }: { label: string; active?: boolean; onPress?: () => void; badge?: number }) {
  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [styles.chip, active && styles.chipActive, pressed && styles.pressed]}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
    >
      <Text style={[styles.chipText, active && styles.chipTextActive]}>{label}</Text>
      {badge !== undefined && badge > 0 ? (
        <View style={styles.chipBadge}>
          <Text style={styles.chipBadgeText}>{badge > 99 ? '99+' : badge}</Text>
        </View>
      ) : null}
    </Pressable>
  );
}

/** Horizontally scrolling row of chips (status/role/date filters). */
export function ChipRow({ children }: { children: ReactNode }) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
      {children}
    </ScrollView>
  );
}

/** Search input with leading icon and clear button. */
export function SearchInput({ value, onChangeText, placeholder = 'Search…' }: { value: string; onChangeText: (t: string) => void; placeholder?: string }) {
  return (
    <View style={styles.search}>
      <Ionicons name="search-outline" size={17} color={COLORS.textMuted} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={COLORS.textMuted}
        style={styles.searchInput}
        autoCapitalize="none"
        returnKeyType="search"
      />
      {value ? (
        <Pressable onPress={() => onChangeText('')} hitSlop={8} accessibilityLabel="Clear search">
          <Ionicons name="close-circle" size={17} color={COLORS.textMuted} />
        </Pressable>
      ) : null}
    </View>
  );
}

/** A list record rendered as a tappable card (tables → cards on mobile). */
export function DataCard({
  title,
  subtitle,
  lines = [],
  badge,
  badgeTone = 'gold',
  right,
  onPress,
  children,
}: {
  title: string;
  subtitle?: string;
  lines?: (string | null | undefined)[];
  badge?: string;
  badgeTone?: 'gold' | 'danger' | 'success' | 'warning' | 'muted';
  right?: ReactNode;
  onPress?: () => void;
  children?: ReactNode;
}) {
  const toneColor =
    badgeTone === 'danger'
      ? COLORS.danger
      : badgeTone === 'success'
        ? COLORS.success
        : badgeTone === 'warning'
          ? COLORS.warning
          : badgeTone === 'muted'
            ? COLORS.textSecondary
            : COLORS.gold;

  return (
    <Pressable
      onPress={onPress}
      disabled={!onPress}
      style={({ pressed }) => [styles.card, onPress && pressed && styles.pressed]}
      accessibilityRole={onPress ? 'button' : undefined}
    >
      <View style={styles.cardHead}>
        <View style={styles.cardTitleWrap}>
          <Text style={styles.cardTitle} numberOfLines={1}>
            {title}
          </Text>
          {subtitle ? (
            <Text style={styles.cardSubtitle} numberOfLines={1}>
              {subtitle}
            </Text>
          ) : null}
        </View>
        {badge ? (
          <View style={[styles.badge, { backgroundColor: 'rgba(0,0,0,0)', borderColor: toneColor }]}>
            <Text style={[styles.badgeText, { color: toneColor }]}>{badge}</Text>
          </View>
        ) : null}
        {right ?? (onPress ? <Ionicons name="chevron-forward" size={17} color={COLORS.textMuted} /> : null)}
      </View>
      {lines.filter(Boolean).length > 0 ? (
        <View style={styles.cardLines}>
          {lines.filter(Boolean).map((line, i) => (
            <Text key={i} style={styles.cardLine} numberOfLines={2}>
              {line}
            </Text>
          ))}
        </View>
      ) : null}
      {children}
    </Pressable>
  );
}

/** Label + value row inside a card. */
export function KV({ label, value, tone }: { label: string; value?: string | number | null; tone?: 'gold' | 'danger' | 'success' | 'warning' }) {
  if (value === null || value === undefined || value === '') return null;
  const color = tone === 'danger' ? COLORS.danger : tone === 'success' ? COLORS.success : tone === 'warning' ? COLORS.warning : COLORS.text;
  return (
    <View style={styles.kv}>
      <Text style={styles.kvLabel}>{label}</Text>
      <Text style={[styles.kvValue, { color }]} numberOfLines={3}>
        {String(value)}
      </Text>
    </View>
  );
}

/** Full-width section label used between groups of cards. */
export function GroupLabel({ children, right }: { children: ReactNode; right?: ReactNode }) {
  return (
    <View style={styles.groupLabelRow}>
      <Text style={styles.groupLabel}>{children}</Text>
      {right}
    </View>
  );
}

/** Blocking overlay spinner for in-flight mutations. */
export function BusyOverlay({ visible, label = 'Working…' }: { visible: boolean; label?: string }) {
  if (!visible) return null;
  return (
    <View style={styles.busy}>
      <View style={styles.busyBox}>
        <ActivityIndicator size="large" color={COLORS.gold} />
        <Text style={styles.busyText}>{label}</Text>
      </View>
    </View>
  );
}

/**
 * Confirmation dialog for destructive/high-impact actions (delete, reject,
 * block, purge…). Never let a single careless tap perform them.
 */
export function ConfirmDialog({
  visible,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  danger = false,
  loading = false,
  onConfirm,
  onCancel,
}: {
  visible: boolean;
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  danger?: boolean;
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}) {
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onCancel}>
      <View style={styles.overlay}>
        <View style={styles.dialog}>
          <Text style={styles.dialogTitle}>{title}</Text>
          <Text style={styles.dialogMessage}>{message}</Text>
          <View style={styles.dialogActions}>
            <Pressable
              onPress={onCancel}
              disabled={loading}
              style={({ pressed }) => [styles.dialogBtn, styles.dialogCancel, pressed && styles.pressed]}
              accessibilityRole="button"
            >
              <Text style={styles.dialogCancelText}>{cancelLabel}</Text>
            </Pressable>
            <Pressable
              onPress={onConfirm}
              disabled={loading}
              style={({ pressed }) => [
                styles.dialogBtn,
                danger ? styles.dialogDanger : styles.dialogConfirm,
                pressed && styles.pressed,
                loading && { opacity: 0.6 },
              ]}
              accessibilityRole="button"
            >
              {loading ? (
                <ActivityIndicator size="small" color={danger ? '#FFF' : '#1A1400'} />
              ) : (
                <Text style={[styles.dialogConfirmText, danger && { color: '#FFF' }]}>{confirmLabel}</Text>
              )}
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

/**
 * Load/refresh helper shared by every admin screen: one state machine for
 * loading, error, (re)load and pull-to-refresh. Session problems (401/403)
 * are reported through `sessionExpired` so screens can kick back to /staff.
 */
export function useAsyncData<T>(loader: () => Promise<T>, deps: unknown[] = []) {
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [sessionExpired, setSessionExpired] = useState(false);
  const alive = useRef(true);

  useEffect(() => {
    alive.current = true;
    return () => {
      alive.current = false;
    };
  }, []);

  const run = useCallback(
    async (mode: 'initial' | 'refresh' | 'reload') => {
      if (mode === 'refresh') setRefreshing(true);
      else if (mode === 'reload') setError(null);
      try {
        const result = await loader();
        if (!alive.current) return;
        setData(result);
        setError(null);
        setSessionExpired(false);
      } catch (e) {
        if (!alive.current) return;
        if (isSessionError(e)) {
          // The server rejected the session/role — drop the stale token and
          // go back to the staff login, exactly like a website redirect.
          setSessionExpired(true);
          staffSession.clear();
          setTimeout(() => {
            router.replace('/staff');
          }, 800);
        }
        setError(messageOf(e));
      } finally {
        if (alive.current) {
          setLoading(false);
          setRefreshing(false);
        }
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    deps,
  );

  useEffect(() => {
    setLoading(true);
    run('initial');
  }, [run]);

  return { data, error, loading, refreshing, sessionExpired, reload: () => run('reload'), refresh: () => run('refresh') };
}

/** True when the server rejected the session/role (401/403) — kick to login. */
export function isSessionError(e: unknown): boolean {
  return isApiError(e) && (e.status === 401 || e.status === 403);
}

/** Safe user-facing message for any thrown error (never leaks internals). */
export function messageOf(e: unknown): string {
  if (isApiError(e)) return e.message;
  return 'Something went wrong. Please try again.';
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: COLORS.bg },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 10,
    gap: 8,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
    backgroundColor: COLORS.bgRaised,
  },
  backBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  titleWrap: { flex: 1 },
  eyebrow: {
    color: COLORS.goldDark,
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 2.5,
    textTransform: 'uppercase',
  },
  title: { color: COLORS.text, fontSize: 20, fontWeight: '800', marginTop: 2 },
  body: { flex: 1 },
  bodyContent: { padding: 16, paddingBottom: 40, gap: 12 },
  pressed: { opacity: 0.75 },

  statGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  stat: {
    flexGrow: 1,
    flexBasis: '30%',
    minWidth: 140,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 14,
  },
  statLabel: { color: COLORS.textSecondary, fontSize: 11.5, fontWeight: '600', textTransform: 'uppercase', letterSpacing: 0.8 },
  statValue: { fontSize: 24, fontWeight: '800', marginTop: 6 },
  statHint: { color: COLORS.textMuted, fontSize: 11, marginTop: 4 },

  chipRow: { gap: 8, paddingVertical: 2 },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 13,
    paddingVertical: 8,
    borderRadius: RADIUS.pill,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  chipActive: { backgroundColor: COLORS.goldSoft, borderColor: COLORS.gold },
  chipText: { color: COLORS.textSecondary, fontSize: 13, fontWeight: '600' },
  chipTextActive: { color: COLORS.goldLight },
  chipBadge: {
    minWidth: 18,
    paddingHorizontal: 4,
    borderRadius: 9,
    backgroundColor: COLORS.danger,
    alignItems: 'center',
  },
  chipBadgeText: { color: '#FFF', fontSize: 10, fontWeight: '800' },

  search: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 9,
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 13,
    height: 46,
  },
  searchInput: { flex: 1, color: COLORS.text, fontSize: 15, paddingVertical: 0 },

  card: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 14,
    gap: 10,
  },
  cardHead: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  cardTitleWrap: { flex: 1 },
  cardTitle: { color: COLORS.text, fontSize: 15.5, fontWeight: '700' },
  cardSubtitle: { color: COLORS.gold, fontSize: 12.5, marginTop: 3 },
  badge: { borderWidth: 1, borderRadius: RADIUS.pill, paddingHorizontal: 9, paddingVertical: 3 },
  badgeText: { fontSize: 11, fontWeight: '800', letterSpacing: 0.4, textTransform: 'uppercase' },
  cardLines: { gap: 4 },
  cardLine: { color: COLORS.textSecondary, fontSize: 13, lineHeight: 18 },

  kv: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  kvLabel: { color: COLORS.textMuted, fontSize: 13, fontWeight: '600' },
  kvValue: { color: COLORS.text, fontSize: 13, flexShrink: 1, minWidth: 60 },

  groupLabelRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 6 },
  groupLabel: {
    color: COLORS.textSecondary,
    fontSize: 11.5,
    fontWeight: '700',
    letterSpacing: 1.6,
    textTransform: 'uppercase',
  },

  busy: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(5,5,5,0.72)',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 50,
  },
  busyBox: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    paddingVertical: 26,
    paddingHorizontal: 34,
    alignItems: 'center',
    gap: 14,
  },
  busyText: { color: COLORS.textSecondary, fontSize: 13.5, fontWeight: '600' },

  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.72)',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 26,
  },
  dialog: {
    width: '100%',
    maxWidth: 420,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 20,
    gap: 10,
  },
  dialogTitle: { color: COLORS.text, fontSize: 17, fontWeight: '800' },
  dialogMessage: { color: COLORS.textSecondary, fontSize: 14, lineHeight: 21 },
  dialogActions: { flexDirection: 'row', gap: 10, marginTop: 10 },
  dialogBtn: {
    flex: 1,
    minHeight: 46,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 12,
  },
  dialogCancel: { backgroundColor: COLORS.surfaceHigh, borderWidth: 1, borderColor: COLORS.border },
  dialogCancelText: { color: COLORS.textSecondary, fontSize: 14.5, fontWeight: '700' },
  dialogConfirm: { backgroundColor: COLORS.gold },
  dialogDanger: { backgroundColor: '#B91C1C' },
  dialogConfirmText: { color: '#1A1400', fontSize: 14.5, fontWeight: '800' },
});
