<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'Unauthorized. You do not have the required role to access this resource.');
        }

        if (auth()->user()->role !== 'super_admin' && !auth()->user()->isApproved()) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account is pending approval.');
        }

        return $next($request);
    }
}
