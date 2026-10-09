/**
 * The Stock Manager module's navigation: a slide-in copy of the website's
 * stock-manager sidebar (`resources/views/stock-manager/partials/sidebar.blade.php`),
 * in the sidebar's own order:
 *
 *   Dashboard
 *   ── Stock Management ── Sales · Orders · Products · Product Stock
 *      (Kinondoni monitor only) Cross-Branch Stock
 *      (autonomous branches) Bottle Stock · Oil Fragrance · Bottle Accessories
 *   ── Transfers ── Stock Transfers · Pending Incoming Stock
 *      (Kinondoni only) Returned Stock · Returned Items
 *   ── Account ── Profile · Logout
 *
 * The module used to show a bottom tab bar with a "More" hub instead, so the
 * app's navigation never matched the sidebar it is meant to mirror. The tab
 * bar is now hidden and this is the only navigation: every entry points at a
 * real screen, and the three blade gates — products-only branches, the
 * Kinondoni monitor privilege (Cross-Branch + Returned Stock) and the
 * cross-branch Monitoring banner — are read from the same scope payload the
 * server re-checks on every /api/sm call.
 *
 * The blade's pending-count badges (orders / incoming / damage reports) are
 * server-rendered there; the drawer skips them like the care sidebar does.
 * The Monitoring box shows the active branch when the server reports
 * cross-branch mode; its "Exit branch" action has no mobile counterpart
 * (the app is a read-only, stateless twin of that mode), so it is omitted.
 */
