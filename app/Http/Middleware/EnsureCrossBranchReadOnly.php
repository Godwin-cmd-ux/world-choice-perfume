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
        if ((new StockManagerScope())->inCrossBranchMode()
            && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            abort(403, 'Cross-branch monitoring is read-only. Exit the branch to make changes.');
        }

        return $next($request);
    }
}