import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AdminPage, BusyOverlay, ConfirmDialog, DataCard, GroupLabel, useAsyncData } from '../../../components/adminkit';
import { ErrorView, LoadingView } from '../../../components/ui';
import { fetchCcScope } from '../../../lib/careApi';
import { staffSession } from '../../../lib/staffSession';
import { CC_ACCENT, COLORS, RADIUS } from '../../../lib/theme';

/**
 * More — the creative answer to a seven-entry sidebar. The bottom bar only
 * ever holds four daily destinations; this hub folds everything else into
 * grouped tiles:
 *
 *   HQ ONLY   Inquiries · News · info@ Mails   (Head Quarters member only,
 *                                               server enforces the same gate)
 *   SELLING   Sales · New Sale
 *   ACCOUNT   Profile · Sign Out
 *
 * Branch members simply never see the first group — their module is
 * intentionally smaller than Head Quarters', exactly like the website.
 */
export default function CareMore() {
  const { data: scope, error, loading, sessionExpired, reload } = useAsyncData(() => fetchCcScope(), []);
  const [confirmOut, setConfirmOut] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const isHq = scope?.is_hq ?? false;
  const identity = staffSession.getIdentity();

  return (
    <AdminPage
      title="More"
      eyebrow={isHq ? 'Head Quarters-Mikocheni' : scope?.branch_name ?? 'Customer Care'}
      accent={CC_ACCENT.main}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {error ? <ErrorView message={error} onRetry={reload} /> : null}
      {loading && !scope ? <LoadingView label="Loading…" /> : null}

      {isHq ? (
        <>
          <GroupLabel>Head Quarters only</GroupLabel>
          <View style={styles.grid}>
            <Tile icon="chatbubble-ellipses-outline" label="Inquiries" hint="Reply, feature, delete" onPress={() => router.push('/care/inquiries')} />
            <Tile icon="newspaper-outline" label="News" hint="Moderate + publish" onPress={() => router.push('/care/news')} />
            <Tile icon="mail-outline" label="info@ Mails" hint="Company inbox" onPress={() => router.push('/care/mails')} />
          </View>
        </>
      ) : null}

      <GroupLabel>Selling</GroupLabel>
      <View style={styles.grid}>
        <Tile icon="cart-outline" label="Sales" hint="Branch history" onPress={() => router.push('/care/sales')} />
        <Tile icon="cart-outline" label="New Sale" hint="Full checkout" onPress={() => router.push('/care/sale-new')} />
      </View>

      <GroupLabel>Account</GroupLabel>
      <DataCard
        title={identity?.name ?? 'Customer Care'}
        subtitle={[identity?.email, identity?.role].filter(Boolean).join(' · ')}
        onPress={() => router.push('/care/profile')}
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
        <Ionicons name={icon} size={20} color={CC_ACCENT.light} />
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
    borderColor: CC_ACCENT.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    padding: 12,
  },
  tileIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: CC_ACCENT.soft,
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
