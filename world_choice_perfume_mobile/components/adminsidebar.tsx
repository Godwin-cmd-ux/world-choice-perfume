/**
 * The Super Admin module's navigation: a slide-in copy of the website's
 * super-admin sidebar (`resources/views/super-admin/partials/sidebar.blade.php`),
 * in the sidebar's own order:
 *
 *   Dashboard · Daily Sales Overview · Branches · Cross-Branch Stock ·
 *   Cross-Branch Sales · Approvals · Orders · Emails · Returned Stock ·
 *   Notifications · Reports · Staff · Settings
 *   ── Account ── Profile · Logout
 *
 * The module used to show a bottom tab bar with a "More" launcher instead,
 * so the app's navigation never matched the sidebar it is meant to mirror.
 * The tab bar is now hidden and this is the only navigation: every entry
 * points at a real screen. The super-admin sidebar has no branch gates (the
 * role is company-wide), so all 13 sections always render; the blade's
 * pending-count badges (approvals / orders / notifications) are
 * server-rendered there and, like the care and stock-manager drawers, this
 * one skips them.
 */
import { Ionicons } from '@expo/vector-icons';
import { router, usePathname, type Href } from 'expo-router';
import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { Animated, Image, Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { staffSession } from '../lib/staffSession';
import { COLORS, RADIUS } from '../lib/theme';

/* ------------------------------------------------------------------ *
 * Open/close state — one instance rendered by app/admin/_layout.tsx,
 * opened from any screen through `adminMenu.open` (the AdminPage menu
 * button or the AdminMenuButton on the custom-header tabs).
 * ------------------------------------------------------------------ */
let isOpen = false;
const listeners = new Set<() => void>();
const emit = () => {
  listeners.forEach((listener) => listener());
};

export const adminMenu = {
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

export function useAdminMenu(): boolean {
  return useSyncExternalStore(adminMenu.subscribe, adminMenu.snapshot, adminMenu.snapshot);
}

/** Header hamburger for screens that do not use the AdminPage shell. */
export function AdminMenuButton() {
  return (
    <Pressable
      onPress={adminMenu.open}
      style={({ pressed }) => [styles.plainBtn, pressed && styles.itemPressed]}
      hitSlop={10}
      accessibilityRole="button"
      accessibilityLabel="Open navigation menu"
    >
      <Ionicons name="menu-outline" size={22} color={COLORS.goldFill} />
    </Pressable>
  );
}

type NavItem = { label: string; icon: keyof typeof Ionicons.glyphMap; href: Href; end?: boolean };

/** The sidebar's flat list, in the blade's order. */
const ITEMS: NavItem[] = [
  { label: 'Dashboard', icon: 'grid-outline', href: '/admin', end: true },
  { label: 'Daily Sales Overview', icon: 'stats-chart-outline', href: '/admin/daily-sales' },
  { label: 'Branches', icon: 'storefront-outline', href: '/admin/branches' },
  { label: 'Cross-Branch Stock', icon: 'business-outline', href: '/admin/cross-stock' },
  { label: 'Cross-Branch Sales', icon: 'card-outline', href: '/admin/cross-sales' },
  { label: 'Approvals', icon: 'checkmark-done-outline', href: '/admin/approvals' },
  { label: 'Orders', icon: 'bag-handle-outline', href: '/admin/orders' },
  { label: 'Emails', icon: 'mail-outline', href: '/admin/emails' },
  { label: 'Returned Stock', icon: 'archive-outline', href: '/admin/returned-stock' },
  { label: 'Notifications', icon: 'notifications-outline', href: '/admin/notifications' },
  { label: 'Reports', icon: 'bar-chart-outline', href: '/admin/reports' },
  { label: 'Staff', icon: 'people-outline', href: '/admin/staff' },
  { label: 'Settings', icon: 'settings-outline', href: '/admin/settings' },
];

export function AdminSidebar() {
  const visible = useAdminMenu();
  const pathname = usePathname();
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
    adminMenu.close();
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
          active && { backgroundColor: COLORS.goldFill, borderColor: COLORS.goldFill },
          pressed && !active && styles.itemPressed,
        ]}
        accessibilityRole="button"
        accessibilityState={{ selected: active }}
        accessibilityLabel={item.label}
      >
        <Ionicons name={item.icon} size={17} color={active ? '#1A1305' : '#CBD5E1'} />
        <Text style={[styles.itemLabel, active && styles.itemLabelActive]} numberOfLines={1}>
          {item.label}
        </Text>
      </Pressable>
    );
  };

  return (
    <Modal transparent visible={visible} animationType="fade" onRequestClose={adminMenu.close} statusBarTranslucent>
      <View style={styles.overlay}>
        {/* Tap the dimmed area to close — the website closes the sidebar the same way. */}
        <Pressable
          style={StyleSheet.absoluteFill}
          onPress={adminMenu.close}
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
                <Text style={styles.brandSub}>Super Admin</Text>
              </View>
            </View>

            <View style={styles.nav}>
              {ITEMS.map(renderItem)}

              <View style={styles.divider} />
              <Text style={styles.groupLabel}>Account</Text>
              {renderItem({ label: 'Profile', icon: 'person-outline', href: '/admin/profile' })}

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
                        adminMenu.close();
                        setConfirmOut(false);
                        staffSession.clear();
                        router.replace('/staff');
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
                  <Ionicons name="exit-outline" size={17} color={'#EF4444'} />
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
                <Text style={styles.footerRole}>Super Admin</Text>
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
    borderRightColor: 'rgba(200, 160, 42, 0.30)',
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
    borderColor: COLORS.goldFill,
  },
  brandText: { flex: 1 },
  brandTitle: { color: '#F1F5F9', fontSize: 12.5, fontWeight: '800', letterSpacing: 0.6 },
  brandSub: {
    color: COLORS.goldFill,
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
  plainBtn: { paddingVertical: 2, alignSelf: 'flex-start' },
  itemLabel: { color: '#CBD5E1', fontSize: 14, fontWeight: '600', flexShrink: 1 },
  itemLabelActive: { color: '#1A1305', fontWeight: '800' },
  divider: {
    height: 1,
    backgroundColor: 'rgba(148, 163, 184, 0.18)',
    marginTop: 14,
    marginBottom: 4,
  },
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
  logout: { backgroundColor: 'rgba(248, 113, 113, 0.08)', borderColor: 'rgba(248, 113, 113, 0.28)' },
  logoutLabel: { color: '#EF4444' },
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
  confirmGoText: { color: '#EF4444', fontSize: 12, fontWeight: '800' },
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
    backgroundColor: COLORS.goldFill,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: '#1A1305', fontWeight: '800', fontSize: 13 },
  footerText: { flex: 1 },
  footerName: { color: '#F1F5F9', fontSize: 13.5, fontWeight: '600' },
  footerRole: {
    color: COLORS.goldFill,
    fontSize: 9.5,
    letterSpacing: 1.6,
    textTransform: 'uppercase',
    marginTop: 2,
  },
});
