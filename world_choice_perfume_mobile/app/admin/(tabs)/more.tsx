import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner } from '../../../components/authkit';
import { ConfirmDialog, GroupLabel } from '../../../components/adminkit';
import { COLORS, RADIUS } from '../../../lib/theme';
import { staffSession } from '../../../lib/staffSession';

/**
 * "More" — the grouped launcher that replaces the website's desktop sidebar.
 * The 13 sidebar sections are translated into three groups (Monitor /
 * Records / System) so everything stays one tap away without cramming the
 * bottom bar. Account actions (profile, sign out) sit at the bottom exactly
 * where the sidebar's Account block sits.
 */
export default function AdminMore() {
  // Screens draw edge-to-edge, so the page keeps its own header clear of the status bar.
  const insets = useSafeAreaInsets();
  const [confirmOut, setConfirmOut] = useState(false);
  const identity = staffSession.getIdentity();

  const groups: { label: string; items: { icon: keyof typeof Ionicons.glyphMap; title: string; hint?: string; path: string }[] }[] = [
    {
      label: 'Monitor',
      items: [
        { icon: 'trending-up-outline', title: 'Daily Sales Overview', hint: 'All branches, today', path: '/admin/daily-sales' },
        { icon: 'cube-outline', title: 'Cross-Branch Stock', hint: 'Stock levels by branch', path: '/admin/cross-stock' },
        { icon: 'cash-outline', title: 'Cross-Branch Sales', hint: 'Sales pulse by branch', path: '/admin/cross-sales' },
      ],
    },
    {
      label: 'Records',
      items: [
        { icon: 'storefront-outline', title: 'Branches', hint: 'Create, edit, deactivate, delete', path: '/admin/branches' },
        { icon: 'people-outline', title: 'Staff', hint: 'Create, block, change status, delete', path: '/admin/staff' },
        { icon: 'mail-outline', title: 'Emails', hint: 'The info@ mailbox', path: '/admin/emails' },
        { icon: 'notifications-outline', title: 'Notifications', hint: 'Admin alerts', path: '/admin/notifications' },
        { icon: 'return-down-back-outline', title: 'Returned Stock', hint: 'Lost / broken reports', path: '/admin/returned-stock' },
        { icon: 'stats-chart-outline', title: 'Reports', hint: 'Sales, expenses, stock, performance', path: '/admin/reports' },
      ],
    },
    {
      label: 'System',
      items: [
        { icon: 'key-outline', title: 'Settings', hint: 'Company secret codes', path: '/admin/settings' },
        { icon: 'person-circle-outline', title: 'Profile', hint: 'Name, photo, password', path: '/admin/profile' },
      ],
    },
  ];

  return (
    <View style={[styles.root, { paddingTop: insets.top }]}>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.identity}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(identity?.name || 'S').charAt(0).toUpperCase()}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.name}>{identity?.name ?? 'Super Admin'}</Text>
            <Text style={styles.role}>Super Admin · {identity?.email ?? ''}</Text>
          </View>
        </View>

        <Banner kind="connection" message="Session stays on this device only — sign in again after an app restart." />

        {groups.map((group) => (
          <View key={group.label} style={styles.group}>
            <GroupLabel>{group.label}</GroupLabel>
            <View style={styles.grid}>
              {group.items.map((item) => (
                <Pressable
                  key={item.path}
                  onPress={() => router.push(item.path as never)}
                  style={({ pressed }) => [styles.tile, pressed && styles.pressed]}
                  accessibilityRole="button"
                >
                  <View style={styles.tileIcon}>
                    <Ionicons name={item.icon} size={20} color={COLORS.gold} />
                  </View>
                  <Text style={styles.tileTitle} numberOfLines={2}>
                    {item.title}
                  </Text>
                  {item.hint ? (
                    <Text style={styles.tileHint} numberOfLines={2}>
                      {item.hint}
                    </Text>
                  ) : null}
                </Pressable>
              ))}
            </View>
          </View>
        ))}

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
    backgroundColor: COLORS.goldDeep,
    borderWidth: 1.5,
    borderColor: COLORS.gold,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: COLORS.goldLight, fontSize: 19, fontWeight: '800' },
  name: { color: COLORS.text, fontSize: 16, fontWeight: '800' },
  role: { color: COLORS.gold, fontSize: 11.5, letterSpacing: 1, textTransform: 'uppercase', marginTop: 3 },
  group: { gap: 10 },
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
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tileTitle: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
  tileHint: { color: COLORS.textMuted, fontSize: 11.5, lineHeight: 15 },
  pressed: { opacity: 0.75 },
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
    marginTop: 6,
  },
  signOutText: { color: COLORS.danger, fontSize: 15, fontWeight: '800' },
});
