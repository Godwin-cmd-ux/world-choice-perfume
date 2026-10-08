/**
 * The Customer Care module's navigation: a slide-in copy of the website's
 * customer-care sidebar (`resources/views/customer-care/partials/sidebar.blade.php`),
 * in the sidebar's own order:
 *
 *   Dashboard · Clients · Sales · Orders
 *   (Head Quarters only) News · Inquiries · Mails
 *   ── Account ── Profile · Logout
 *
 * The module used to show a bottom tab bar with a "More" hub instead, so the
 * app's navigation never matched the sidebar it is meant to mirror. The tab
 * bar is now hidden and this is the only navigation: every entry points at a
 * real screen, and the Head Quarters block disappears for branch members just
 * as the blade's `isHqCustomerCare()` block does (the server re-checks the
 * same gate on every call).
 */
import { Ionicons } from '@expo/vector-icons';
import { router, usePathname, type Href } from 'expo-router';
import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { Animated, Image, Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { fetchCcScope } from '../lib/careApi';
import { staffSession } from '../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../lib/theme';
import { useAsyncData } from './adminkit';

/* ------------------------------------------------------------------ *
 * Open/close state — one instance rendered by app/care/_layout.tsx,
 * opened from any screen through `careMenu.open` (the AdminPage menu
 * button), so no screen has to hold the drawer itself.
 * ------------------------------------------------------------------ */
let isOpen = false;
const listeners = new Set<() => void>();
const emit = () => {
  listeners.forEach((listener) => listener());
};

export const careMenu = {
  open(): void {
    isOpen = true;
    emit();
  },
  close(): void {
    isOpen = false;
    emit();
  },
  subscribe(listener: () => void): () => void {
    listeners.add(listener);
    return () => {
      listeners.delete(listener);
    };
  },
  snapshot(): boolean {
    return isOpen;
  },
};

export function useCareMenu(): boolean {
  return useSyncExternalStore(careMenu.subscribe, careMenu.snapshot, careMenu.snapshot);
}

type NavItem = { label: string; icon: keyof typeof Ionicons.glyphMap; href: Href; end?: boolean };

/** The sidebar's main block, in the blade's order. */
const MAIN_ITEMS: NavItem[] = [
  { label: 'Dashboard', icon: 'grid-outline', href: '/care', end: true },
  { label: 'Clients', icon: 'people-outline', href: '/care/customers' },
  { label: 'Sales', icon: 'cart-outline', href: '/care/sales' },
  { label: 'Orders', icon: 'clipboard-outline', href: '/care/orders' },
];

/** Head Quarters-Mikocheni only — the blade's isHqCustomerCare block. */
const HQ_ITEMS: NavItem[] = [
  { label: 'News', icon: 'newspaper-outline', href: '/care/news' },
  { label: 'Inquiries', icon: 'mail-open-outline', href: '/care/inquiries' },
  { label: 'Mails', icon: 'paper-plane-outline', href: '/care/mails' },
];

export function CareSidebar() {
  const visible = useCareMenu();
  const pathname = usePathname();
  const { data: scope } = useAsyncData(() => fetchCcScope(), []);
  const isHq = scope?.is_hq ?? false;
  const identity = staffSession.getIdentity();
  const [confirmOut, setConfirmOut] = useState(false);
  const slide = useRef(new Animated.Value(-340)).current;

  // Slide the panel in each time it opens (the Modal itself fades), and
  // drop a half-answered sign-out prompt when it closes.
  useEffect(() => {
    if (visible) {
      slide.setValue(-340);
      Animated.timing(slide, { toValue: 0, duration: 220, useNativeDriver: true }).start();
    } else {
      setConfirmOut(false);
    }
  }, [visible, slide]);

  const go = (href: Href) => {
    careMenu.close();
    if (pathname !== href) router.push(href);
  };

  const isActive = (item: NavItem) =>
    item.end ? pathname === item.href : pathname === item.href || pathname.startsWith(`${item.href}/`);

  const renderItem = (item: NavItem) => {
    const active = isActive(item);
    return (
      <Pressable
        key={item.href as string}
        onPress={() => go(item.href)}
        style={({ pressed }) => [
          styles.item,
          active && { backgroundColor: CC_ACCENT.main, borderColor: CC_ACCENT.main },
          pressed && !active && styles.itemPressed,
        ]}
        accessibilityRole="button"
        accessibilityState={{ selected: active }}
        accessibilityLabel={item.label}
      >
        <Ionicons name={item.icon} size={17} color={active ? '#06121F' : '#CBD5E1'} />
        <Text style={[styles.itemLabel, active && styles.itemLabelActive]}>{item.label}</Text>
      </Pressable>
    );
  };

  return (
    <Modal transparent visible={visible} animationType="fade" onRequestClose={careMenu.close} statusBarTranslucent>
      <View style={styles.overlay}>
        {/* Tap the dimmed area to close — the website closes the sidebar the same way. */}
        <Pressable
          style={StyleSheet.absoluteFill}
          onPress={careMenu.close}
          accessibilityLabel="Close menu"
          accessibilityRole="button"
        />

        <Animated.View style={[styles.panel, { transform: [{ translateX: slide }] }]}>
          <View style={styles.panelInner}>
            {/* Brand header — the blade's logo block. */}
            <View style={styles.brand}>
              <Image
                source={require('../assets/images/logo.jpeg')}
                style={styles.logo}
                resizeMode="contain"
                accessibilityLabel="World Choice Perfume logo"
              />
              <View style={styles.brandText}>
                <Text style={styles.brandTitle}>WORLD CHOICE PERFUMES</Text>
                <Text style={styles.brandSub}>Customer Care</Text>
              </View>
            </View>

            <View style={styles.nav}>
              {MAIN_ITEMS.map(renderItem)}

              {isHq ? (
                <View style={styles.hqGroup}>
                  <Text style={styles.groupLabel}>Head Quarters</Text>
                  {HQ_ITEMS.map(renderItem)}
                </View>
              ) : null}

              <View style={styles.divider} />
              <Text style={styles.groupLabel}>Account</Text>
              {renderItem({ label: 'Profile', icon: 'person-outline', href: '/care/profile' })}

              {confirmOut ? (
                <View style={styles.confirmBox}>
                  <Text style={styles.confirmText}>
                    Sign out? You will need the staff secret code and your password to sign back in.
                  </Text>
                  <View style={styles.confirmRow}>
                    <Pressable
                      onPress={() => setConfirmOut(false)}
                      style={({ pressed }) => [styles.confirmBtn, pressed && styles.itemPressed]}
                      accessibilityRole="button"
                      accessibilityLabel="Cancel sign out"
                    >
                      <Text style={styles.confirmCancel}>Cancel</Text>
                    </Pressable>
                    <Pressable
                      onPress={() => {
                        careMenu.close();
                        setConfirmOut(false);
                        staffSession.clear();
                        router.replace('/');
                      }}
                      style={({ pressed }) => [styles.confirmBtn, styles.confirmGo, pressed && styles.itemPressed]}
                      accessibilityRole="button"
                      accessibilityLabel="Confirm sign out"
                    >
                      <Text style={styles.confirmGoText}>Sign out</Text>
                    </Pressable>
                  </View>
                </View>
              ) : (
                <Pressable
                  onPress={() => setConfirmOut(true)}
                  style={({ pressed }) => [styles.item, styles.logout, pressed && styles.itemPressed]}
                  accessibilityRole="button"
                  accessibilityLabel="Logout"
                >
                  <Ionicons name="exit-outline" size={17} color={COLORS.danger} />
                  <Text style={[styles.itemLabel, styles.logoutLabel]}>Logout</Text>
                </Pressable>
              )}
            </View>

            {/* Footer — the blade's signed-in member block. */}
            <View style={styles.footer}>
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>{initialsOf(identity?.name)}</Text>
              </View>
              <View style={styles.footerText}>
                <Text style={styles.footerName} numberOfLines={1}>
                  {identity?.name ?? 'Staff member'}
                </Text>
                <Text style={styles.footerRole}>Customer Care</Text>
              </View>
            </View>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
}

function initialsOf(name?: string | null): string {
  const parts = (name ?? '').trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  return parts.slice(0, 2).map((part) => part[0]!.toUpperCase()).join('');
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    flexDirection: 'row',
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
  },
  panel: {
    width: 300,
    maxWidth: '86%',
    height: '100%',
    backgroundColor: '#0B1220',
    borderRightWidth: 1,
    borderRightColor: 'rgba(56, 189, 248, 0.25)',
  },
  panelInner: { flex: 1, paddingTop: 54 },
  brand: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(148, 163, 184, 0.18)',
  },
  logo: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: '#0F172A',
    borderWidth: 1,
    borderColor: CC_ACCENT.main,
  },
  brandText: { flex: 1 },
  brandTitle: { color: '#F1F5F9', fontSize: 12.5, fontWeight: '800', letterSpacing: 0.6 },
  brandSub: {
    color: CC_ACCENT.main,
    fontSize: 9.5,
    letterSpacing: 2,
    textTransform: 'uppercase',
    marginTop: 3,
  },
  nav: { flex: 1, paddingHorizontal: 12, paddingTop: 14, gap: 4 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 12,
    paddingVertical: 11,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: 'transparent',
  },
  itemPressed: { backgroundColor: 'rgba(148, 163, 184, 0.12)' },
  itemLabel: { color: '#CBD5E1', fontSize: 14, fontWeight: '600' },
  itemLabelActive: { color: '#06121F', fontWeight: '800' },
  hqGroup: { marginTop: 14, gap: 4 },
  groupLabel: {
    color: '#64748B',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 1.6,
    textTransform: 'uppercase',
    marginTop: 6,
    marginBottom: 2,
    paddingHorizontal: 12,
  },
  divider: {
    height: 1,
    backgroundColor: 'rgba(148, 163, 184, 0.18)',
    marginTop: 14,
    marginBottom: 4,
  },
  logout: { backgroundColor: 'rgba(248, 113, 113, 0.08)', borderColor: 'rgba(248, 113, 113, 0.28)' },
  logoutLabel: { color: COLORS.danger },
  confirmBox: {
    backgroundColor: 'rgba(248, 113, 113, 0.08)',
    borderWidth: 1,
    borderColor: 'rgba(248, 113, 113, 0.28)',
    borderRadius: RADIUS.md,
    padding: 10,
    gap: 8,
  },
  confirmText: { color: '#CBD5E1', fontSize: 11.5, lineHeight: 16 },
  confirmRow: { flexDirection: 'row', gap: 8 },
  confirmBtn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: 'rgba(148, 163, 184, 0.30)',
  },
  confirmCancel: { color: '#CBD5E1', fontSize: 12, fontWeight: '700' },
  confirmGo: { backgroundColor: 'rgba(248, 113, 113, 0.18)', borderColor: 'rgba(248, 113, 113, 0.50)' },
  confirmGoText: { color: COLORS.danger, fontSize: 12, fontWeight: '800' },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderTopWidth: 1,
    borderTopColor: 'rgba(148, 163, 184, 0.18)',
  },
  avatar: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: CC_ACCENT.main,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: '#06121F', fontWeight: '800', fontSize: 13 },
  footerText: { flex: 1 },
  footerName: { color: '#F1F5F9', fontSize: 13.5, fontWeight: '600' },
  footerRole: {
    color: CC_ACCENT.main,
    fontSize: 9.5,
    letterSpacing: 1.6,
    textTransform: 'uppercase',
    marginTop: 2,
  },
});
