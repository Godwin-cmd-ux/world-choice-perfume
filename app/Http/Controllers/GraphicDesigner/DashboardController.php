<?php

namespace App\Http\Controllers\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;

class DashboardController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function dashboard()
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();

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
                if (!$p['created_at']) return false;
                return \Carbon\Carbon::parse($p['created_at'])->setTimezone('Africa/Dar_es_Salaam')->isToday();
            })->count(),
        ];

        $rejected = $statuses->where('status', 'rejected')->values()->map(fn($p) => (object) $p);

        return view('graphic-designer.dashboard', compact('counts', 'rejected'));
    }
}