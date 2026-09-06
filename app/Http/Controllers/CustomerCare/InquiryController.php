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
            'select' => '*, user:users(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->status === 'read') {
            $params['is_read'] = 'eq.true';
        } elseif ($request->status === 'unread') {
            $params['is_read'] = 'eq.false';
        }

        $inquiries = collect($this->supabase->query('inquiries', $params))->map(function ($i) {
            if (isset($i['user']) && is_array($i['user'])) $i['user'] = (object) $i['user'];
            $i['name'] = $i['name'] ?? null;
            $i['reply_message'] = $i['reply_message'] ?? null;
            $i['status'] = $i['status'] ?? 'pending';
            return (object) $i;
        });

        return view('customer-care.inquiries.index', ['inquiries' => $inquiries]);
    }

    public function show($inquiryId)
    {
        $inquiry = $this->supabase->find('inquiries', $inquiryId, '*, user:users(id,name)');
        if (!$inquiry) abort(404);

        if (isset($inquiry['user']) && is_array($inquiry['user'])) $inquiry['user'] = (object) $inquiry['user'];
        $inquiry['name'] = $inquiry['name'] ?? null;
        $inquiry['reply_message'] = $inquiry['reply_message'] ?? null;
        $inquiry['status'] = $inquiry['status'] ?? 'pending';

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
}
