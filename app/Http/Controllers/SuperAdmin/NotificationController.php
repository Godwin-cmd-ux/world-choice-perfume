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
            'select' => '*, branch:branches(id,name), user:users(id,name)',
            'order' => 'created_at.desc',
            'limit' => 200,
        ];

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $notifications = $this->supabase->query('notifications', $params);

        // Apply date range filters in PHP
        if ($request->date_from) {
            $from = $request->date_from;
            $notifications = array_filter($notifications, fn($n) => substr($n['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->date_to) {
            $to = $request->date_to;
            $notifications = array_filter($notifications, fn($n) => substr($n['created_at'] ?? '', 0, 10) <= $to);
        }

        $notifications = collect(array_values($notifications))->map(function ($n) {
            if (isset($n['branch']) && is_array($n['branch'])) $n['branch'] = (object) $n['branch'];
            if (isset($n['user']) && is_array($n['user'])) $n['user'] = (object) $n['user'];
            return (object) $n;
        });

        return view('super-admin.notifications.index', ['notifications' => $notifications]);
    }

    public function markRead($notificationId)
    {
        $this->supabase->update('notifications', [
            'is_read' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $notificationId]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        $unread = $this->supabase->query('notifications', [
            'is_read' => 'eq.false',
            'select' => 'id',
        ]);

        foreach ($unread as $n) {
            $this->supabase->update('notifications', [
                'is_read' => true,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $n['id']]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function generateReport(Request $request)
    {
        $params = [
            'select' => '*, branch:branches(id,name), user:users(id,name)',
            'order' => 'created_at.desc',
            'limit' => 500,
        ];

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $notifications = $this->supabase->query('notifications', $params);

        if ($request->date_from) {
            $from = $request->date_from;
            $notifications = array_filter($notifications, fn($n) => substr($n['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->date_to) {
            $to = $request->date_to;
            $notifications = array_filter($notifications, fn($n) => substr($n['created_at'] ?? '', 0, 10) <= $to);
        }

        $notifications = collect(array_values($notifications))->map(function ($n) {
            if (isset($n['branch']) && is_array($n['branch'])) $n['branch'] = (object) $n['branch'];
            if (isset($n['user']) && is_array($n['user'])) $n['user'] = (object) $n['user'];
            return (object) $n;
        });

        return view('super-admin.notifications.report', [
            'notifications' => $notifications,
            'type' => $request->type,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);
    }
}
