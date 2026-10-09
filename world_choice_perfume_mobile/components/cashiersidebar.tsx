/**
 * The Cashier module's navigation: a slide-in copy of the website's cashier
 * sidebar (`resources/views/cashier/partials/sidebar.blade.php`), in the
 * sidebar's own order:
 *
 *   Dashboard · Sales · Orders · Expenses
 *   (Head Quarters monitors only) Daily Sales — All Branches ·
 *   Cross-Branch Monitoring
 *   ── Account ── Profile · Logout
 *
 * The module used to show a bottom tab bar with a "More" hub instead, so
 * the app's navigation never matched the sidebar it is meant to mirror. The
 * tab bar is now hidden and this is the only navigation: every entry points
 * at a real screen, and the two Head Quarters entries appear only when the
 * server says the member holds the monitor tier (`is_cross_branch_monitor`),
 * the same gate the blade's `isCrossBranchMonitor()` block uses.
 *
 * The blade's Monitoring box (active branch + "Exit branch") is reproduced
 * too: while `in_cross_branch` the drawer names the monitored branch and
 * its exit clears the app's monitor choice (`cashierMonitor` — the stateless
 * twin of the website's session flag), exactly like the blade's exit route.
 * The pending-orders badge is server-rendered on the blade and, like the
 * other drawers, is not repeated here.
 */
