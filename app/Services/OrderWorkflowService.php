<?php

namespace App\Services;

/**
 * The staff order workflow shared by every role that owns an Orders page.
 *
 * An order has exactly three statuses and only ever moves forward:
 *
 *     pending -> picked -> served
 *
 * "picked" is an exclusive claim. It is written as a compare-and-set (a PATCH
 * filtered on id AND status = 'pending'), so if two staff members click Pick on
 * the same order at the same moment exactly one PATCH still matches a row and
 * the other is told the order has gone. The rule that an order cannot be worked
 * by two people is therefore enforced by the write itself, not by anything the
 * browser does.
 *
 * Every role except Super Admin is isolated: they see all unclaimed pending
 * orders in their branch, but picked and served orders only when they are the
 * one who picked it (assigned_to, falling back to cashier_id for orders that
 * predate that column). Super Admin passes $isolated = false and a null
 * $branchId to monitor every branch and see who picked what.
 *
 * Every status change requires a note, stored in order_notes against the staff
 * member who made the change.
 */
class OrderWorkflowService
{
    /** Tab slug => the single status that tab shows. */
    public const TABS = [
        'pending' => 'pending',
        'progress' => 'picked',
        'completed' => 'served',
    ];

    /** Tab slug => the label shown on the tab. */
    public const TAB_LABELS = [
        'pending' => 'Pending Orders',
        'progress' => 'My Orders On Progress',
        'completed' => 'My Completed Orders',
    ];

    /** The only status changes the system allows. */
    public const TRANSITIONS = [
        'pending' => ['picked'],
        'picked' => ['served'],
        'served' => [],
    ];

    /** Longest personal label we will store. */
    public const LABEL_MAX = 120;

    public function __construct(private SupabaseService $supabase)
    {
    }

    /**
     * The validation rules a status change has to satisfy. The note is not
     * optional: a status change without one is rejected before any write.
     */
    public function statusChangeRules(): array
    {
        // The allowed *targets* are the values of TRANSITIONS, not its keys.
        // Using the keys would let "pending" through, which would silently
        // re-open an order that has already been picked or served.
        $targets = [];
        foreach (self::TRANSITIONS as $allowed) {
            foreach ($allowed as $status) {
                $targets[$status] = true;
            }
        }

        return [
            'status' => 'required|in:' . implode(',', array_keys($targets)),
            'note' => 'required|string|max:2000',
        ];
    }

    /**
     * Clamp an untrusted tab slug to a real tab, defaulting to Pending.
     */
    public function resolveTab(?string $slug): string
    {
        return is_string($slug) && array_key_exists($slug, self::TABS) ? $slug : 'pending';
    }

    public function statusFor(string $tab): string
    {
        return self::TABS[$tab] ?? 'pending';
    }

