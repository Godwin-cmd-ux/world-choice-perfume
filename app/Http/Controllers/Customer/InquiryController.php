<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Support\BranchAccess;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Public contact form submission — routed straight to the MIKOCHENI BRANCH
     * HQ customer care. Fields: email, phone, heading (subject), message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $hqId = $this->hqBranchId();
        if (!$hqId) {
            $message = 'Our Head Quarters branch is not configured yet. Please try again later.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['email' => $message])->withInput();
        }

        $this->supabase->insert('inquiries', [
            'branch_id' => $hqId,
            'user_id' => auth()->check() ? (auth()->user()->supabase_id ?? auth()->id()) : null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'is_read' => false,
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $success = 'Thank you! Your message has been sent to our Head Quarters customer care team. We will get back to you soon.';

        // The website flashes and redirects; the app gets the same confirmation
        // as JSON (POST /api/contact).
        if ($request->expectsJson()) {
            return response()->json(['message' => $success], 201);
        }

        return back()->with('success', $success);
    }

    /**
     * The branch that receives public inquiries — the Head Quarters branch row,
     * found by id so a rename cannot send inquiries nowhere.
     */
    private function hqBranchId(): ?int
    {
        $rows = $this->supabase->query('branches', [
            'select' => 'id',
            'id' => 'eq.' . BranchAccess::HEAD_QUARTERS_ID,
            'is_active' => 'eq.true',
            'limit' => 1,
        ]);

        return isset($rows[0]['id']) ? (int) $rows[0]['id'] : null;
    }
}