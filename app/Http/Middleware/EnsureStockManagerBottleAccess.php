<?php

namespace App\Http\Middleware;

use App\Services\StockManagerScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockManagerBottleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((new StockManagerScope())->isHQStockManager()) {
            abort(403, 'Bottle, oil fragrance and bottle accessories management are not available for your branch.');
        }

        return $next($request);
    }
}