import { Ionicons } from '@expo/vector-icons';
import { router, usePathname, type Href } from 'expo-router';
import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { Animated, Image, Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { fetchCashierScope, type CashierScope } from '../lib/cashierApi';
import { cashierMonitor } from '../lib/cashierMonitor';
import { staffSession } from '../lib/staffSession';
import { CASHIER_ACCENT, RADIUS } from '../lib/theme';
import { useAsyncData } from './adminkit';

/* ------------------------------------------------------------------ *
 * Open/close state — one instance rendered by app/cashier/_layout.tsx,
 * opened from any screen through `cashierMenu.open` (the AdminPage menu
 * button).
 * ------------------------------------------------------------------ */
let isOpen = false;
const listeners = new Set<() => void>();
const emit = () => {
  listeners.forEach((listener) => listener());
};

export const cashierMenu = {
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

export function useCashierMenu(): boolean {
  return useSyncExternalStore(cashierMenu.subscribe, cashierMenu.snapshot, cashierMenu.snapshot);
}

type NavItem = { label: string; icon: keyof typeof Ionicons.glyphMap; href: Href; end?: boolean };

/** The sidebar's main block, in the blade's order. */
const MAIN_ITEMS: NavItem[] = [
  { label: 'Dashboard', icon: 'grid-outline', href: '/cashier', end: true },
  { label: 'Sales', icon: 'receipt-outline', href: '/cashier/sales' },
  { label: 'Orders', icon: 'bag-handle-outline', href: '/cashier/orders' },
  { label: 'Expenses', icon: 'cash-outline', href: '/cashier/expenses' },
];

/** Head Quarters monitors only — the blade's isCrossBranchMonitor block. */
const HQ_ITEMS: NavItem[] = [
  { label: 'Daily Sales — All Branches', icon: 'bar-chart-outline', href: '/cashier/daily-overview' },
  { label: 'Cross-Branch Monitoring', icon: 'git-branch-outline', href: '/cashier/cross-branch' },
];

export function CashierSidebar() {
  const visible = useCashierMenu();
  const pathname = usePathname();
  const monitor = useSyncExternalStore(cashierMonitor.subscribe, cashierMonitor.get, cashierMonitor.get);
  // The same scope read the More hub used: monitor_branch travels with the
  // request so branch_name / in_cross_branch describe the watched branch.
  const { data: scope } = useAsyncData<CashierScope>(
    () => fetchCashierScope({ monitor_branch: monitor?.id }),
    [monitor?.id],
  );
  const identity = staffSession.getIdentity();
  const [confirmOut, setConfirmOut] = useState(false);
  const slide = useRef(new Animated.Value(-340)).current;

  const isMonitor = scope?.is_cross_branch_monitor ?? false;
  const inCrossBranch = scope?.in_cross_branch ?? false;

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
    cashierMenu.close();
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
          active && { backgroundColor: CASHIER_ACCENT.main, borderColor: CASHIER_ACCENT.main },
          pressed && !active && styles.itemPressed,
        ]}
        accessibilityRole="button"
        accessibilityState={{ selected: active }}
        accessibilityLabel={item.label}
      >
        <Ionicons name={item.icon} size={17} color={active ? '#231302' : '#CBD5E1'} />
        <Text style={[styles.itemLabel, active && styles.itemLabelActive]} numberOfLines={1}>
          {item.label}
        </Text>
      </Pressable>
    );
  };

  return (
    <Modal transparent visible={visible} animationType="fade" onRequestClose={cashierMenu.close} statusBarTranslucent>
      <View style={styles.overlay}>
        {/* Tap the dimmed area to close — the website closes the sidebar the same way. */}
        <Pressable
          style={StyleSheet.absoluteFill}
          onPress={cashierMenu.close}
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
                <Text style={styles.brandSub}>Cashier</Text>
              </View>
            </View>

            {/* The blade's Monitoring box — active branch + Exit branch. */}
            {inCrossBranch ? (
              <View style={styles.monitorBox}>
                <Text style={styles.monitorLabel}>Monitoring</Text>
                <Text style={styles.monitorBranch} numberOfLines={1}>
                  {scope?.branch_name ?? 'Another branch'}
                </Text>
                <Pressable
                  onPress={() => {
                    cashierMenu.close();
                    cashierMonitor.clear();
                  }}
                  style={({ pressed }) => [styles.monitorExit, pressed && styles.itemPressed]}
                  accessibilityRole="button"
                  accessibilityLabel="Exit monitored branch"
                >
                  <Ionicons name="arrow-back" size={11} color={CASHIER_ACCENT.light} />
                  <Text style={styles.monitorExitText}>Exit branch</Text>
                </Pressable>
              </View>
            ) : null}

            <View style={styles.nav}>
              {MAIN_ITEMS.map(renderItem)}
              {isMonitor ? HQ_ITEMS.map(renderItem) : null}

              <View style={styles.divider} />
              <Text style={styles.groupLabel}>Account</Text>
              {renderItem({ label: 'Profile', icon: 'person-outline', href: '/cashier/profile' })}

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
                        cashierMenu.close();
                        setConfirmOut(false);
                        cashierMonitor.clear();
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
                <Text style={styles.footerRole}>Cashier</Text>
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
    borderRightColor: 'rgba(217, 119, 6, 0.30)',
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
    borderColor: CASHIER_ACCENT.main,
  },
  brandText: { flex: 1 },
  brandTitle: { color: '#F1F5F9', fontSize: 12.5, fontWeight: '800', letterSpacing: 0.6 },
  brandSub: {
    color: CASHIER_ACCENT.light,
    fontSize: 9.5,
    letterSpacing: 2,
    textTransform: 'uppercase',
    marginTop: 3,
  },
  monitorBox: {
    marginTop: 12,
    marginHorizontal: 16,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: RADIUS.md,
    backgroundColor: 'rgba(217, 119, 6, 0.10)',
    borderWidth: 1,
    borderColor: 'rgba(217, 119, 6, 0.30)',
  },
  monitorLabel: {
    color: '#FBBF24',
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 1.6,
    textTransform: 'uppercase',
  },
  monitorBranch: { color: '#F1F5F9', fontSize: 13.5, fontWeight: '700', marginTop: 2 },
  monitorExit: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 6, alignSelf: 'flex-start' },
  monitorExitText: { color: CASHIER_ACCENT.light, fontSize: 11, fontWeight: '700' },
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
  itemLabel: { color: '#CBD5E1', fontSize: 14, fontWeight: '600', flexShrink: 1 },
  itemLabelActive: { color: '#231302', fontWeight: '800' },
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
    backgroundColor: CASHIER_ACCENT.main,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: '#231302', fontWeight: '800', fontSize: 13 },
  footerText: { flex: 1 },
  footerName: { color: '#F1F5F9', fontSize: 13.5, fontWeight: '600' },
  footerRole: {
    color: CASHIER_ACCENT.light,
    fontSize: 9.5,
    letterSpacing: 1.6,
    textTransform: 'uppercase',
    marginTop: 2,
  },
});
