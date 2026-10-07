import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../components/adminkit';
import { EmptyView, ErrorView, LoadingView, OutlineButton } from '../../components/ui';
import { errorMessage } from '../../lib/api';
import {
  fetchNotifications,
  fetchNotificationsReport,
  markAllNotificationsRead,
  markNotificationRead,
} from '../../lib/adminApi';
import { COLORS } from '../../lib/theme';

const TYPES = [
  { key: '', label: 'All types' },
  { key: 'approval', label: 'Approvals' },
  { key: 'order', label: 'Orders' },
  { key: 'stock', label: 'Stock' },
  { key: 'system', label: 'System' },
];

/**
 * Notifications — the mobile twin of super-admin/notifications/index:
 * admin_notifications with the same type filter, mark-one-read and
 * mark-all-read, plus the report filter set (type + date range) which the
 * website's "generate report" page uses — the app renders the report rows
 * natively instead of printing.
 */
export default function Notifications() {
  const [type, setType] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  // "Inbox" = the website's notifications index (200 rows, read actions);
  // "Report" = its Generate Report page (same filters, up to 500 rows).
  const [mode, setMode] = useState<'inbox' | 'report'>('inbox');

  const { data, error, loading, refreshing, sessionExpired, reload, refresh } = useAsyncData(
    () =>
      mode === 'report'
        ? fetchNotificationsReport({
            type: type || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
          }).then((r) => ({ notifications: r.notifications, unread_count: 0 }))
        : fetchNotifications({
            type: type || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
          }),
    [type, dateFrom, dateTo, mode],
  );

  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const markOne = async (id: string) => {
    setBusy(true);
    setActionError(null);
    try {
      await markNotificationRead(id);
      await reload();
    } catch (e) {
      setActionError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  const markAll = async () => {
    setBusy(true);
    setActionError(null);
    try {
      const res = await markAllNotificationsRead();
      setNotice(res.message);
      await reload();
    } catch (e) {
      setActionError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  const rows = data?.notifications ?? [];

  return (
    <AdminPage
      title="Notifications"
      onBack={() => router.back()}
      refreshing={refreshing}
      onRefresh={refresh}
      action={
        mode === 'inbox' && data && data.unread_count > 0 ? (
          <OutlineButton label={`Mark all (${data.unread_count})`} onPress={markAll} style={{ minHeight: 38, paddingHorizontal: 12 }} />
        ) : undefined
      }
    >
      {loading ? <LoadingView label="Loading notifications…" /> : null}
      {sessionExpired ? <Banner kind="error" message="Your session has expired. Please sign in again." /> : null}
      {error && !data ? <ErrorView message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {notice ? <Banner kind="success" message={notice} /> : null}
          {actionError ? <Banner kind="error" message={actionError} /> : null}
          {error ? <Banner kind="error" message={error} /> : null}

          <ChipRow>
            <Chip label="Inbox view" active={mode === 'inbox'} onPress={() => setMode('inbox')} />
            <Chip label="Report view (500)" active={mode === 'report'} onPress={() => setMode('report')} />
          </ChipRow>
          <ChipRow>
            {TYPES.map((t) => (
              <Chip key={t.key || 'all'} label={t.label} active={type === t.key} onPress={() => setType(t.key)} />
            ))}
          </ChipRow>
          <ChipRow>
            <Chip label="From: today" active={dateFrom === todayStr()} onPress={() => setDateFrom(dateFrom === todayStr() ? '' : todayStr())} />
            <Chip label="From: month start" active={dateFrom === monthStart()} onPress={() => setDateFrom(dateFrom === monthStart() ? '' : monthStart())} />
            <Chip label="To: today" active={dateTo === todayStr()} onPress={() => setDateTo(dateTo === todayStr() ? '' : todayStr())} />
          </ChipRow>

          <GroupLabel right={<Text style={styles.count}>{rows.length} entries</Text>}>
            {mode === 'report' ? 'Report listing' : 'Admin alerts'}
          </GroupLabel>

          {rows.length === 0 ? (
            <EmptyView icon="notifications-outline" title="No notifications" hint="Nothing matches this filter." />
          ) : (
            rows.map((n) => (
              <DataCard
                key={String(n.id)}
                title={n.title ?? 'Notification'}
                badge={n.is_read ? undefined : 'New'}
                badgeTone="gold"
                lines={[
                  n.message,
                  n.user_name ? `For: ${n.user_name}` : null,
                  n.branch_name ? `Branch: ${n.branch_name}` : null,
                  n.type ? `Type: ${n.type}` : null,
                  n.created_at ? new Date(n.created_at).toLocaleString() : null,
                ]}
                onPress={mode === 'inbox' && !n.is_read ? () => markOne(String(n.id)) : undefined}
              />
            ))
          )}
        </>
      ) : null}
      <BusyOverlay visible={busy} label="Saving…" />
    </AdminPage>
  );
}

function monthStart(): string {
  const d = new Date();
  return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10);
}
function todayStr(): string {
  return new Date().toISOString().slice(0, 10);
}

const styles = StyleSheet.create({
  count: { color: COLORS.textMuted, fontSize: 12 },
});
