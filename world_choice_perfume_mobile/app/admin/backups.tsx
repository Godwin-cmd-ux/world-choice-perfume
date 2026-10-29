import * as Linking from 'expo-linking';
import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { Banner } from '../../components/authkit';
import {
  AdminPage,
  BusyOverlay,
  ConfirmDialog,
  DataCard,
  GroupLabel,
  StatGrid,
  StatTile,
  messageOf,
  useAsyncData,
} from '../../components/adminkit';
import { EmptyView, ErrorView, GoldButton, LoadingView, OutlineButton } from '../../components/ui';
import { deleteAdminBackup, fetchAdminBackups, induceAdminBackup, type AdminBackupsPayload } from '../../lib/adminApi';
import { COLORS, RADIUS } from '../../lib/theme';
import { adminMenu } from '../../components/adminsidebar';

/**
 * Backups — the mobile twin of super-admin/backups.
 *
 * The same catalogue the website lists: the nightly snapshot (one per day) and
 * any backup induced on demand (own time, own folder under induced/). From here
 * the Super Admin can take one immediately, narrow the list by date, open either
 * SQL file, or delete a backup — which removes both files from Cloudinary and
 * only then its row, exactly as the website does.
 */
export default function Backups() {
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [fromInput, setFromInput] = useState('');
  const [toInput, setToInput] = useState('');

  const [inducing, setInducing] = useState(false);
  const [confirmInduce, setConfirmInduce] = useState(false);
  const [deleting, setDeleting] = useState<string | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [failure, setFailure] = useState<string | null>(null);

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData<AdminBackupsPayload>(
    () => fetchAdminBackups({ date_from: from || undefined, date_to: to || undefined }),
    [from, to],
  );

  const rows = data?.backups ?? [];
  const filtered = Boolean(from || to);
  const size = humanBytes(filtered ? data?.matching_bytes ?? 0 : data?.total_bytes ?? 0);

  const applyDates = () => {
    setFrom(isDate(fromInput) ? fromInput : '');
    setTo(isDate(toInput) ? toInput : '');
  };

  const clearDates = () => {
    setFromInput('');
    setToInput('');
    setFrom('');
    setTo('');
  };

  const induce = async () => {
    setConfirmInduce(false);
    setInducing(true);
    setNotice(null);
    setFailure(null);
    try {
      const result = await induceAdminBackup();
      setNotice(result.message);
      reload();
    } catch (e) {
      setFailure(messageOf(e));
    } finally {
      setInducing(false);
    }
  };

  const remove = async (stamp: string) => {
    setConfirmDelete(null);
    setDeleting(stamp);
    setNotice(null);
    setFailure(null);
    try {
      const result = await deleteAdminBackup(stamp);
      setNotice(result.message);
      reload();
    } catch (e) {
      setFailure(messageOf(e));
    } finally {
      setDeleting(null);
    }
  };

  return (
    <AdminPage
      title="Backups"
      onBack={() => router.back()}
      onMenu={adminMenu.open}
      refreshing={refreshing}
      onRefresh={refresh}
    >
      <BusyOverlay visible={inducing} label="Taking the backup…" />
      <BusyOverlay visible={deleting !== null} label="Deleting…" />

      {loading ? <LoadingView label="Loading backups…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {error ? <Banner kind="error" message={error} /> : null}
          {notice ? <Banner kind="success" message={notice} /> : null}
          {failure ? <Banner kind="error" message={failure} /> : null}
          {data.cloudinary_warning ? (
            <Banner kind="error" message={`Cloudinary could not be read: ${data.cloudinary_warning}`} />
          ) : null}

          <StatGrid>
            <StatTile label={filtered ? 'In range' : 'Stored'} value={data.total_stored} hint={filtered ? `${rows.length} shown` : undefined} />
            <StatTile label="Latest" value={rows[0]?.date ?? '—'} tone="success" />
            <StatTile label="Cloudinary size" value={size} tone="info" />
          </StatGrid>

          <GoldButton label="Induce Backup" icon="flash-outline" onPress={() => setConfirmInduce(true)} />

          <View style={styles.filterCard}>
            <Text style={styles.filterLabel}>Date range</Text>
            <View style={styles.filterRow}>
              <TextInput
                value={fromInput}
                onChangeText={setFromInput}
                placeholder="From YYYY-MM-DD"
                placeholderTextColor={COLORS.textMuted}
                style={styles.input}
                autoCapitalize="none"
                keyboardType="numbers-and-punctuation"
              />
              <TextInput
                value={toInput}
                onChangeText={setToInput}
                placeholder="To YYYY-MM-DD"
                placeholderTextColor={COLORS.textMuted}
                style={styles.input}
                autoCapitalize="none"
                keyboardType="numbers-and-punctuation"
              />
            </View>
            <View style={styles.filterRow}>
              <OutlineButton label="Apply" onPress={applyDates} style={styles.flex} />
              <OutlineButton label={filtered ? 'Clear' : 'All dates'} onPress={clearDates} style={styles.flex} />
            </View>
          </View>

          <GroupLabel
            right={
              <Text style={styles.count}>
                {filtered ? `${rows.length} of ${data.total_stored}` : `${rows.length} backups`}
              </Text>
            }
          >
            Snapshots
          </GroupLabel>

          {rows.length === 0 ? (
            <EmptyView
              icon="cloud-upload-outline"
              title={filtered ? 'Nothing in this range' : 'No backups yet'}
              hint={
                filtered
                  ? 'No backup falls between those dates. Clear the range to see them all.'
                  : `The nightly snapshot runs at midnight (${data.system.timezone}). Tap Induce Backup to take one now.`
              }
            />
          ) : (
            rows.map((row) => (
              <DataCard
                key={row.stamp}
                title={row.label}
                subtitle={row.kind === 'induced' ? 'Induced backup' : 'Nightly backup'}
                badge={row.kind === 'induced' ? 'induced' : 'nightly'}
                badgeTone={row.kind === 'induced' ? 'gold' : 'muted'}
                lines={[
                  `${row.rows_count.toLocaleString()} rows · ${row.tables_count} tables`,
                  `Schema ${humanBytes(row.schema_bytes)} · Seed ${humanBytes(row.seed_bytes)}`,
                  row.seconds !== null ? `Took ${row.seconds.toFixed(1)}s` : null,
                  row.created_at ? `Filed ${new Date(row.created_at).toLocaleString()}` : null,
                ]}
              >
                <View style={styles.actions}>
                  <OutlineButton
                    label="Schema"
                    icon="document-text-outline"
                    onPress={() => open(row.schema_url)}
                    style={styles.flex}
                  />
                  <OutlineButton
                    label="Seed"
                    icon="document-text-outline"
                    onPress={() => open(row.seed_url)}
                    style={styles.flex}
                  />
                  <OutlineButton
                    label="Delete"
                    icon="trash-outline"
                    onPress={() => setConfirmDelete(row.stamp)}
                    style={styles.flex}
                  />
                </View>
              </DataCard>
            ))
          )}

          <Text style={styles.footnote}>
            Stored under {data.system.folder}/ — nightly at backup_&lt;date&gt;/, induced at induced/backup_&lt;date&gt;_&lt;time&gt;/.
            To restore one, run its schema file first, then the seed.
          </Text>
        </>
      ) : null}

      <ConfirmDialog
        visible={confirmInduce}
        title="Induce a backup now?"
        message="Every table is dumped and filed in Cloudinary as an induced backup, separate from the nightly one, so this never replaces it."
        confirmLabel="Take backup"
        loading={inducing}
        onConfirm={induce}
        onCancel={() => setConfirmInduce(false)}
      />

      <ConfirmDialog
        visible={confirmDelete !== null}
        title="Delete this backup?"
        message={`Both SQL files are removed from Cloudinary and the record is removed from the database. This cannot be undone.${
          confirmDelete ? `\n\n${confirmDelete}` : ''
        }`}
        confirmLabel="Delete"
        danger
        loading={deleting !== null}
        onConfirm={() => confirmDelete && remove(confirmDelete)}
        onCancel={() => setConfirmDelete(null)}
      />
    </AdminPage>
  );
}

function humanBytes(bytes: number): string {
  if (!bytes || bytes <= 0) return '—';
  if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
  if (bytes >= 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${bytes} B`;
}

function isDate(value: string): boolean {
  return /^\d{4}-\d{2}-\d{2}$/.test(value.trim());
}

function open(url: string) {
  if (url) Linking.openURL(url).catch(() => undefined);
}

const styles = StyleSheet.create({
  filterCard: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    padding: 12,
    gap: 8,
  },
  filterLabel: {
    color: COLORS.textSecondary,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 1.4,
    textTransform: 'uppercase',
  },
  filterRow: { flexDirection: 'row', gap: 8 },
  input: {
    flex: 1,
    height: 44,
    paddingHorizontal: 12,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surfaceHigh,
    color: COLORS.text,
    fontSize: 14,
  },
  flex: { flex: 1 },
  actions: { flexDirection: 'row', gap: 8, marginTop: 2 },
  count: { color: COLORS.textMuted, fontSize: 12 },
  footnote: { color: COLORS.textMuted, fontSize: 11.5, lineHeight: 17, marginTop: 4 },
});
