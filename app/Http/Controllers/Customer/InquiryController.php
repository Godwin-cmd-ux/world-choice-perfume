<?php

namespace App\Http\Controllers\Customer;

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

    /**
     * Public contact form submission — routes to the branch's customer care inquiries.
     * Fields: email, phone, heading (subject), message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Verify the branch exists
        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (!$branch) {
            return back()->withErrors(['branch_id' => 'Selected branch does not exist.'])->withInput();
        }

        $this->supabase->insert('inquiries', [
            'branch_id' => (int) $validated['branch_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'is_read' => false,
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return back()->with('success', 'Thank you! Your message has been sent to our customer care team. We will get back to you soon.');
    }
}
