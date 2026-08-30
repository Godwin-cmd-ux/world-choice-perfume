<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCashierApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && in_array($user->role, ['cashier', 'branch_admin']) && !$user->isApproved()) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account is pending approval. Please wait for a super administrator to approve your account.');
        }

        return $next($request);
    }
}
