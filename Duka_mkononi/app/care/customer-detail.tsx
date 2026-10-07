import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../components/adminkit';
import { ErrorView, LoadingView } from '../../components/ui';
import { fetchCustomer } from '../../lib/careApi';
import { formatDateTime, formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT, RADIUS } from '../../lib/theme';

/**
 * Client record — the website's customer-care/customers/{id}: contact
 * actions (whatsapp / email / call), the branch switcher with visit counts,
 * every transaction for the selected branch, and the "what they buy"
 * summary the website builds from the line items.
 */
export default function CareCustomerDetail() {
  const { id, notice } = useLocalSearchParams<{ id: string; notice?: string }>();
  const [branch, setBranch] = useState<string | undefined>(undefined);

  const { data, error, loading, sessionExpired, reload } = useAsyncData(
    () => fetchCustomer(id!, branch),
    [id, branch],
  );

  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const customer = data?.customer;

  return (
    <AdminPage
      title={customer?.name || 'Client'}
      eyebrow="Client record"
      accent={CC_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
    >
      {notice ? <Banner kind="success" message={String(notice)} /> : null}
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

      {loading && !data ? (
        <LoadingView label="Loading record…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : data && customer ? (
        <>
          <DataCard
            title={customer.name || 'Unnamed client'}
            subtitle={[customer.phone, customer.email].filter(Boolean).join(' · ') || 'No contact details'}
            badge={formatMoney(data.totalSpent)}
            badgeTone="success"
          >
            <View style={styles.contactRow}>
              {data.whatsappLink ? (
                <ContactBtn icon="logo-whatsapp" label="WhatsApp" onPress={() => Linking.openURL(data.whatsappLink!)} />
              ) : null}
              {customer.phone ? (
                <ContactBtn icon="call-outline" label="Call" onPress={() => Linking.openURL(`tel:${customer.phone}`)} />
              ) : null}
              {data.emailLink ? (
                <ContactBtn icon="mail-outline" label="Email" onPress={() => Linking.openURL(data.emailLink!)} />
              ) : null}
            </View>
          </DataCard>

          <GroupLabel>Transactions</GroupLabel>
          <ChipRow>
            <Chip label="All Branches" active={data.selected === 'all'} onPress={() => setBranch('all')} />
            {data.branchTabs.map((tab) => (
              <Chip
                key={tab.id}
                label={`${tab.name} (${tab.count})`}
                active={data.selected === String(tab.id)}
                onPress={() => setBranch(String(tab.id))}
              />
            ))}
          </ChipRow>

          {data.transactions.length === 0 ? (
            <DataCard title="No transactions" subtitle={data.selectedName} />
          ) : (
            data.transactions.map((txn) => (
              <DataCard
                key={String(txn.id)}
                title={txn.sale_number ?? `Sale #${txn.id}`}
                subtitle={txn.branch_name ?? null}
                badge={formatMoney(txn.total)}
                badgeTone="success"
                lines={[
                  formatDateTime(txn.created_at),
                  txn.payment_summary ?? null,
                  txn.items.length > 0 ? txn.items.map((it) => `${it.quantity}× ${it.name}`).join(', ') : null,
                ].filter(Boolean) as string[]}
              />
            ))
          )}

          <GroupLabel>What {customer.name || 'they'} buys</GroupLabel>
          {data.itemSummary.length === 0 ? (
            <DataCard title="Nothing yet" subtitle="Line items appear here after the first sale" />
          ) : (
            data.itemSummary.map((item) => (
              <DataCard
                key={item.name}
                title={item.name}
                subtitle={item.brand ?? undefined}
                badge={`${item.quantity} sold`}
                badgeTone="gold"
                lines={[`${item.times} purchases · ${formatMoney(item.spent)}`, item.last_bought ? `Last: ${formatDateTime(item.last_bought)}` : null].filter(Boolean) as string[]}
              />
            ))
          )}
        </>
      ) : null}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

function ContactBtn({ icon, label, onPress }: { icon: keyof typeof Ionicons.glyphMap; label: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.contactBtn, pressed && { opacity: 0.7 }]} accessibilityRole="button">
      <Ionicons name={icon} size={14} color={CC_ACCENT.light} />
      <Text style={styles.contactLabel}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  contactRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10 },
  contactBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderWidth: 1,
    borderColor: CC_ACCENT.border,
    backgroundColor: CC_ACCENT.soft,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  contactLabel: { color: CC_ACCENT.light, fontWeight: '800', fontSize: 12 },
});
