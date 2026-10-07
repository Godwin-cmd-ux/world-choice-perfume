import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchSales } from '../../lib/careApi';
import { formatDateTime, formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT } from '../../lib/theme';

/**
 * Sales — the website's customer-care/sales index: the branch's latest 50
 * sales with the customer, cashier and running total. Tap opens the
 * receipt; the header button starts a new sale.
 */
export default function CareSales() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchSales(), []);
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  return (
    <AdminPage
      title="Sales"
      eyebrow="Customer Care"
      accent={CC_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={async () => {
        setRefreshing(true);
        await reload();
        setRefreshing(false);
      }}
      action={
        <Pressable
          onPress={() => router.push('/care/sale-new')}
          style={({ pressed }) => [styles.newBtn, pressed && { opacity: 0.7 }]}
          accessibilityRole="button"
          accessibilityLabel="New sale"
        >
          <Text style={styles.newBtnText}>+ New</Text>
        </Pressable>
      }
    >
      {error ? <Banner kind="error" message={error} actionLabel="Retry" onAction={reload} /> : null}

      {loading && !data ? (
        <LoadingView label="Loading sales…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : !data || data.sales.length === 0 ? (
        <EmptyView icon="cart-outline" title="No sales yet" hint="Ring up the first sale from More → New Sale." />
      ) : (
        <>
          <GroupLabel>Latest first</GroupLabel>
          <DataCard title="Total" subtitle={`${data.sales.length} sales`} badge={formatMoney(data.totalRevenue)} badgeTone="success" />
          {data.sales.map((sale) => (
            <DataCard
              key={String(sale.id)}
              title={sale.sale_number ?? `Sale #${sale.id}`}
              subtitle={sale.customer?.name ?? 'Walk-in customer'}
              badge={formatMoney(sale.total ?? 0)}
              badgeTone="success"
              lines={[formatDateTime(sale.created_at), sale.payment_summary ?? null].filter(Boolean) as string[]}
              onPress={() => router.push({ pathname: '/care/sale-detail', params: { id: String(sale.id) } })}
            />
          ))}
        </>
      )}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  newBtn: {
    backgroundColor: 'rgba(56, 189, 248, 0.12)',
    borderColor: 'rgba(56, 189, 248, 0.30)',
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  newBtnText: { color: '#7DD3FC', fontWeight: '800', fontSize: 12 },
});
