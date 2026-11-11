<?php

namespace App\Services;

/**
 * Returned stock alerts live in admin_notifications: one row when a branch
 * rejects an item, and one when the stock manager files its lost / broken
 * report. The Super Admin clears them by opening the rejected item, which is
 * why this service owns both the unread count and the "mark this item read"
 * rule.
 */
class ReturnedStockNotifications
{
    /** Rejections are recorded under stock_transfer; reports under returned_stock. */
    private const TYPES = ['returned_stock', 'stock_transfer'];

    private const REJECTION_TITLE = 'Transfer item rejected (invalid)';

    public function __construct(private SupabaseService $supabase) {}

    /**
     * Every unread notification that belongs to a rejected transfer item.
     *
     * @return array<int, array<string, mixed>>
     */
    public function unread(): array
    {
        $rows = $this->supabase->query('admin_notifications', [
            'select' => 'id,type,title,message,data,is_read',
            'is_read' => 'eq.false',
            'type' => 'in.('.implode(',', self::TYPES).')',
            'limit' => 1000,
        ]);

        return array_values(array_filter($rows, fn ($row) => $this->isReturnedStockAlert($row)));
    }

    public function countUnread(): int
    {
        return count($this->unread());
    }

    /**
     * True when this notification is the alert for the given item.
     */
    public function isForItem(array $notification, array $item, array $transfer): bool
    {
        if (! $this->isReturnedStockAlert($notification)) {
            return false;
        }

        $data = $this->data($notification);
        $itemId = (int) ($item['id'] ?? 0);

        // Newer rows carry the exact item id.
        if (! empty($data['item_id'])) {
            return (int) $data['item_id'] === $itemId;
        }

        // Older rejection rows only have the message text, so fall back to
        // matching the transfer number recorded on it.
        $transferNumber = trim((string) ($transfer['transfer_number'] ?? ''));

        return $transferNumber !== ''
            && str_contains((string) ($notification['message'] ?? ''), $transferNumber);
    }

    /**
     * Mark the alerts for one rejected item as read. Returns how many rows
     * changed, which is exactly how much the sidebar badge should drop.
     */
    public function markReadForItem(array $item, array $transfer): int
    {
        $marked = 0;
        $now = now()->toIso8601String();

        foreach ($this->unread() as $notification) {
            if (! $this->isForItem($notification, $item, $transfer)) {
                continue;
            }

            $updated = $this->supabase->update('admin_notifications', [
                'is_read' => true,
                'updated_at' => $now,
            ], ['id' => $notification['id']]);

            if (! empty($updated)) {
                $marked++;
            }
        }

        return $marked;
    }

    private function isReturnedStockAlert(array $notification): bool
    {
        $type = (string) ($notification['type'] ?? '');

        if (! in_array($type, self::TYPES, true)) {
            return false;
        }

        if ($type === 'returned_stock') {
            return true;
        }

        return str_starts_with((string) ($notification['title'] ?? ''), self::REJECTION_TITLE);
    }

    /**
     * The notification payload may be decoded by PostgREST or still be a JSON
     * string depending on the driver.
     */
    private function data(array $notification): array
    {
        $data = $notification['data'] ?? [];

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($data) ? $data : [];
    }
}
