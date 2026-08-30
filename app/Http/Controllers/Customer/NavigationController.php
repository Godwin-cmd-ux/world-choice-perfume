<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;

class NavigationController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function show(string $branchId)
    {
        $branch = $this->supabase->find('branches', $branchId);

        if (!$branch) {
            abort(404);
        }

        // If no coordinates set, redirect back
        if (empty($branch['latitude']) || empty($branch['longitude'])) {
            return back()->with('error', 'Branch location has not been set yet. Please contact the branch admin.');
        }

        return view('customer.navigation', [
            'branch' => (object) $branch,
        ]);
    }
}
