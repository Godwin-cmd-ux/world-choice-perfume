<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * JSON twin of SuperAdmin\NotificationController — the admin_notifications
 * list with type/date filters, mark-one-read and mark-all-read. The website's
 * "generate report" is a printable view of the same rows; the API returns the
 * report rows so the app can render them natively.
 */
class AdminNotificationController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 200,
        ];

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $notifications = $this->fetch($params, $request->date_from, $request->date_to);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => count(array_filter($notifications, fn ($n) => ! ($n['is_read'] ?? false))),
        ]);
    }

    public function report(Request $request)
    {
        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 500,
        ];

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        return response()->json([
            'notifications' => $this->fetch($params, $request->date_from, $request->date_to),
            'type' => (string) $request->type,
            'date_from' => (string) $request->date_from,
            'date_to' => (string) $request->date_to,
        ]);
    }

    public function markRead($notificationId)
    {
        $this->supabase->update('admin_notifications', [
            'is_read' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $notificationId]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllRead()
    {
        $unread = $this->supabase->query('admin_notifications', [
            'is_read' => 'eq.false',
            'select' => 'id',
        ]);

        foreach ($unread as $n) {
            $this->supabase->update('admin_notifications', [
                'is_read' => true,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $n['id']]);
        }

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * admin_notifications has no foreign keys, so PostgREST embedded selects
     * fail (PGRST200) — names are resolved with separate IN queries, exactly
     * as the website controller does.
     */
    private function fetch(array $params, $dateFrom = null, $dateTo = null): array
    {
        $notifications = $this->supabase->query('admin_notifications', $params);

        if ($dateFrom) {
            $notifications = array_filter($notifications, fn ($n) => substr($n['created_at'] ?? '', 0, 10) >= $dateFrom);
        }
        if ($dateTo) {
            $notifications = array_filter($notifications, fn ($n) => substr($n['created_at'] ?? '', 0, 10) <= $dateTo);
        }

        $notifications = array_values($notifications);

        $branchIds = array_values(array_unique(array_filter(array_column($notifications, 'branch_id'))));
        $userIds = array_values(array_unique(array_filter(array_column($notifications, 'user_id'))));

        $branchNames = [];
        if ($branchIds) {
            foreach ($this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.('.implode(',', $branchIds).')',
            ]) as $b) {
                $branchNames[$b['id']] = $b['name'] ?? null;
            }
        }

        $userNames = [];
        if ($userIds) {
            foreach ($this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.('.implode(',', $userIds).')',
            ]) as $u) {
                $userNames[$u['id']] = $u['name'] ?? null;
            }
        }

        return array_map(function ($n) use ($branchNames, $userNames) {
            $n['branch_name'] = $branchNames[$n['branch_id'] ?? null] ?? null;
            $n['user_name'] = $userNames[$n['user_id'] ?? null] ?? null;

            return $n;
        }, $notifications);
    }
}
