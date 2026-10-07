import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { ConfirmDialog, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { COLORS, SM_ACCENT, RADIUS } from '../../../lib/theme';
import { fetchSmScope, type SmScope } from '../../../lib/smApi';
import { staffSession } from '../../../lib/staffSession';

/**
 * "More" — the mobile counterpart of the website sidebar's rest for Stock
 * Managers: Sales, Orders, Product catalogue, the three bottle-module
 * screens (ONLY for autonomous branches), Returned Stock (Kinondoni only),
 * cross-branch monitoring (Kinondoni only), Movements and the shared
 * Account block. The tiles are chosen from scope so a products-only branch
 * never even sees a closed door.
 */
export default function SmMore() {
  const [confirmOut, setConfirmOut] = useState(false);
  const identity = staffSession.getIdentity();
  const { data: scope } = useAsyncData<SmScope>(() => fetchSmScope(), []);

  const productsOnly = scope?.is_products_only ?? false;
  const canMonitor = scope?.can_monitor_cross_branch ?? false;
  const canReturnedStock = scope?.can_returned_stock ?? false;

  const tools: { icon: keyof typeof Ionicons.glyphMap; title: string; hint: string; path: string }[] = [
    { icon: 'receipt-outline', title: 'Sales', hint: 'Your sales record + new sale', path: '/sm/sales' },
    { icon: 'list-outline', title: 'Orders', hint: 'Pending · Picked · Served', path: '/sm/orders' },
    { icon: 'pricetags-outline', title: 'Products', hint: 'Product catalogue CRUD', path: '/sm/products' },
    { icon: 'time-outline', title: 'Movements', hint: 'Stock movement history', path: '/sm/movements' },
    { icon: 'alert-circle-outline', title: 'Low Stock', hint: 'Products at or below 5', path: '/sm/low-stock' },
  ];

  if (!productsOnly) {
    tools.push(
      { icon: 'cube-outline', title: 'Bottle Stock', hint: 'Stock in, broken, variants', path: '/sm/bottle-stock' },
      { icon: 'flask-outline', title: 'Oil Fragrance', hint: 'Bulk oil in / out', path: '/sm/oil-fragrance' },
      { icon: 'construct-outline', title: 'Accessories', hint: 'Straws, necks, tops', path: '/sm/accessories' },
    );
  }

  if (canReturnedStock) {
    tools.push({ icon: 'shield-checkmark-outline', title: 'Returned Stock', hint: 'Lost / broken reports', path: '/sm/returned-stock' });
  }
  if (canMonitor) {
    tools.push({ icon: 'eye-outline', title: 'Cross-Branch', hint: 'Monitor other branches', path: '/sm/cross-branch' });
  }

  return (
    <View style={styles.root}>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.identity}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(identity?.name || 'S').charAt(0).toUpperCase()}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.name}>{identity?.name ?? 'Stock Manager'}</Text>
            <Text style={styles.role}>Stock Manager · {identity?.branch?.name ?? scope?.branch_name ?? ''}</Text>
          </View>
        </View>

        <View style={[styles.categoryChip, productsOnly && styles.categoryChipProducts]}>
          <Ionicons name={productsOnly ? 'cube-outline' : 'git-branch-outline'} size={14} color={productsOnly ? COLORS.info : SM_ACCENT.main} />
          <Text style={[styles.categoryText, { color: productsOnly ? COLORS.info : SM_ACCENT.main }]}>
            {scope?.branch_category_label ?? 'Loading branch rules…'}
          </Text>
        </View>

        <Banner kind="connection" message="Session stays on this device only — sign in again after an app restart." />

        <GroupLabel>Modules</GroupLabel>
        <View style={styles.grid}>
          {tools.map((item) => (
            <Pressable
              key={item.path}
              onPress={() => router.push(item.path as never)}
              style={({ pressed }) => [styles.tile, pressed && styles.pressed]}
              accessibilityRole="button"
            >
              <View style={styles.tileIcon}>
                <Ionicons name={item.icon} size={20} color={SM_ACCENT.main} />
              </View>
              <Text style={styles.tileTitle} numberOfLines={2}>
                {item.title}
              </Text>
              <Text style={styles.tileHint} numberOfLines={2}>
                {item.hint}
              </Text>
            </Pressable>
          ))}
        </View>

        <GroupLabel>Account</GroupLabel>
        <Pressable
          onPress={() => router.push('/sm/profile')}
          style={({ pressed }) => [styles.accountBtn, pressed && styles.pressed]}
          accessibilityRole="button"
        >
          <Ionicons name="person-circle-outline" size={18} color={SM_ACCENT.light} />
          <Text style={styles.accountText}>Profile & Password</Text>
        </Pressable>

        <Pressable
          onPress={() => router.replace('/staff')}
          style={({ pressed }) => [styles.accountBtn, pressed && styles.pressed]}
          accessibilityRole="button"
        >
          <Ionicons name="open-outline" size={18} color={SM_ACCENT.light} />
          <Text style={styles.accountText}>Open Website Dashboard</Text>
        </Pressable>

        <Pressable
          onPress={() => setConfirmOut(true)}
          style={({ pressed }) => [styles.signOut, pressed && styles.pressed]}
          accessibilityRole="button"
        >
          <Ionicons name="log-out-outline" size={18} color={COLORS.danger} />
          <Text style={styles.signOutText}>Sign Out</Text>
        </Pressable>
      </ScrollView>

      <ConfirmDialog
        visible={confirmOut}
        title="Sign out?"
        message="You will return to the staff login page. The signed-in session on this device is cleared."
        confirmLabel="Sign Out"
        danger
        onCancel={() => setConfirmOut(false)}
        onConfirm={() => {
          setConfirmOut(false);
          staffSession.clear();
          router.dismissAll?.();
          router.replace('/staff');
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: COLORS.bg },
  content: { padding: 16, paddingBottom: 40, gap: 14 },
  identity: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 14,
  },
  avatar: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1.5,
    borderColor: SM_ACCENT.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: SM_ACCENT.light, fontSize: 19, fontWeight: '800' },
  name: { color: COLORS.text, fontSize: 16, fontWeight: '800' },
  role: { color: SM_ACCENT.main, fontSize: 11.5, letterSpacing: 1, textTransform: 'uppercase', marginTop: 3 },
  categoryChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    alignSelf: 'flex-start',
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 12,
    paddingVertical: 7,
  },
  categoryChipProducts: { backgroundColor: 'rgba(96, 165, 250, 0.10)', borderColor: 'rgba(96, 165, 250, 0.30)' },
  categoryText: { fontSize: 12, fontWeight: '800', letterSpacing: 1, textTransform: 'uppercase' },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  tile: {
    width: '47.5%',
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: 14,
    gap: 7,
    minHeight: 104,
  },
  tileIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: SM_ACCENT.soft,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tileTitle: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
  tileHint: { color: COLORS.textMuted, fontSize: 11.5, lineHeight: 15 },
  pressed: { opacity: 0.7 },
  accountBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    minHeight: 50,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: SM_ACCENT.border,
    backgroundColor: SM_ACCENT.soft,
  },
  accountText: { color: SM_ACCENT.light, fontSize: 15, fontWeight: '800' },
  signOut: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    minHeight: 50,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.dangerBorder,
    backgroundColor: COLORS.dangerBg,
  },
  signOutText: { color: COLORS.danger, fontSize: 15, fontWeight: '800' },
});
