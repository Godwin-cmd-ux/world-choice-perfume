import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchSales, type SmSale } from '../../lib/smApi';
import { BUTTON, COLORS, RADIUS, SM_ACCENT } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

type Range = 'today' | 'week' | 'all';

/**
 * Sales — the stock manager's OWN sales record (same cashier filter the
 * website applies), with totals and a New Sale entry. The website's show
 * page is mirrored by the expandable detail below each row.
 */
export default function SmSales() {
  const [range, setRange] = useState<Range>('all');
  const today = new Date().toISOString().slice(0, 10);
  const weekAgo = new Date(Date.now() - 6 * 86400000).toISOString().slice(0, 10);

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(
    () =>
      fetchSales(
        range === 'today'
          ? { date_from: today, date_to: today }
          : range === 'week'
            ? { date_from: weekAgo }
            : {},
      ),
    [range],
  );

  const [openId, setOpenId] = useState<string | null>(null);

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const sales = data?.sales ?? [];

  return (
    <AdminPage
      title="My Sales"
      eyebrow="Stock Manager" onMenu={smMenu.open}
      accent={SM_ACCENT.main}
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={refresh}
      action={
        <Pressable
          onPress={() => router.push('/sm/sale-new')}
          style={({ pressed }) => [styles.newBtn, pressed && styles.pressed]}
          accessibilityRole="button"
        >
          <Text style={styles.newBtnText}>New</Text>
        </Pressable>
      }
    >
      <ChipRow>
        <Chip label="Today" active={range === 'today'} onPress={() => setRange('today')} />
        <Chip label="7 days" active={range === 'week'} onPress={() => setRange('week')} />
        <Chip label="All" active={range === 'all'} onPress={() => setRange('all')} />
      </ChipRow>

      {error ? <Banner kind="error" message={error} /> : null}

      {loading ? (
        <LoadingView label="Loading sales…" />
      ) : error && !data ? (
        <ErrorView message={error} onRetry={reload} />
      ) : (
        <>
          <StatGrid>
            <StatTile label="Sales" value={sales.length} />
            <StatTile label="Total value" value={formatMoney(data?.totalSales ?? 0)} tone="success" />
          </StatGrid>

          <GroupLabel>Sales record</GroupLabel>
          {sales.length === 0 ? (
            <EmptyView
              icon="receipt-outline"
              title="No sales yet"
              hint="Tap New to record your first sale."
            />
          ) : (
            sales.map((s) => (
              <DataCard
                key={String(s.id)}
                title={s.sale_number ?? `Sale #${s.id}`}
                subtitle={s.customer?.name ?? s.customer?.phone ?? undefined}
                lines={[
                  `${formatMoney(s.total ?? 0)} TZS · ${s.payment_summary ?? s.payment_method ?? ''}`,
                  formatDate(s.created_at),
                ]}
                badge={s.sale_type ?? undefined}
                badgeTone="success"
                onPress={() => setOpenId(openId === String(s.id) ? null : String(s.id))}
              >
                {openId === String(s.id) ? <SaleDetail sale={s} /> : null}
              </DataCard>
            ))
          )}
        </>
      )}
    </AdminPage>
  );
}

function SaleDetail({ sale }: { sale: SmSale }) {
  const items = sale.items ?? [];
  return (
    <View style={styles.detail}>
      <Text style={styles.detailTitle}>Items</Text>
      {items.length === 0 ? (
        <Text style={styles.detailMuted}>No item rows stored on this sale.</Text>
      ) : (
        items.map((raw, i) => {
          const item = raw as Record<string, unknown>;
          const product = item.product as { name?: string } | undefined;
          return (
            <Text key={i} style={styles.detailLine}>
              {product?.name ?? `Product #${item.product_id ?? ''}`} × {String(item.quantity ?? '')} —{' '}
              {formatMoney(Number(item.total ?? 0))} TZS
              {item.volume ? ` · ${String(item.volume)}ml` : ''}
            </Text>
          );
        })
      )}
      <Text style={styles.detailLine}>
        Subtotal: {formatMoney(sale.subtotal ?? sale.total ?? 0)} TZS
      </Text>
      {sale.cashier?.name ? <Text style={styles.detailMuted}>Cashier: {sale.cashier.name}</Text> : null}
    </View>
  );
}

function formatMoney(value: number): string {
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
}

function formatDate(value?: string | null): string {
  if (!value) return '';
  const d = new Date(value);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleString('en-US', {
    timeZone: 'Africa/Dar_es_Salaam',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

const styles = StyleSheet.create({
  guardWrap: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: COLORS.bg },
  newBtn: { backgroundColor: BUTTON.fill, borderRadius: RADIUS.pill, paddingHorizontal: 14, paddingVertical: 8 },
  newBtnText: { color: BUTTON.text, fontWeight: '800', fontSize: 13 },
  pressed: { opacity: 0.7 },
  detail: {
    marginTop: 10,
    gap: 4,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 10,
  },
  detailTitle: { color: SM_ACCENT.light, fontSize: 12, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1 },
  detailLine: { color: COLORS.textSecondary, fontSize: 13, lineHeight: 19 },
  detailMuted: { color: COLORS.textMuted, fontSize: 12.5 },
});
