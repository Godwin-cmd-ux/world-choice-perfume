<?php

namespace App\Http\Controllers\Api\CustomerCare;

use Illuminate\Http\Request;

/**
 * JSON twin of the customer-care Inquiries screens — Head Quarters-Mikocheni
 * customer care only, the same `customer-care.hq` gate the website applies.
 * Reading an inquiry marks it read, replying sets status=replied, and the
 * "homepage remark" action pins it as featured — all the website's writes.
 */
class CcInquiryController extends CcBaseController
{
    public function index(Request $request)
    {
        $this->assertHq($request);

        $params = [
            'select' => '*',
            'branch_id' => 'eq.'.$this->ownBranchId($request),
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        $status = (string) $request->query('status');
        if ($status === 'read') {
            $params['is_read'] = 'eq.true';
        } elseif ($status === 'unread') {
            $params['is_read'] = 'eq.false';
        }

        $inquiries = collect($this->supabase->query('inquiries', $params));

        // PHP-side user join (PostgREST expansions return 0 rows).
        $userIds = $inquiries->pluck('user_id')->filter()->unique()->values()->toArray();
        $users = [];
        if (! empty($userIds)) {
            foreach ($this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.('.implode(',', $userIds).')',
            ]) as $u) {
                $users[$u['id']] = $u['name'];
            }
        }

        $rows = $inquiries->map(function ($i) use ($users) {
            $i = (array) $i;
            $i['user'] = isset($i['user_id']) && isset($users[$i['user_id']])
                ? ['id' => $i['user_id'], 'name' => $users[$i['user_id']]] : null;
            $i['name'] = $i['name'] ?? ($i['user']['name'] ?? null);
            $i['reply_message'] = $i['reply_message'] ?? null;
            $i['status'] = $i['status'] ?? 'pending';
            $i['is_featured'] = $i['is_featured'] ?? false;
            $i['is_read'] = $i['is_read'] ?? false;

            return $i;
        })->values();

        $counts = [
            'all' => $rows->count(),
            'unread' => $rows->filter(fn ($i) => ! $i['is_read'])->count(),
            'replied' => $rows->filter(fn ($i) => ($i['status'] ?? '') === 'replied')->count(),
        ];

        return response()->json([
            'inquiries' => $rows->all(),
            'counts' => $counts,
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function show(Request $request, int $inquiryId)
    {
        $this->assertHq($request);

        $inquiry = $this->supabase->find('inquiries', $inquiryId, '*');
        if (! $inquiry) {
            abort(404, 'Inquiry not found.');
        }

        $userId = $inquiry['user_id'] ?? null;
        $userName = null;
        if ($userId) {
            $user = $this->supabase->find('users', $userId, 'id,name');
            $userName = $user['name'] ?? null;
        }
        $inquiry['user'] = $userId && $userName ? ['id' => $userId, 'name' => $userName] : null;
        $inquiry['name'] = $userName;
        $inquiry['reply_message'] = $inquiry['reply_message'] ?? null;
        $inquiry['status'] = $inquiry['status'] ?? 'pending';
        $inquiry['is_featured'] = $inquiry['is_featured'] ?? false;

        // Opening an inquiry marks it read — same as the website.
        if (! ($inquiry['is_read'] ?? false)) {
            $this->supabase->update('inquiries', [
                'is_read' => true,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $inquiryId]);
            $inquiry['is_read'] = true;
        }

        return response()->json(['inquiry' => $inquiry]);
    }

    public function reply(Request $request, int $inquiryId)
    {
        $this->assertHq($request);

        $validated = $request->validate([
            'reply_message' => 'required|string',
        ]);

        $inquiry = $this->supabase->find('inquiries', $inquiryId, 'id');
        if (! $inquiry) {
            abort(404, 'Inquiry not found.');
        }

        $this->supabase->update('inquiries', [
            'reply_message' => $validated['reply_message'],
            'replied_by' => $this->performingUserId($request),
            'replied_at' => now()->toIso8601String(),
            'status' => 'replied',
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $inquiryId]);

        return response()->json(['message' => 'Reply sent successfully.']);
    }

    public function markAsRead(Request $request, int $inquiryId)
    {
        $this->assertHq($request);

        $inquiry = $this->supabase->find('inquiries', $inquiryId);
        if (! $inquiry || $inquiry['branch_id'] != $this->ownBranchId($request)) {
            abort(404, 'Inquiry not found.');
        }

        $this->supabase->update('inquiries', [
            'is_read' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $inquiryId]);

        return response()->json(['message' => 'Inquiry marked as read.']);
    }

    public function markAsComment(Request $request, int $inquiryId)
    {
        $this->assertHq($request);

        $inquiry = $this->supabase->find('inquiries', $inquiryId);
        if (! $inquiry || $inquiry['branch_id'] != $this->ownBranchId($request)) {
            abort(404, 'Inquiry not found.');
        }

        $this->supabase->update('inquiries', [
            'is_read' => true,
            'is_featured' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $inquiryId]);

        return response()->json(['message' => 'Inquiry added as a homepage remark.']);
    }

    public function destroy(Request $request, int $inquiryId)
    {
        $this->assertHq($request);

        $inquiry = $this->supabase->find('inquiries', $inquiryId);
        if (! $inquiry || $inquiry['branch_id'] != $this->ownBranchId($request)) {
            abort(404, 'Inquiry not found.');
        }

        if (! $this->supabase->delete('inquiries', ['id' => $inquiryId])) {
            $this->fail(['error' => 'The inquiry could not be deleted. Please try again.']);
        }

        return response()->json(['message' => 'Inquiry deleted.']);
    }
}
