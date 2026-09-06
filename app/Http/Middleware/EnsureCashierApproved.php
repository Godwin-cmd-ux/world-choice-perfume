<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCashierApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && !$user->isApproved()) {
            $message = ($user->status ?? '') === 'blocked'
                ? 'Your account has been blocked. Please contact your administrator.'
                : 'Your account is pending approval. Please wait for an administrator to approve your account.';
            auth()->logout();
            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
