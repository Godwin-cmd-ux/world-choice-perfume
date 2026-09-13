<?php

namespace App\Http\Middleware;

use App\Services\CustomerCareScope;
use Closure;
use Illuminate\Http\Request;

class EnsureHeadQuarterCustomerCare
{
    public function handle(Request $request, Closure $next)
    {
        if (!(new CustomerCareScope())->isHqCustomerCare()) {
            abort(403, 'Only customer care from Head Quarters-Mikocheni can access this page.');
        }

        return $next($request);
    }
}