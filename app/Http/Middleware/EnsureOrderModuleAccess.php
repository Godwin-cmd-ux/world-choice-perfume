<?php

namespace App\Http\Middleware;

use App\Support\BranchAccess;
use Closure;
use Illuminate\Http\Request;

/**
 * Keeps the Orders module on the Mikocheni Head Quarters branch.
 *
 * The web side reads the signed-in Laravel user; the API side reads the user
 * row that EnsureStaffSessionApi resolved from the X-Staff-Session token on
 * this very request. Super Admin is exempt so its company-wide monitor keeps
 * working from every role's module.
 */
class EnsureOrderModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = EnsureStaffSessionApi::user($request);

        if ($user === null && auth()->check()) {
            $signedIn = auth()->user();
            $user = [
                'role' => $signedIn->role ?? null,
                'branch_id' => $signedIn->branch_id ?? null,
            ];
        }

        $role = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);
        $branchId = is_array($user) ? ($user['branch_id'] ?? null) : ($user->branch_id ?? null);

        if (BranchAccess::canAccessOrders($branchId === null ? null : (int) $branchId, $role)) {
            return $next($request);
        }

        $message = 'The Orders module is only available to MIKOCHENI BRANCH HQ.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'code' => 'orders_unavailable',
            ], 403);
        }

        abort(403, $message);
    }
}
