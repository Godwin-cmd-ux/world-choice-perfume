import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  AppState,
  KeyboardAvoidingView,
  Platform,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '../../components/ScreenHeader';
import { EmptyView, GoldButton } from '../../components/ui';
import { errorMessage, trackOrders, type TrackOrder, type TrackPayload } from '../../lib/api';
import { formatDateTime, formatMoney } from '../../lib/format';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * TRACK ORDERS — the mobile version of the website's customer order tracking
 * (POST /orders/track → Customer\OrderController::trackByPhone). The phone
 * lookup, order list, statuses and timeline all come from the backend; no
 * tracking information is invented in the app.
 */
type Phase = 'idle' | 'loading' | 'error' | 'done';

/**
 * While a phone number is being tracked the screen quietly re-asks the server
 * for its orders, so a status change made by staff shows up without the
 * customer having to leave and re-open the tab. Manual pull-to-refresh is the
 * immediate version of the same request.
 */
const REFRESH_INTERVAL_MS = 20_000;

interface StatusBadge {
  label: string;
  fg: string;
  bg: string;
  border: string;
}

function statusBadge(status?: string): StatusBadge {
  switch ((status ?? '').toLowerCase()) {
    case 'pending':
      return { label: 'Pending', fg: COLORS.warning, bg: 'rgba(250, 204, 21, 0.15)', border: 'rgba(250, 204, 21, 0.30)' };
    case 'picked':
      return { label: 'Picked', fg: COLORS.info, bg: 'rgba(96, 165, 250, 0.15)', border: 'rgba(96, 165, 250, 0.30)' };
    case 'served':
      return { label: 'Served', fg: COLORS.success, bg: 'rgba(52, 211, 153, 0.15)', border: 'rgba(52, 211, 153, 0.30)' };
    default: {
      const raw = (status ?? '').trim();
      const label = raw ? raw.charAt(0).toUpperCase() + raw.slice(1) : 'Status unknown';
      return { label, fg: COLORS.textSecondary, bg: 'rgba(156, 163, 175, 0.15)', border: 'rgba(156, 163, 175, 0.30)' };
    }
  }
}

function OrderCard({ order }: { order: TrackOrder }) {
  const badge = statusBadge(order.status);
  const items = order.items ?? [];
  const timeline = order.timeline ?? [];
  const updates = order.updates ?? [];

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderHead}>
        <Text style={styles.orderNumber}>{order.order_number}</Text>
        <View style={[styles.badge, { backgroundColor: badge.bg, borderColor: badge.border }]}>
          <Text style={[styles.badgeText, { color: badge.fg }]}>{badge.label}</Text>
        </View>
      </View>

      <View style={styles.orderMeta}>
        {order.branch?.name ? (
          <View style={styles.metaRow}>
            <Ionicons name="storefront-outline" size={13} color={COLORS.textMuted} />
            <Text style={styles.metaText}>{order.branch.name}</Text>
          </View>
        ) : null}
        {order.created_at ? (
          <View style={styles.metaRow}>
            <Ionicons name="time-outline" size={13} color={COLORS.textMuted} />
            <Text style={styles.metaText}>{formatDateTime(order.created_at)}</Text>
          </View>
        ) : null}
        {order.total != null ? (
          <View style={styles.metaRow}>
            <Ionicons name="pricetag-outline" size={13} color={COLORS.textMuted} />
            <Text style={styles.metaText}>{formatMoney(order.total)}</Text>
          </View>
        ) : null}
      </View>

      {timeline.length > 0 ? (
        <View style={styles.timeline}>
          {timeline.map((step, index) => (
            <View key={`${step.label}-${index}`} style={styles.timelineRow}>
              <View style={styles.timelineDotCol}>
                <View style={[styles.timelineDot, index === timeline.length - 1 && styles.timelineDotLast]} />
              </View>
              <View style={styles.timelineBody}>
                <Text style={styles.timelineLabel}>{step.label}</Text>
                <Text style={styles.timelineDate}>{formatDateTime(step.at)}</Text>
              </View>
            </View>
          ))}
        </View>
      ) : null}

      {updates.length > 0 ? (
        <View style={styles.updates}>
          <View style={styles.updatesHead}>
            <Ionicons name="chatbubble-ellipses-outline" size={13} color={COLORS.goldBright} />
            <Text style={styles.updatesTitle}>Order Updates</Text>
          </View>
          {updates.map((update, index) => (
            <View key={`${update.created_at ?? 'update'}-${index}`} style={styles.updateCard}>
              <Text style={styles.updateText}>{update.note}</Text>
              {update.author || update.created_at ? (
                <View style={styles.updateMeta}>
                  {update.author ? <Text style={styles.updateAuthor}>{update.author}</Text> : null}
                  {update.created_at ? <Text style={styles.updateDate}>{formatDateTime(update.created_at)}</Text> : null}
                </View>
              ) : null}
            </View>
          ))}
        </View>
      ) : null}

      {items.length > 0 ? (
        <View style={styles.items}>
          <Text style={styles.itemsTitle}>Items</Text>
          {items.map((item, index) => (
            <View key={index} style={styles.itemRow}>
              <Text style={styles.itemName} numberOfLines={2}>
                {item.product?.name ?? 'Item'}
                {item.variety_label ? <Text style={styles.itemVariety}> · {item.variety_label}</Text> : null}
                <Text style={styles.itemQty}> × {item.quantity}</Text>
              </Text>
              <Text style={styles.itemTotal}>{formatMoney(item.total)}</Text>
            </View>
          ))}
        </View>
      ) : null}
    </View>
  );
}

