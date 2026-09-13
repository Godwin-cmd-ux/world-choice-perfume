<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    private const HQ_BRANCH_NAME = 'Head Quarters-Mikocheni';

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Public contact form submission — routed straight to Head Quarters-Mikocheni
     * customer care. Fields: email, phone, heading (subject), message.
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
            return back()->withErrors(['email' => 'Our Head Quarters branch is not configured yet. Please try again later.'])->withInput();
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

        return back()->with('success', 'Thank you! Your message has been sent to our Head Quarters customer care team. We will get back to you soon.');
    }

    private function hqBranchId(): ?int
    {
        $rows = $this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'id.asc',
        ]);

        foreach ($rows as $branch) {
            if (mb_strtolower(trim($branch['name'] ?? '')) === mb_strtolower(trim(self::HQ_BRANCH_NAME))) {
                return (int) $branch['id'];
            }
        }

        return null;
    }
}