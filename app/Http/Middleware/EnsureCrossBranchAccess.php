<?php

namespace App\Http\Middleware;

use App\Services\StockManagerScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrossBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!(new StockManagerScope())->isKinondoniStockManager()) {
            abort(403, 'Only the Kinondoni branch stock manager can monitor other branches.');
        }

        return $next($request);
    }
}