export default function TrackScreen() {
  const [phone, setPhone] = useState('');
  const [trackedPhone, setTrackedPhone] = useState('');
  const [phase, setPhase] = useState<Phase>('idle');
  const [error, setError] = useState('');
  const [fieldError, setFieldError] = useState('');
  const [orders, setOrders] = useState<TrackOrder[]>([]);
  const [emptyMessage, setEmptyMessage] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [lastChecked, setLastChecked] = useState('');

  const applyPayload = useCallback((payload: TrackPayload) => {
    setOrders(payload.orders ?? []);
    setEmptyMessage(payload.message ?? 'No orders found for this phone number.');
    setLastChecked(new Date().toISOString());
  }, []);

  const submit = async () => {
    const value = phone.trim();
    if (!value) {
      setFieldError('Please enter the phone number used for your order.');
      return;
    }
    setFieldError('');
    setError('');
    setPhase('loading');
    try {
      const payload = await trackOrders(value);
      setTrackedPhone(value);
      applyPayload(payload);
      setPhase('done');
    } catch (e) {
      setError(errorMessage(e));
      setPhase('error');
    }
  };

  /**
   * Re-read the tracked number. `silent` is the background poll: it never
   * blanks the results or shows a spinner, so a moment without a signal does
   * not replace an order the customer is reading with an error page.
   */
  const refreshTracked = useCallback(
    async (silent = false) => {
      if (!trackedPhone) return;

      if (!silent) setRefreshing(true);

      try {
        const payload = await trackOrders(trackedPhone);
        applyPayload(payload);
        setError('');
        setPhase('done');
      } catch (e) {
        if (!silent) setError(errorMessage(e));
      } finally {
        if (!silent) setRefreshing(false);
      }
    },
    [trackedPhone, applyPayload],
  );

  // Keep checking while the tab is actually visible. Leaving the tab stops
  // the timer; coming back starts it again with a fresh request.
  useFocusEffect(
    useCallback(() => {
      if (!trackedPhone || phase !== 'done') return undefined;

      const timer = setInterval(() => {
        refreshTracked(true);
      }, REFRESH_INTERVAL_MS);

      return () => clearInterval(timer);
    }, [trackedPhone, phase, refreshTracked]),
  );

  // A phone left in the background can be stale by the time it is picked up
  // again, so check once immediately when the app returns to the foreground.
  useEffect(() => {
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active' && trackedPhone) {
        refreshTracked(true);
      }
    });

    return () => subscription.remove();
  }, [trackedPhone, refreshTracked]);

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="Track Orders" subtitle="World Choice Perfume" />
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        keyboardVerticalOffset={90}
      >
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
          refreshControl={
            trackedPhone ? (
              <RefreshControl
                refreshing={refreshing}
                onRefresh={() => refreshTracked()}
                tintColor={COLORS.gold}
                colors={[COLORS.gold]}
              />
            ) : undefined
          }
        >
          <View style={styles.intro}>
            <Text style={styles.introTitle}>
              Track Your <Text style={styles.introTitleGold}>Order</Text>
            </Text>
            <Text style={styles.introLead}>
              Enter the phone number you used when placing your order to see its current status,
              updates, items and timeline.
            </Text>
          </View>

          <View style={styles.form}>
            <Text style={styles.label}>Phone Number</Text>
            <View style={[styles.inputWrap, fieldError ? styles.inputWrapError : null]}>
              <Ionicons name="call-outline" size={17} color={COLORS.textMuted} />
              <TextInput
                value={phone}
                onChangeText={(text) => {
                  setPhone(text);
                  if (fieldError) setFieldError('');
                }}
                placeholder="e.g. 0754 000 000"
                placeholderTextColor={COLORS.textMuted}
                style={styles.input}
                keyboardType="phone-pad"
                accessibilityLabel="Order phone number"
              />
            </View>
            {fieldError ? <Text style={styles.fieldError}>{fieldError}</Text> : null}

            <GoldButton
              label="Track Order"
              icon="cube-outline"
              onPress={submit}
              loading={phase === 'loading'}
              disabled={phase === 'loading'}
            />
          </View>

          {error ? (
            <View style={styles.errorBanner}>
              <Ionicons name="alert-circle-outline" size={18} color={COLORS.danger} />
              <Text style={styles.errorText}>{error}</Text>
            </View>
          ) : null}

          {phase === 'done' && trackedPhone ? (
            <View style={styles.liveRow}>
              <Ionicons name="sync-outline" size={13} color={COLORS.goldBright} />
              <Text style={styles.liveText}>
                Updates check automatically every {REFRESH_INTERVAL_MS / 1000}s
                {lastChecked ? ` · last checked ${formatDateTime(lastChecked)}` : ''}
              </Text>
            </View>
          ) : null}

          {phase === 'done' ? (
            orders.length === 0 ? (
              <EmptyView
                icon="cube-outline"
                title={emptyMessage}
                hint="We keep checking while this screen is open."
              />
            ) : (
              <View style={styles.results}>
                <Text style={styles.resultsTitle}>
                  {orders.length} order{orders.length === 1 ? '' : 's'} for {trackedPhone}
                </Text>
                {orders.map((order) => (
                  <OrderCard key={order.order_number} order={order} />
                ))}
              </View>
            )
          ) : null}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  flex: { flex: 1 },
  content: {
    paddingHorizontal: 20,
    paddingTop: 6,
    paddingBottom: 40,
    gap: 18,
  },
  intro: {
    gap: 6,
  },
  introTitle: {
    color: COLORS.text,
    fontSize: 25,
    fontWeight: '800',
  },
  introTitleGold: {
    color: COLORS.gold,
  },
  introLead: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 20,
  },

  form: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 10,
  },
  label: {
    color: COLORS.textSecondary,
    fontSize: 12.5,
    fontWeight: '600',
    marginBottom: -2,
  },
  inputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.bgRaised,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 12,
    height: 48,
  },
  inputWrapError: {
    borderColor: COLORS.dangerBorder,
  },
  input: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15.5,
    paddingVertical: 0,
    letterSpacing: 0.5,
  },
  fieldError: {
    color: COLORS.danger,
    fontSize: 12.5,
  },

  errorBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    backgroundColor: COLORS.dangerBg,
    borderWidth: 1,
    borderColor: COLORS.dangerBorder,
    borderRadius: RADIUS.md,
    padding: 14,
  },
  errorText: {
    color: COLORS.danger,
    fontSize: 13,
    lineHeight: 19,
    flex: 1,
  },

  liveRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: -8,
  },
  liveText: {
    color: COLORS.textMuted,
    fontSize: 11.5,
    flexShrink: 1,
  },

  results: {
    gap: 12,
  },
  resultsTitle: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '600',
  },
  orderCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 10,
  },
  orderHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  orderNumber: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
    flexShrink: 1,
  },
  badge: {
    borderRadius: RADIUS.pill,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  badgeText: {
    fontSize: 11.5,
    fontWeight: '700',
  },
  orderMeta: {
    gap: 5,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
  },
  metaText: {
    color: COLORS.textSecondary,
    fontSize: 12.5,
  },

  timeline: {
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 10,
    gap: 0,
  },
  timelineRow: {
    flexDirection: 'row',
    gap: 10,
  },
  timelineDotCol: {
    width: 14,
    alignItems: 'center',
  },
  timelineDot: {
    width: 9,
    height: 9,
    borderRadius: 5,
    backgroundColor: COLORS.gold,
    marginTop: 5,
  },
  timelineDotLast: {
    backgroundColor: COLORS.goldBright,
  },
  timelineBody: {
    flex: 1,
    paddingBottom: 12,
  },
  timelineLabel: {
    color: COLORS.text,
    fontSize: 13.5,
    fontWeight: '600',
  },
  timelineDate: {
    color: COLORS.textMuted,
    fontSize: 11.5,
    marginTop: 1,
  },

  updates: {
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 10,
    gap: 7,
  },
  updatesHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  updatesTitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  updateCard: {
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.md,
    paddingHorizontal: 11,
    paddingVertical: 9,
    gap: 4,
  },
  updateText: {
    color: COLORS.text,
    fontSize: 13,
    lineHeight: 19,
  },
  updateMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 8,
  },
  updateAuthor: {
    color: COLORS.goldBright,
    fontSize: 11,
    fontWeight: '600',
  },
  updateDate: {
    color: COLORS.textMuted,
    fontSize: 11,
  },

  items: {
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 10,
    gap: 6,
  },
  itemsTitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  itemRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 10,
  },
  itemName: {
    color: COLORS.textSecondary,
    fontSize: 13,
    flexShrink: 1,
  },
  itemVariety: {
    color: COLORS.textMuted,
  },
  itemQty: {
    color: COLORS.textMuted,
  },
  itemTotal: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '600',
  },
});
