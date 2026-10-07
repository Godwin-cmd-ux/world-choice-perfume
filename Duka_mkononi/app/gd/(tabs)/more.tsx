import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../../components/authkit';
import { ConfirmDialog, GroupLabel } from '../../../components/adminkit';
import { COLORS, GD_ACCENT, RADIUS } from '../../../lib/theme';
import { staffSession } from '../../../lib/staffSession';

/**
 * "More" — the mobile counterpart of the website sidebar's lower half for
 * Graphic Designers: QR Code and the shared Account block (Profile, Open
 * Website Dashboard, Sign Out). The QR page is a full screen of its own;
 * profile opens the same account/password form the website's /profile uses.
 */
export default function GdMore() {
  const [confirmOut, setConfirmOut] = useState(false);
  const identity = staffSession.getIdentity();

  const items = [
    { icon: 'qr-code-outline' as const, title: 'QR Code', hint: 'Scan to open the shop', path: '/gd/qr-code' },
    { icon: 'person-circle-outline' as const, title: 'Profile', hint: 'Name, photo, password', path: '/gd/profile' },
  ];

  return (
    <View style={styles.root}>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.identity}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(identity?.name || 'G').charAt(0).toUpperCase()}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.name}>{identity?.name ?? 'Graphic Designer'}</Text>
            <Text style={styles.role}>Graphic Designer · {identity?.email ?? ''}</Text>
          </View>
        </View>

        <Banner kind="connection" message="Session stays on this device only — sign in again after an app restart." />

        <GroupLabel>Tools</GroupLabel>
        <View style={styles.grid}>
          {items.map((item) => (
            <Pressable
              key={item.path}
              onPress={() => router.push(item.path as never)}
              style={({ pressed }) => [styles.tile, pressed && styles.pressed]}
              accessibilityRole="button"
            >
              <View style={styles.tileIcon}>
                <Ionicons name={item.icon} size={20} color={GD_ACCENT.main} />
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

        <Pressable
          onPress={() => router.replace('/staff')}
          style={({ pressed }) => [styles.websiteBtn, pressed && styles.pressed]}
          accessibilityRole="button"
        >
          <Ionicons name="open-outline" size={18} color={GD_ACCENT.light} />
          <Text style={styles.websiteText}>Open Website Dashboard</Text>
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
    backgroundColor: GD_ACCENT.soft,
    borderWidth: 1.5,
    borderColor: GD_ACCENT.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: GD_ACCENT.light, fontSize: 19, fontWeight: '800' },
  name: { color: COLORS.text, fontSize: 16, fontWeight: '800' },
  role: { color: GD_ACCENT.main, fontSize: 11.5, letterSpacing: 1, textTransform: 'uppercase', marginTop: 3 },
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
    backgroundColor: GD_ACCENT.soft,
    borderWidth: 1,
    borderColor: GD_ACCENT.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tileTitle: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
  tileHint: { color: COLORS.textMuted, fontSize: 11.5, lineHeight: 15 },
  websiteBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    minHeight: 50,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: GD_ACCENT.border,
    backgroundColor: GD_ACCENT.soft,
  },
  websiteText: { color: GD_ACCENT.light, fontSize: 15, fontWeight: '800' },
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
  pressed: { opacity: 0.75 },
});