    /**
     * Whether the order sits with this staff member.
     *
     * assigned_to is the canonical picker column, but orders created before
     * that column existed only ever had cashier_id written, so both have to be
     * checked or older orders would look unowned.
     */
    public function isOwnedBy(array $order, $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        foreach (['assigned_to', 'cashier_id'] as $column) {
            if (isset($order[$column]) && (string) $order[$column] === (string) $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * How many orders sit behind each tab.
     *
     * One pass over the branch gives all three counts without a round trip per
     * tab. $isolated is false for Super Admin, who counts the whole branch.
     */
    public function counts(?int $branchId, $userId, bool $isolated = true): array
    {
        $counts = ['pending' => 0, 'progress' => 0, 'completed' => 0];

        $params = [
            'select' => 'status,assigned_to,cashier_id',
            'limit' => 1000,
        ];
        if ($branchId !== null) {
            $params['branch_id'] = "eq.{$branchId}";
        }

        foreach ($this->supabase->query('orders', $params) as $row) {
            $status = $row['status'] ?? 'pending';

            if ($status === 'pending') {
                $counts['pending']++;
            } elseif (in_array($status, ['picked', 'served'], true) && (!$isolated || $this->isOwnedBy($row, $userId))) {
                $counts[$status === 'picked' ? 'progress' : 'completed']++;
            }
        }

        return $counts;
    }

    /**
     * The orders shown on one tab.
     *
     * $isolated is false only for Super Admin, who sees every order in the
     * branch regardless of who picked it.
     */
    public function tabRows(?int $branchId, string $tab, $userId, bool $isolated = true, ?string $search = null)
    {
        $status = $this->statusFor($tab);

        $params = [
            'select' => $this->listSelect(),
            'status' => "eq.{$status}",
            'order' => 'created_at.desc',
            'limit' => 200,
        ];
        if ($branchId !== null) {
            $params['branch_id'] = "eq.{$branchId}";
        }

        $rows = $this->supabase->query('orders', $params);

        // Pending work belongs to whoever gets there first, so it is not
        // filtered. Picked and served work belongs to the staff member who
        // picked it, so a colleague's order never reaches this list.
        if ($isolated && $status !== 'pending') {
            $rows = array_values(array_filter($rows, fn($o) => $this->isOwnedBy($o, $userId)));
        }

        $orders = collect($rows)->map(fn($o) => $this->cast($o));

        return $this->applySearch($orders, $search);
    }

    /**
     * Display names for the staff members who picked the given orders.
     *
     * Fetched as its own lookup instead of embedded on the order query: an
     * embed has to name the assigned_to foreign key exactly, and getting that
     * name wrong fails the whole Orders page for every role.
     */
    public function pickerNames($orders): array
    {
        $ids = [];

        foreach ($orders as $order) {
            $row = (array) $order;
            if (!empty($row['assigned_to'])) {
                $ids[(string) $row['assigned_to']] = true;
            }
        }

        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach ($this->supabase->query('users', [
            'select' => 'id,name',
            'id' => 'in.(' . implode(',', array_keys($ids)) . ')',
            'limit' => count($ids),
        ]) as $user) {
            $names[(string) $user['id']] = $user['name'] ?? 'Staff';
        }

        return $names;
    }

    /**
     * Load one order for a detail page, applying the same isolation rule as the
     * tabs so a staff member cannot reach a colleague's order by guessing an id.
     */
    public function findForShow(int $orderId, ?int $branchId, $userId, bool $isolated = true): ?object
    {
        $order = $this->supabase->find('orders', $orderId, $this->showSelect());

        if (!$order || ($branchId !== null && (int) $order['branch_id'] !== $branchId)) {
            return null;
        }

        if ($isolated && ($order['status'] ?? 'pending') !== 'pending' && !$this->isOwnedBy($order, $userId)) {
            return null;
        }

        return $this->cast($order);
    }

    /**
     * Claim a pending order for this staff member.
     *
     * @return array{ok: bool, message: string}
     */
    public function pick(int $orderId, ?int $branchId, $userId, string $note): array
    {
        $order = $this->supabase->find('orders', $orderId);

        if (!$order || ($branchId !== null && (int) $order['branch_id'] !== $branchId)) {
            return $this->fail('Order not found.');
        }

        $current = $order['status'] ?? 'pending';

        if (!in_array('picked', self::TRANSITIONS[$current] ?? [], true)) {
            return $this->fail($this->staleMessage($order, $current));
        }

        $now = now()->toIso8601String();

        // Compare-and-set. Only a row that is still pending can match, so only
        // one staff member can win this claim; the loser falls through to the
        // re-read below and is told the order has gone.
        $updated = $this->supabase->update('orders', [
            'status' => 'picked',
            'assigned_to' => $userId,
            'cashier_id' => $userId,
            'assigned_at' => $now,
            'updated_at' => $now,
        ], ['id' => $orderId, 'status' => 'pending']);

        if (empty($updated)) {
            return $this->fail($this->staleMessage($this->supabase->find('orders', $orderId) ?: [], null));
        }

        $this->recordNote($orderId, $note, $userId);
        $this->audit($order, 'picked', $current, $orderId, $note);

        return ['ok' => true, 'message' => 'Order picked. It is now in My Orders On Progress.'];
    }

    /**
     * Mark this staff member's picked order as served.
     *
     * @return array{ok: bool, message: string}
     */
    public function serve(int $orderId, ?int $branchId, $userId, string $note): array
    {
        $order = $this->supabase->find('orders', $orderId);

        if (!$order || ($branchId !== null && (int) $order['branch_id'] !== $branchId)) {
            return $this->fail('Order not found.');
        }

        if (!$this->isOwnedBy($order, $userId)) {
            return $this->fail('This order was picked by another staff member. Only they can serve it.');
        }

        $current = $order['status'] ?? 'pending';

        if (!in_array('served', self::TRANSITIONS[$current] ?? [], true)) {
            return $this->fail($this->staleMessage($order, $current));
        }

        $now = now()->toIso8601String();

        // assigned_to is deliberately left untouched: the picker stays on the
        // order after serving so My Completed Orders can still find it.
        $updated = $this->supabase->update('orders', [
            'status' => 'served',
            'served_at' => $now,
            'completed_at' => $now,
            'updated_at' => $now,
        ], ['id' => $orderId, 'status' => 'picked']);

        if (empty($updated)) {
            return $this->fail($this->staleMessage($this->supabase->find('orders', $orderId) ?: [], null));
        }

        $this->recordNote($orderId, $note, $userId);
        $this->audit($order, 'served', $current, $orderId, $note);

        return ['ok' => true, 'message' => 'Order served. It is now in My Completed Orders.'];
    }

    /**
     * Save, edit or clear the personal label the picking staff member gave this
     * order.
     *
     * Ownership is checked here on the server, so changing the order id in the
     * request does not let one staff member label a colleague's order. An empty
     * value clears the label. Clearing is allowed once served, so a label can
     * be corrected after the order has moved to My Completed Orders.
     *
     * @return array{ok: bool, message: string}
     */
    public function savePersonalName(int $orderId, ?int $branchId, $userId, ?string $name, bool $isSuperAdmin = false): array
    {
        $order = $this->supabase->find('orders', $orderId);

        if (!$order || ($branchId !== null && (int) $order['branch_id'] !== $branchId)) {
            return $this->fail('Order not found.');
        }

        if (!$isSuperAdmin && !$this->isOwnedBy($order, $userId)) {
            return $this->fail('You can only name orders that you picked.');
        }

        $name = trim((string) $name);
        $name = $name === '' ? null : $name;

        if ($name !== null && mb_strlen($name) > self::LABEL_MAX) {
            return $this->fail('The personal name must be ' . self::LABEL_MAX . ' characters or fewer.');
        }

        $updated = $this->supabase->update('orders', [
            'personal_order_name' => $name,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $orderId]);

        if (empty($updated)) {
            $reason = $this->supabase->lastErrorMessage();

            return $this->fail('Could not save the personal name. ' . ($reason ?: 'Please try again.'));
        }

        return [
            'ok' => true,
            'message' => $name === null ? 'Personal name cleared.' : 'Personal name saved.',
        ];
    }

    /**
     * Turn a raw PostgREST row into the object shape the views already use.
     */
    public function cast(array $row): object
    {
        foreach (['cashier', 'customer', 'branch'] as $relation) {
            if (isset($row[$relation]) && is_array($row[$relation])) {
                $row[$relation] = (object) $row[$relation];
            }
        }

        if (isset($row['items'])) {
            $row['items'] = collect($row['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) {
                    $item['product'] = (object) $item['product'];
                }

                return (object) $item;
            });
        }

        if (isset($row['notes'])) {
            $row['notes'] = collect($row['notes'])
                ->sortBy('created_at')
                ->map(fn($n) => (object) $n)
                ->values();
        }

        return (object) $row;
    }

    /**
     * Filter the rows a tab already returned by order number or personal name.
     *
     * Done in memory on the fetched rows so it inherits that tab's ownership
     * filter and can never surface another staff member's orders. The official
     * order number and the personal label are both searchable.
     */
    private function applySearch($orders, ?string $search)
    {
        $needle = mb_strtolower(trim((string) $search));

        if ($needle === '') {
            return $orders;
        }

        return $orders->filter(function ($order) use ($needle) {
            $number = mb_strtolower((string) ($order->order_number ?? ''));
            $label = mb_strtolower(trim((string) ($order->personal_order_name ?? '')));

            return str_contains($number, $needle) || ($label !== '' && str_contains($label, $needle));
        })->values();
    }

    /**
     * Columns for the tab list.
     *
     * The leading * carries personal_order_name once its migration has been
     * run, and is simply absent before that, so the same select works either
     * way. Naming the column explicitly would make PostgREST reject the whole
     * query on an installation that has not run the migration yet.
     */
    private function listSelect(): string
    {
        return '*, customer:customers(id,name,phone), items:order_items(*, product:products(id,name,brand))';
    }

    private function showSelect(): string
    {
        return '*, cashier:users!orders_cashier_id_fkey(id,name), customer:customers(*), items:order_items(*, product:products(id,name,brand)), branch:branches(id,name,address), notes:order_notes(*)';
    }

    /**
     * Store the note that every status change is required to carry.
     */
    private function recordNote(int $orderId, string $note, $userId): void
    {
        $now = now()->toIso8601String();

        $this->supabase->insert('order_notes', [
            'order_id' => $orderId,
            'note' => $note,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function audit(array $order, string $next, string $previous, int $orderId, string $note): void
    {
        (new \App\Services\AuditService())->recordCriticalAction(
            'order_status_changed',
            'order_status_changed',
            'Order Status Changed',
            "Order {$order['order_number']} status changed to {$next}.",
            [
                'order_id' => $orderId,
                'order_number' => $order['order_number'],
                'total' => $order['total'] ?? null,
                'note' => $note,
            ],
            'orders',
            (string) $orderId,
            ['status' => $previous],
            ['status' => $next]
        );
    }

    /**
     * What to tell the staff member when a claim lost the race, or when the
     * order has already moved past the requested status.
     */
    private function staleMessage(array $order, ?string $knownStatus): string
    {
        $status = $knownStatus ?? ($order['status'] ?? null);

        if ($status === 'picked') {
            return 'This order has already been picked by another staff member.';
        }

        if ($status === 'served') {
            return 'This order has already been served.';
        }

        if ($status === null) {
            return 'This order is no longer available. Please refresh and try again.';
        }

        return 'This order is now "' . $status . '". Please refresh and try again.';
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}
