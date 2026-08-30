<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BranchAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin has access to all branches
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Branch admin and cashier must have a branch_id
        if (!$user->branch_id) {
            abort(403, 'No branch assigned to your account.');
        }

        return $next($request);
    }
}
