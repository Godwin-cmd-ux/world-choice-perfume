<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\StockManagerScope;
use App\Services\StockNotificationService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * Stock manager's own notification module.
 *
 * The stock-admin alerts live in the shared admin_notifications feed, tagged
 * with the branch they concern. A stock manager sees only the stock alerts for
 * the branch his pages are currently scoped to — his own, or the one he is
 * monitoring read-only — and can never mark another branch's alert as read by
 * guessing its id.
 */
class NotificationController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        // A low-stock row should be on the page as soon as it is opened, just
        // as it is on the Super Admin notification page.
        (new StockNotificationService($this->supabase))->sync();

        $scope = new StockManagerScope($this->supabase);
        $notifications = $this->supabase->query('admin_notifications', $this->scopedParams([
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 200,
        ]));

        return view('stock-manager.notifications.index', [
            'notifications' => collect($notifications)->map(fn ($n) => (object) $n),
            'readOnly' => $scope->inCrossBranchMode(),
        ]);
    }

    public function markRead($notificationId)
    {
        $this->markAsRead((int) $notificationId);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        $unread = $this->supabase->query('admin_notifications', $this->scopedParams([
            'select' => 'id',
            'is_read' => 'eq.false',
        ]));

        foreach ($unread as $notification) {
            $this->markAsRead((int) ($notification['id'] ?? 0));
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Every read and write carries the same type and branch filters, so the
     * module is stock-only and branch-scoped at the data layer, not just in the
     * blade.
     */
    private function scopedParams(array $params = []): array
    {
        $branchId = (new StockManagerScope($this->supabase))->activeBranchId();

        return array_merge([
            'type' => 'eq.' . StockNotificationService::TYPE,
            'branch_id' => 'eq.' . $branchId,
        ], $params);
    }

    private function markAsRead(int $notificationId): void
    {
        if ($notificationId <= 0) {
            return;
        }

        $this->supabase->update('admin_notifications', [
            'is_read' => true,
            'updated_at' => now()->toIso8601String(),
        ], $this->scopedParams(['id' => $notificationId]));
    }
}
