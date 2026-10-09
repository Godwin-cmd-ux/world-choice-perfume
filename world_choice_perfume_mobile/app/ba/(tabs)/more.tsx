import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AdminPage, BusyOverlay, ConfirmDialog, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { baMenu } from '../../../components/basidebar';
import { ErrorView, LoadingView } from '../../../components/ui';
import { fetchBaScope } from '../../../lib/baApi';
import { staffSession } from '../../../lib/staffSession';
import { BA_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * More — the creative answer to the website's five-entry sidebar. The
 * bottom bar holds the four daily destinations; this hub folds the rest
 * into grouped tiles:
 *
 *   BRANCH OPS   Staffs · Expenses
 *   SELLING      New Sale · Sales history
 *   ACCOUNT      Profile · Sign Out
 *
 * Staffs and Expenses are full sidebar entries on the website — they earn
 * a tile here instead of a fifth bottom-bar icon.
 */
export default function BaMore() {
  const { data: scope, error, loading, sessionExpired, reload } = useAsyncData(() => fetchBaScope(), []);
  const [confirmOut, setConfirmOut] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const identity = staffSession.getIdentity();

  return (
    <AdminPage
      title="More"
      eyebrow={scope?.branch_name ?? 'Branch Admin'}
      accent={BA_ACCENT.main}
      onMenu={baMenu.open}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <ErrorView message={error} onRetry={reload} /> : null}
      {loading && !scope ? <LoadingView label="Loading…" /> : null}

      <GroupLabel>Branch ops</GroupLabel>
      <View style={styles.grid}>
        <Tile icon="people-outline" label="Staffs" hint="Team, approvals" onPress={() => router.push('/ba/staff')} />
        <Tile icon="cash-outline" label="Expenses" hint="View-only, by date" onPress={() => router.push('/ba/expenses')} />
      </View>

      <GroupLabel>Selling</GroupLabel>
      <View style={styles.grid}>
        <Tile icon="cart-outline" label="New Sale" hint="Full checkout" onPress={() => router.push('/ba/sale-new')} />
        <Tile icon="receipt-outline" label="Sales History" hint="Filter by cashier" onPress={() => router.push('/ba/(tabs)/sales')} />
      </View>

      <GroupLabel>Account</GroupLabel>
      <DataCard
        title={identity?.name ?? 'Branch Admin'}
        subtitle={[identity?.email, identity?.role].filter(Boolean).join(' · ')}
        onPress={() => router.push('/ba/profile')}
      />
      <Pressable
        onPress={() => setConfirmOut(true)}
        style={({ pressed }) => [styles.signOut, pressed && { opacity: 0.7 }]}
        accessibilityRole="button"
      >
        <Ionicons name="log-out-outline" size={18} color={COLORS.danger} />
        <Text style={styles.signOutText}>Sign Out</Text>
      </Pressable>

      <ConfirmDialog
        visible={confirmOut}
        title="Sign out?"
        message="You will need the staff secret code and your password to sign back in."
        confirmLabel="Sign Out"
        danger
        onConfirm={() => {
          staffSession.clear();
          router.replace('/');
        }}
        onCancel={() => setConfirmOut(false)}
      />
      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

function Tile({
  icon,
  label,
  hint,
  onPress,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  hint: string;
  onPress: () => void;
}) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.tile, pressed && { opacity: 0.72 }]} accessibilityRole="button">
      <View style={styles.tileIcon}>
        <Ionicons name={icon} size={20} color={BA_ACCENT.light} />
      </View>
      <Text style={styles.tileLabel}>{label}</Text>
      <Text style={styles.tileHint} numberOfLines={1}>
        {hint}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 6 },
  tile: {
    width: '48%',
    backgroundColor: COLORS.bgRaised,
    borderColor: BA_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
  },
  tileIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: BA_ACCENT.soft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  tileLabel: { color: COLORS.textSecondary, fontWeight: '800', fontSize: 14 },
  tileHint: { color: COLORS.textMuted, fontSize: 11, marginTop: 2 },
  signOut: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderColor: 'rgba(248, 113, 113, 0.4)',
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: 12,
    marginTop: 14,
  },
  signOutText: { color: COLORS.danger, fontWeight: '800', fontSize: 13 },
});
