import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, Chip, ChipRow, DataCard, GroupLabel, KV, StatGrid, StatTile, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchReturnedStock, type ReturnedStockPayload } from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';

/**
 * Returned Stock — the mobile twin of super-admin/returned-stock/index:
 * every rejected/returned transfer item with its lost/broken report, the
 * same damage-type / branch / date filters, the lost & broken counters, and
 * the transfer officer attached to each report for physical follow-up. The
 * website's printable report renders from the same rows — the app shows them
 * natively.
 */
export default function ReturnedStock() {
  const [damageType, setDamageType] = useState('');
  const [branchId, setBranchId] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<ReturnedStockPayload>(
    () =>
      fetchReturnedStock({
        damage_type: damageType || undefined,
        branch_id: branchId || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
      }),
    [damageType, branchId, dateFrom, dateTo],
  );

  const rows = data?.rows ?? [];
  const branches = Object.entries(data?.branches ?? {});

  return (
    <AdminPage title="Returned Stock" onBack={() => router.back()} refreshing={refreshing} onRefresh={refresh}>
      {loading ? <LoadingView label="Loading returns…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}

          <StatGrid>
            <StatTile label="Lost reported" value={data.lost_count} tone="danger" />
            <StatTile label="Broken reported" value={data.broken_count} tone="warning" />
            <StatTile label="Returned rows" value={rows.length} />
          </StatGrid>

          <ChipRow>
            <Chip label="All" active={!damageType} onPress={() => setDamageType('')} />
            <Chip label="Lost" active={damageType === 'lost'} onPress={() => setDamageType('lost')} />
            <Chip label="Broken" active={damageType === 'broken'} onPress={() => setDamageType('broken')} />
          </ChipRow>

          <ChipRow>
            <Chip label="Any branch" active={!branchId} onPress={() => setBranchId('')} />
            {branches.slice(0, 12).map(([bid, bname]) => (
              <Chip key={bid} label={String(bname)} active={branchId === bid} onPress={() => setBranchId(bid)} />
            ))}
          </ChipRow>

          <ChipRow>
            <Chip
              label={dateFrom ? `From ${dateFrom} ✕` : 'From date…'}
              active={!!dateFrom}
              onPress={() => setDateFrom(dateFrom ? '' : new Date(Date.now() - 7 * 864e5).toISOString().slice(0, 10))}
            />
            <Chip
              label={dateTo ? `To ${dateTo} ✕` : 'To date…'}
              active={!!dateTo}
              onPress={() => setDateTo(dateTo ? '' : new Date().toISOString().slice(0, 10))}
            />
          </ChipRow>

          <GroupLabel right={<Text style={styles.count}>{rows.length} items</Text>}>Returned items</GroupLabel>

          {rows.length === 0 ? (
            <EmptyView icon="return-down-back-outline" title="No returned items" hint="Nothing matches these filters." />
          ) : (
            rows.map((row, i) => (
              <DataCard
                key={`${row.transfer_number ?? 't'}-${i}`}
                title={row.item_label}
                subtitle={`${row.from_branch_name} → ${row.to_branch_name}`}
                badge={row.damage_type ?? row.return_status ?? 'returned'}
                badgeTone={row.damage_type === 'lost' ? 'danger' : row.damage_type === 'broken' ? 'warning' : 'muted'}
                lines={[
                  row.transfer_number ? `Transfer: ${row.transfer_number}` : null,
                  row.officer_name ? `Officer: ${row.officer_name}${row.officer_phone ? ` · ${row.officer_phone}` : ''}` : null,
                  row.returned_at ? `Returned: ${new Date(row.returned_at).toLocaleString()}` : null,
                ]}
              >
                <View style={{ gap: 4 }}>
                  <KV label="Reason:" value={row.return_reason} />
                  <KV label="Damage reason:" value={row.damage_reason} />
                  <KV label="Reported by:" value={row.damage_reported_by_name} />
                  <KV label="Reported at:" value={row.damage_reported_at ? new Date(row.damage_reported_at).toLocaleString() : null} />
                </View>
              </DataCard>
            ))
          )}
        </>
      ) : null}
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  count: { color: COLORS.textMuted, fontSize: 12 },
});
