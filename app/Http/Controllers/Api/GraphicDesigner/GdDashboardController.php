<?php

namespace App\Http\Controllers\Api\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffSessionApi;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * JSON twin of GraphicDesigner\DashboardController — the same own-posts
 * query, status normalisation, five stat counts and rejected-posts list the
 * dashboard Blade page renders, for the mobile module. The signed-in user
 * comes from EnsureStaffSessionApi (the session token is only a pointer;
 * the role and account status are re-checked on every call).
 */
class GdDashboardController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function dashboard(Request $request)
    {
        $sessionUser = EnsureStaffSessionApi::user($request);
        $userId = $sessionUser['id'] ?? null;

        $posts = collect($this->supabase->query('news_posts', [
            'select' => '*',
            'author_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ]));

        $statuses = $posts->map(function ($p) {
            $raw = $p['status'] ?? null;
            $p['status'] = in_array($raw, ['approved', 'rejected', 'pending'], true)
                ? $raw
                : (($p['is_published'] ?? false) ? 'approved' : 'pending');
            $p['rejection_reason'] = $p['rejection_reason'] ?? null;

            return $p;
        });

        $counts = [
            'total' => $statuses->count(),
            'approved' => $statuses->where('status', 'approved')->count(),
            'pending' => $statuses->where('status', 'pending')->count(),
            'rejected' => $statuses->where('status', 'rejected')->count(),
            'today' => $statuses->filter(function ($p) {
                if (! $p['created_at']) {
                    return false;
                }

                return Carbon::parse($p['created_at'])->setTimezone('Africa/Dar_es_Salaam')->isToday();
            })->count(),
        ];

        $rejected = $statuses->where('status', 'rejected')->values()->map(fn ($p) => (object) $p);

        return response()->json([
            'counts' => $counts,
            'rejected' => $rejected,
        ]);
    }
}
