<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class NotificationController extends Controller
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

        $notifications = $this->fetchNotifications($params, $request->date_from, $request->date_to);

        return view('super-admin.notifications.index', ['notifications' => $notifications]);
    }

    public function markRead($notificationId)
    {
        $this->supabase->update('admin_notifications', [
            'is_read' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $notificationId]);

        return back()->with('success', 'Notification marked as read.');
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

        return back()->with('success', 'All notifications marked as read.');
    }

    public function generateReport(Request $request)
    {
        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 500,
        ];

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $notifications = $this->fetchNotifications($params, $request->date_from, $request->date_to);

        return view('super-admin.notifications.report', [
            'notifications' => $notifications,
            'type' => $request->type,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);
    }

    /**
     * Fetch admin_notifications and attach branch/user names.
     *
     * PostgREST embedded relations (e.g. branch:branches(id,name)) require a
     * foreign key between the tables. admin_notifications has no FKs, so those
     * embedded selects fail with PGRST200 and return zero rows. We therefore
     * fetch the records plainly and resolve names in separate IN queries.
     */
    private function fetchNotifications(array $params, $dateFrom = null, $dateTo = null)
    {
        $notifications = $this->supabase->query('admin_notifications', $params);

        if ($dateFrom) {
            $from = $dateFrom;
            $notifications = array_filter($notifications, fn($n) => substr($n['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($dateTo) {
            $to = $dateTo;
            $notifications = array_filter($notifications, fn($n) => substr($n['created_at'] ?? '', 0, 10) <= $to);
        }

        $notifications = $this->attachNames(array_values($notifications));

        return collect($notifications)->map(fn($n) => (object) $n);
    }

    private function attachNames(array $notifications): array
    {
        $branchIds = array_values(array_unique(array_filter(array_column($notifications, 'branch_id'))));
        $userIds = array_values(array_unique(array_filter(array_column($notifications, 'user_id'))));

        $branchNames = [];
        if ($branchIds) {
            $branches = $this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $branchIds) . ')',
            ]);
            foreach ($branches as $b) {
                $branchNames[$b['id']] = $b['name'] ?? null;
            }
        }

        $userNames = [];
        if ($userIds) {
            $users = $this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $userIds) . ')',
            ]);
            foreach ($users as $u) {
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
