import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, KV, useAsyncData, messageOf } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { fetchReturnedStock, fileDamageReport, type SmReturnedRow } from '../../lib/smApi';
import { BUTTON, COLORS, RADIUS, SM_ACCENT } from '../../lib/theme';
import { smMenu } from '../../components/smsidebar';

/**
 * Returned Stock module — Kinondoni branch stock manager only (the server
 * answers 403 for anyone else, same as the website). Every returned item
 * can carry the mandatory lost / broken report that is forwarded to the
 * Super Admin with the transfer officer attached.
 */
export default function SmReturnedStock() {
  const { data, error, loading, sessionExpired, reload } = useAsyncData(() => fetchReturnedStock(), []);

  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [reportingId, setReportingId] = useState<string | null>(null);
  const [damageType, setDamageType] = useState<'lost' | 'broken'>('lost');
  const [damageReason, setDamageReason] = useState('');

  if (sessionExpired) {
    return (
      <View style={styles.guardWrap}>
        <Banner kind="error" message="Your session has expired. Please sign in again." />
      </View>
    );
  }

  const forbidden = (error ?? '').includes('Kinondoni');

  const run = async (fn: () => Promise<unknown>, after?: () => void) => {
    setBusy(true);
    setActionError(null);
    try {
      await fn();
      after?.();
      await reload();
    } catch (e) {
      setActionError(messageOf(e));
    } finally {
      setBusy(false);
    }
  };

  const rows = (data?.rows ?? []) as SmReturnedRow[];

  return (
    <AdminPage title="Returned Stock" eyebrow="Kinondoni only" onMenu={smMenu.open} accent={SM_ACCENT.main} onBack={() => router.back()}>
      {forbidden ? (
        <Banner kind="error" message={error ?? 'The Returned Stock module is reserved for the Kinondoni branch stock manager.'} />
      ) : (
        <>
          {error || actionError ? <Banner kind="error" message={actionError ?? error ?? ''} /> : null}
          {data && !data.hasDamageColumns ? (
            <Banner kind="error" message="Damage report columns are missing — run database/supabase_returned_stock.sql in Supabase to enable reports." />
          ) : null}

          {loading ? (
            <LoadingView label="Loading returned stock…" />
          ) : error && !data ? (
            <ErrorView message={error} onRetry={reload} />
          ) : rows.length === 0 ? (
            <EmptyView icon="shield-checkmark-outline" title="No returned items" hint="Items your sent transfers had rejected appear here." />
          ) : (
            rows.map((row, i) => {
              const itemId = String(row.item?.id ?? i);
              const status = row.item?.return_status ?? 'pending';
              const reported = status === 'reported';
              const expanded = reportingId === itemId;
              return (
                <DataCard
                  key={itemId}
                  title={row.item_label}
                  subtitle={`${row.transfer_number ?? ''} → ${row.to_branch_name ?? '—'}`}
                  lines={[
                    row.officer_name ? `Officer: ${row.officer_name}` : null,
                    row.item?.return_reason ? `Rejected: ${row.item.return_reason}` : null,
                    row.item?.damage_reason ? `Report (${row.item.damage_type}): ${row.item.damage_reason}` : null,
                    row.item?.damage_reported_at ? `Filed ${formatDate(row.item.damage_reported_at)}` : null,
                  ]}
                  badge={status}
                  badgeTone={reported ? 'danger' : status === 'written_off' ? 'muted' : 'warning'}
                  onPress={() => setReportingId(expanded ? null : itemId)}
                >
                  {expanded && !reported ? (
                    <View style={styles.reportBox}>
                      <GroupLabel>Lost / broken report</GroupLabel>
                      <ChipRow>
                        <Chip label="Lost" active={damageType === 'lost'} onPress={() => setDamageType('lost')} />
                        <Chip label="Broken" active={damageType === 'broken'} onPress={() => setDamageType('broken')} />
                      </ChipRow>
                      <AuthField
                        label="What happened? (required)"
                        value={damageReason}
                        onChangeText={setDamageReason}
                        placeholder="Details for the Super Admin…"
                        icon="document-text-outline"
                        autoCapitalize="sentences"
                      />
                      <Pressable
                        onPress={() => {
                          if (damageReason.trim().length < 3) return;
                          run(
                            () => fileDamageReport(itemId, { damage_type: damageType, damage_reason: damageReason.trim() }),
                            () => {
                              setReportingId(null);
                              setDamageReason('');
                            },
                          );
                        }}
                        style={({ pressed }) => [
                          styles.primaryBtn,
                          pressed && styles.pressed,
                          damageReason.trim().length < 3 && styles.disabled,
                        ]}
                        accessibilityRole="button"
                      >
                        <Text style={styles.primaryBtnText}>File report</Text>
                      </Pressable>
                      <Text style={styles.hint}>
                        Filing deducts the quantity from your stock again and notifies the Super Admin with the transfer officer&apos;s details.
                      </Text>
                    </View>
                  ) : null}
                  {reported && row.item?.damage_type ? (
                    <KV label={`Report (${row.item.damage_type})`} value={row.item.damage_reason} tone="danger" />
                  ) : null}
                </DataCard>
              );
            })
          )}
        </>
      )}

      <BusyOverlay visible={busy} label="Filing…" />
    </AdminPage>
  );
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
  reportBox: { marginTop: 10, gap: 8 },
  primaryBtn: {
    backgroundColor: BUTTON.fill,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 9,
    alignSelf: 'flex-start',
  },
  primaryBtnText: { color: BUTTON.text, fontWeight: '800', fontSize: 13 },
  disabled: { opacity: 0.4 },
  pressed: { opacity: 0.7 },
  hint: { color: COLORS.textMuted, fontSize: 12, lineHeight: 17 },
});
