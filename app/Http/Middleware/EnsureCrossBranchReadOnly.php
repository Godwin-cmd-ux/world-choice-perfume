<?php

namespace App\Http\Middleware;

use App\Services\StockManagerScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrossBranchReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $scope = new StockManagerScope();

        if (!$scope->inCrossBranchMode()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        // Sales and orders of the monitored branch stay hidden.
        if (str_starts_with($routeName, 'stock-manager.sales.')
            || str_starts_with($routeName, 'stock-manager.orders.')) {
            return redirect()->route('stock-manager.dashboard')
                ->with('warning', 'Sales and orders are hidden while monitoring another branch. Exit the branch to view them.');
        }

        // Everything else in cross-branch monitoring is strictly read-only.
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            abort(403, 'Cross-branch monitoring is read-only. Exit the branch to make changes.');
        }

        return $next($request);
    }
}