import { Ionicons } from '@expo/vector-icons';
import { router, usePathname, type Href } from 'expo-router';
import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { Animated, Image, Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { fetchSmScope, type SmScope } from '../lib/smApi';
import { staffSession } from '../lib/staffSession';
import { SM_ACCENT, RADIUS } from '../lib/theme';
import { useAsyncData } from './adminkit';

/* ------------------------------------------------------------------ *
 * Open/close state — one instance rendered by app/sm/_layout.tsx,
 * opened from any screen through `smMenu.open` (the AdminPage menu
 * button or the SmMenuButton on the custom-header tabs).
 * ------------------------------------------------------------------ */
let isOpen = false;
const listeners = new Set<() => void>();
const emit = () => {
  listeners.forEach((listener) => listener());
};

export const smMenu = {
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

export function useSmMenu(): boolean {
  return useSyncExternalStore(smMenu.subscribe, smMenu.snapshot, smMenu.snapshot);
}

/** Header hamburger for screens that do not use the AdminPage shell. */
export function SmMenuButton() {
  return (
    <Pressable
      onPress={smMenu.open}
      style={({ pressed }) => [styles.plainBtn, pressed && styles.itemPressed]}
      hitSlop={10}
      accessibilityRole="button"
      accessibilityLabel="Open navigation menu"
    >
      <Ionicons name="menu-outline" size={22} color={SM_ACCENT.light} />
    </Pressable>
  );
}

type NavItem = { label: string; icon: keyof typeof Ionicons.glyphMap; href: Href; end?: boolean };

/** Dashboard — the sidebar's first entry. */
const DASHBOARD: NavItem = { label: 'Dashboard', icon: 'grid-outline', href: '/sm', end: true };

/** The blade's "Stock Management" group, minus its gated entries. */
const STOCK_ITEMS: NavItem[] = [
  { label: 'Sales', icon: 'receipt-outline', href: '/sm/sales' },
  { label: 'Orders', icon: 'clipboard-outline', href: '/sm/orders' },
  { label: 'Products', icon: 'pricetags-outline', href: '/sm/products' },
  { label: 'Product Stock', icon: 'cube-outline', href: '/sm/stock' },
];

const CROSS_BRANCH_ITEM: NavItem = { label: 'Cross-Branch Stock', icon: 'git-branch-outline', href: '/sm/cross-branch' };

/** Autonomous branches only — the blade's @unless(productsOnly) block. */
const BOTTLE_ITEMS: NavItem[] = [
  { label: 'Bottle Stock', icon: 'wine-outline', href: '/sm/bottle-stock' },
  { label: 'Oil Fragrance', icon: 'flask-outline', href: '/sm/oil-fragrance' },
  { label: 'Bottle Accessories', icon: 'construct-outline', href: '/sm/accessories' },
];

/** The blade's "Transfers" group. */
const TRANSFER_ITEMS: NavItem[] = [
  { label: 'Stock Transfers', icon: 'swap-horizontal-outline', href: '/sm/transfers' },
  { label: 'Pending Incoming Stock', icon: 'download-outline', href: '/sm/incoming' },
];

const RETURNED_STOCK_ITEM: NavItem = { label: 'Returned Stock', icon: 'shield-checkmark-outline', href: '/sm/returned-stock' };
const RETURNED_ITEMS_ITEM: NavItem = { label: 'Returned Items', icon: 'arrow-undo-outline', href: '/sm/returns' };

export function SmSidebar() {
  const visible = useSmMenu();
  const pathname = usePathname();
  const { data: scope } = useAsyncData<SmScope>(() => fetchSmScope(), []);
  const identity = staffSession.getIdentity();
  const [confirmOut, setConfirmOut] = useState(false);
  const slide = useRef(new Animated.Value(-340)).current;

  const productsOnly = scope?.is_products_only ?? false;
  const canMonitor = scope?.can_monitor_cross_branch ?? false;
  const canReturnedStock = scope?.can_returned_stock ?? false;
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
    smMenu.close();
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
          active && { backgroundColor: SM_ACCENT.main, borderColor: SM_ACCENT.main },
          pressed && !active && styles.itemPressed,
        ]}
        accessibilityRole="button"
        accessibilityState={{ selected: active }}
        accessibilityLabel={item.label}
      >
        <Ionicons name={item.icon} size={17} color={active ? '#052E1B' : '#CBD5E1'} />
        <Text style={[styles.itemLabel, active && styles.itemLabelActive]}>{item.label}</Text>
      </Pressable>
    );
  };

  return (
    <Modal transparent visible={visible} animationType="fade" onRequestClose={smMenu.close} statusBarTranslucent>
      <View style={styles.overlay}>
        {/* Tap the dimmed area to close — the website closes the sidebar the same way. */}
        <Pressable
          style={StyleSheet.absoluteFill}
          onPress={smMenu.close}
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
                <Text style={styles.brandSub}>Stock Manager</Text>
              </View>
            </View>

            {/* The blade's Monitoring box when the server reports cross-branch mode. */}
            {inCrossBranch ? (
              <View style={styles.monitorBox}>
                <Text style={styles.monitorLabel}>Monitoring</Text>
                <Text style={styles.monitorBranch} numberOfLines={1}>
                  {scope?.branch_name ?? 'Another branch'}
                </Text>
              </View>
            ) : null}

            <View style={styles.nav}>
              {renderItem(DASHBOARD)}

              <View style={styles.group}>
                <Text style={styles.groupLabel}>Stock Management</Text>
              </View>
              {STOCK_ITEMS.map(renderItem)}
              {canMonitor ? renderItem(CROSS_BRANCH_ITEM) : null}
              {!productsOnly ? BOTTLE_ITEMS.map(renderItem) : null}

              <View style={styles.group}>
                <Text style={styles.groupLabel}>Transfers</Text>
              </View>
              {TRANSFER_ITEMS.map(renderItem)}
              {canReturnedStock ? renderItem(RETURNED_STOCK_ITEM) : null}
              {renderItem(RETURNED_ITEMS_ITEM)}

              <View style={styles.divider} />
              <Text style={styles.groupLabel}>Account</Text>
              {renderItem({ label: 'Profile', icon: 'person-outline', href: '/sm/profile' })}

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
                        smMenu.close();
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
                <Text style={styles.footerRole}>Stock Manager</Text>
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
    borderRightColor: 'rgba(16, 185, 129, 0.25)',
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
    borderColor: SM_ACCENT.main,
  },
  brandText: { flex: 1 },
  brandTitle: { color: '#F1F5F9', fontSize: 12.5, fontWeight: '800', letterSpacing: 0.6 },
  brandSub: {
    color: SM_ACCENT.main,
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
    backgroundColor: 'rgba(16, 185, 129, 0.10)',
    borderWidth: 1,
    borderColor: 'rgba(16, 185, 129, 0.30)',
  },
  monitorLabel: {
    color: '#6EE7B7',
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 1.6,
    textTransform: 'uppercase',
  },
  monitorBranch: { color: '#F1F5F9', fontSize: 13.5, fontWeight: '700', marginTop: 2 },
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
  itemLabel: { color: '#CBD5E1', fontSize: 14, fontWeight: '600' },
  itemLabelActive: { color: '#052E1B', fontWeight: '800' },
  group: { marginTop: 12 },
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
    backgroundColor: SM_ACCENT.main,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: '#052E1B', fontWeight: '800', fontSize: 13 },
  footerText: { flex: 1 },
  footerName: { color: '#F1F5F9', fontSize: 13.5, fontWeight: '600' },
  footerRole: {
    color: SM_ACCENT.main,
    fontSize: 9.5,
    letterSpacing: 1.6,
    textTransform: 'uppercase',
    marginTop: 2,
  },
});
