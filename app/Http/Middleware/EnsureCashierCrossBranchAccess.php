<?php

namespace App\Http\Middleware;

use App\Services\CashierScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashierCrossBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!(new CashierScope())->isCrossBranchMonitor()) {
            abort(403, 'Only the HQ cashier or the Super Admin can monitor other branches.');
        }

        return $next($request);
    }
}
