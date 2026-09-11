<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->status === 'read') {
            $params['is_read'] = 'eq.true';
        } elseif ($request->status === 'unread') {
            $params['is_read'] = 'eq.false';
        }

        $inquiries = collect($this->supabase->query('inquiries', $params));

        // PHP-side user join (PostgREST user:users expansion returns 0 rows due to RLS/FK issues)
        $userIds = $inquiries->pluck('user_id')->filter()->unique()->values()->toArray();
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

        $inquiries = $inquiries->map(function ($i) use ($users) {
            $i['user'] = isset($i['user_id']) && isset($users[$i['user_id']])
                ? (object) ['id' => $i['user_id'], 'name' => $users[$i['user_id']]] : null;
            $i['name'] = $i['name'] ?? null;
            $i['reply_message'] = $i['reply_message'] ?? null;
            $i['status'] = $i['status'] ?? 'pending';
            $i['is_featured'] = $i['is_featured'] ?? false;
            $i['is_read'] = $i['is_read'] ?? false;
            return (object) $i;
        });

        return view('customer-care.inquiries.index', ['inquiries' => $inquiries]);
    }

    public function show($inquiryId)
    {
        $inquiry = $this->supabase->find('inquiries', $inquiryId, '*');
        if (!$inquiry) abort(404);

        // PHP-side user join
        $userId = $inquiry['user_id'] ?? null;
        $userName = null;
        if ($userId) {
            $user = $this->supabase->find('users', $userId, 'id,name');
            if ($user) $userName = $user['name'];
        }
        $inquiry['user'] = $userId && $userName
            ? (object) ['id' => $userId, 'name' => $userName] : null;
        $inquiry['name'] = $userName;
        $inquiry['reply_message'] = $inquiry['reply_message'] ?? null;
        $inquiry['status'] = $inquiry['status'] ?? 'pending';
        $inquiry['is_featured'] = $inquiry['is_featured'] ?? false;

        // Mark as read
        if (!($inquiry['is_read'] ?? false)) {
            $this->supabase->update('inquiries', [
                'is_read' => true,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $inquiryId]);
        }

        return view('customer-care.inquiries.show', ['inquiry' => (object) $inquiry]);
    }

    public function reply(Request $request, $inquiryId)
    {
        $validated = $request->validate([
            'reply_message' => 'required|string',
        ]);

        $this->supabase->update('inquiries', [
            'reply_message' => $validated['reply_message'],
            'replied_by' => auth()->user()->supabase_id ?? auth()->id(),
            'replied_at' => now()->toIso8601String(),
            'status' => 'replied',
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $inquiryId]);

        return back()->with('success', 'Reply sent successfully.');
    }

    public function markAsRead($inquiryId)
    {
        $inquiry = $this->supabase->find('inquiries', $inquiryId);
        if (!$inquiry || $inquiry['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        $this->supabase->update('inquiries', [
            'is_read' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $inquiryId]);

        return back()->with('success', 'Inquiry marked as read.');
    }

    public function markAsComment($inquiryId)
    {
        $inquiry = $this->supabase->find('inquiries', $inquiryId);
        if (!$inquiry || $inquiry['branch_id'] != auth()->user()->branch_id) {
            abort(404);
        }

        $this->supabase->update('inquiries', [
            'is_read' => true,
            'is_featured' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $inquiryId]);

        return back()->with('success', 'Inquiry added as a homepage remark.');
    }
}
