<?php

namespace App\Services;

use Carbon\Carbon;

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

    /** An order waiting less than this is still fresh, not yet a problem. */
    public const WAIT_WARN_MINUTES = 10;

    /** An order waiting this long is called out as late on a monitor view. */
    public const WAIT_LATE_MINUTES = 30;

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

        return $this->staffNames(array_keys($ids));
    }

    /**
     * Display names for the given staff ids, keyed by id.
     */
    public function staffNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(fn($id) => (string) $id, $ids),
            fn($id) => $id !== ''
        )));

        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach ($this->supabase->query('users', [
            'select' => 'id,name',
            'id' => 'in.(' . implode(',', $ids) . ')',
            'limit' => count($ids),
        ]) as $user) {
            $names[(string) $user['id']] = $user['name'] ?? 'Staff';
        }

        return $names;
    }

    /**
     * When the order entered the state it is in right now.
     *
     * A pending order is measured from created_at, a picked one from the moment
     * it was claimed and a served one from when it was handed over. Measuring
     * everything from created_at would make a well-run order look slow purely
     * because it took a while to serve.
     */
    public function waitingSince($order): ?Carbon
    {
        $row = (array) $order;

        $column = match ($row['status'] ?? 'pending') {
            'served' => 'served_at',
            'picked' => 'assigned_at',
            default => 'created_at',
        };

        // A row written before a timestamp existed is still measured from when
        // it arrived rather than showing nothing at all.
        return $this->toCarbon($row[$column] ?? null) ?? $this->toCarbon($row['created_at'] ?? null);
    }

    /**
     * Add the waiting fields a monitor view needs to each order:
     * waiting_since, waiting_minutes, waiting_label and waiting_tone.
     */
    public function decorateWaitingTimes($orders)
    {
        return $orders->map(function ($order) {
            $since = $this->waitingSince($order);
            $minutes = $since === null ? null : (int) abs($since->diffInMinutes(now()));

            $order->waiting_since = $since;
            $order->waiting_minutes = $minutes;
            $order->waiting_label = $this->humanDuration($minutes);
            $order->waiting_tone = $this->waitTone($minutes);

            return $order;
        });
    }

    /**
     * A wait written the way it is said out loud: 45m, 2h 10m, 1d 4h.
     */
    public function humanDuration(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        if ($minutes < 1) {
            return 'just now';
        }

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        if ($minutes < 1440) {
            return intdiv($minutes, 60) . 'h ' . ($minutes % 60) . 'm';
        }

        return intdiv($minutes, 1440) . 'd ' . intdiv($minutes % 1440, 60) . 'h';
    }

    /**
     * How the unclaimed queue at this branch is doing.
     *
     * Loaded apart from the active tab so the queue can be watched from any
     * tab, including while looking at orders that are already completed.
     */
    public function pendingWatch(?int $branchId): array
    {
        $params = [
            'select' => 'created_at',
            'status' => 'eq.pending',
            'order' => 'created_at.asc',
            'limit' => 500,
        ];
        if ($branchId !== null) {
            $params['branch_id'] = "eq.{$branchId}";
        }

        $rows = $this->supabase->query('orders', $params);

        $waits = [];
        foreach ($rows as $row) {
            $minutes = $this->minutesSince($row['created_at'] ?? null);
            if ($minutes !== null) {
                $waits[] = $minutes;
            }
        }

        $longest = $waits === [] ? null : max($waits);

        return [
            'count' => count($rows),
            'longest_minutes' => $longest,
            'longest_label' => $this->humanDuration($longest),
            'late_count' => count(array_filter($waits, fn($m) => $m >= self::WAIT_LATE_MINUTES)),
        ];
    }

    /**
     * Who is carrying the open work at this branch.
     *
     * One pass over the branch grouped by whoever picked each order, so a
     * supervisor can see who has picked what and who has been holding on to an
     * order the longest. Pending orders are left out on purpose: they belong to
     * nobody until somebody claims them, so counting them here would blame the
     * queue on a staff member.
     */
    public function teamActivity(?int $branchId)
    {
        $params = [
            'select' => 'status,assigned_to,cashier_id,created_at,assigned_at',
            'limit' => 1000,
        ];
        if ($branchId !== null) {
            $params['branch_id'] = "eq.{$branchId}";
        }

        $team = [];
        $ids = [];

        foreach ($this->supabase->query('orders', $params) as $row) {
            $status = $row['status'] ?? 'pending';

            if ($status === 'pending') {
                continue;
            }

            $staffId = $row['assigned_to'] ?? ($row['cashier_id'] ?? null);

            if (empty($staffId)) {
                continue;
            }

            $key = (string) $staffId;
            $ids[$key] = true;

            $team[$key] ??= [
                'id' => $key,
                'name' => 'Staff',
                'open' => 0,
                'served' => 0,
                'longest_open_minutes' => null,
            ];

            if ($status === 'served') {
                $team[$key]['served']++;
                continue;
            }

            $team[$key]['open']++;

            $held = $this->minutesSince($row['assigned_at'] ?? $row['created_at'] ?? null);

            if ($held !== null && ($team[$key]['longest_open_minutes'] === null || $held > $team[$key]['longest_open_minutes'])) {
                $team[$key]['longest_open_minutes'] = $held;
            }
        }

        $names = $this->staffNames(array_keys($ids));

        return collect($team)
            ->map(function ($member) use ($names) {
                $member['name'] = $names[$member['id']] ?? 'Staff';
                $member['longest_open_label'] = $this->humanDuration($member['longest_open_minutes']);
                $member['longest_open_tone'] = $this->waitTone($member['longest_open_minutes']);

                return (object) $member;
            })
            ->sortByDesc(fn($member) => [$member->open, $member->longest_open_minutes ?? -1])
            ->values();
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
     * Fresh, attention or late. Served work is never late: it is finished.
     */
    private function waitTone(?int $minutes): string
    {
        if ($minutes === null) {
            return 'unknown';
        }

        if ($minutes >= self::WAIT_LATE_MINUTES) {
            return 'late';
        }

        return $minutes >= self::WAIT_WARN_MINUTES ? 'warn' : 'ok';
    }

    /**
     * Whole minutes between a stored timestamp and now, or null when the
     * timestamp is missing or unreadable.
     */
    private function minutesSince($value): ?int
    {
        $moment = $this->toCarbon($value);

        return $moment === null ? null : (int) abs($moment->diffInMinutes(now()));
    }

    private function toCarbon($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return $value instanceof Carbon ? $value : Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
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
