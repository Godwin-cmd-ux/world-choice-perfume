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

        // Recent inquiries
        $recentInquiries = collect($this->supabase->query('inquiries', [
            'select' => '*, user:users(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 10,
        ]))->map(function ($i) {
            if (isset($i['user']) && is_array($i['user'])) $i['user'] = (object) $i['user'];
            return (object) $i;
        });

        return view('customer-care.dashboard', compact('newsCount', 'inquiriesCount', 'unreadInquiries', 'recentInquiries'));
    }
}
