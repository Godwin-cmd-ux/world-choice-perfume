import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, DataCard, GroupLabel, KV, useAsyncData } from '../../components/adminkit';
import { ErrorView, LoadingView } from '../../components/ui';
import { fetchSale } from '../../lib/careApi';
import { formatDateTime, formatMoney } from '../../lib/format';
import { staffSession } from '../../lib/staffSession';
import { CC_ACCENT } from '../../lib/theme';

/**
 * Sale receipt — the website's customer-care/sales/{id}: line items with
 * their product names, the payment summary and the cashier who rang it up.
 */
export default function CareSaleDetail() {
  const { id, notice } = useLocalSearchParams<{ id: string; notice?: string }>();
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchSale(id!), [id]);
  const [refreshing, setRefreshing] = useState(false);

  if (sessionExpired) {
    staffSession.clear();
    router.replace('/staff');
    return null;
  }

  const sale = data?.sale;
  const items = (sale?.items ?? []) as {
    id?: number | string;
    quantity?: number;
    unit_price?: number;
    total?: number;
    volume?: number | null;
    variant?: string | null;
    product?: { name?: string; brand?: string } | null;
    name?: string;
  }[];

  return (
    <AdminPage
      title={sale?.sale_number ? String(sale.sale_number) : 'Sale'}
      eyebrow="Receipt"
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
        <LoadingView label="Loading sale…" />
      ) : error && !sale ? (
        <ErrorView message={error} onRetry={reload} />
      ) : sale ? (
        <>
          <DataCard
            title={formatMoney(sale.total ?? 0)}
            subtitle={sale.payment_summary ?? undefined}
            badge={sale.sale_type ?? undefined}
            badgeTone="success"
          >
            <View>
              <KV label="Date" value={formatDateTime(sale.created_at)} />
              <KV label="Customer" value={sale.customer?.name ?? 'Walk-in customer'} />
              <KV label="Phone" value={sale.customer?.phone ?? null} />
              <KV label="Served by" value={sale.cashier?.name ?? null} />
              <KV label="Branch" value={sale.branch?.name ?? null} />
              <KV label="Payment" value={sale.payment_summary ?? null} />
              <KV label="Payment status" value={sale.payment_status ?? null} />
            </View>
          </DataCard>

          <GroupLabel>Items</GroupLabel>
          {items.length === 0 ? (
            <DataCard title="No line items" />
          ) : (
            items.map((it, i) => (
              <DataCard
                key={String(it.id ?? i)}
                title={it.product?.name ?? it.name ?? `Item ${i + 1}`}
                subtitle={it.product?.brand ?? undefined}
                badge={formatMoney(it.total ?? 0)}
                badgeTone="gold"
                lines={[
                  `${it.quantity ?? 0} × ${formatMoney(it.unit_price ?? 0)}`,
                  it.volume ? `${it.volume}ml${it.variant ? ` · ${it.variant}` : ''}` : null,
                ].filter(Boolean) as string[]}
              />
            ))
          )}

          <DataCard title="Thank you for your purchase!" subtitle="World Choice Perfume" />
        </>
      ) : null}

      <BusyOverlay visible={refreshing} />
    </AdminPage>
  );
}
