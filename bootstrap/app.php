<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['*']);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'branch.access' => \App\Http\Middleware\BranchAccessMiddleware::class,
            'cashier.approved' => \App\Http\Middleware\EnsureCashierApproved::class,
            'staff.access' => \App\Http\Middleware\EnsureStaffAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Application Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    })->create();
