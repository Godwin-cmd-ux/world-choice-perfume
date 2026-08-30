<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('staff_access_verified')) {
            return redirect()->route('home')->with('show_staff_modal', true);
        }

        return $next($request);
    }
}
