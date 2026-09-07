<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;

class DashboardController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        $branchId = auth()->user()->branch_id;

        // News posts count
        $newsCount = $this->supabase->count('news_posts', []);

        // Inquiries count
        $inquiriesCount = $this->supabase->count('inquiries', [
            'branch_id' => "eq.{$branchId}",
        ]);

        // Unread inquiries
        $unreadInquiries = $this->supabase->count('inquiries', [
            'branch_id' => "eq.{$branchId}",
            'is_read' => 'eq.false',
        ]);

        // Recent inquiries (PHP-side user join — PostgREST expansion returns 0 rows)
        $recentInquiries = collect($this->supabase->query('inquiries', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 10,
        ]));

        $userIds = $recentInquiries->pluck('user_id')->filter()->unique()->values()->toArray();
        $users = [];
        if (!empty($userIds)) {
            $usersList = $this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $userIds) . ')',
            ]);
            foreach ($usersList as $u) {
                $users[$u['id']] = $u['name'];
            }
        }

        $recentInquiries = $recentInquiries->map(function ($i) use ($users) {
            $i['user'] = isset($i['user_id']) && isset($users[$i['user_id']])
                ? (object) ['id' => $i['user_id'], 'name' => $users[$i['user_id']]] : null;
            return (object) $i;
        });

        return view('customer-care.dashboard', compact('newsCount', 'inquiriesCount', 'unreadInquiries', 'recentInquiries'));
    }
}
