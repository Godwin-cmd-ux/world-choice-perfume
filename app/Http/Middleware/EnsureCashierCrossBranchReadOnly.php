<?php

namespace App\Http\Middleware;

use App\Services\CashierScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashierCrossBranchReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $scope = new CashierScope();

        if (!$scope->inCrossBranchMode()) {
            return $next($request);
        }

        // Everything in cross-branch monitoring is strictly read-only.
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            abort(403, 'Cross-branch monitoring is read-only. Exit the branch to make changes.');
        }

        return $next($request);
    }
}
