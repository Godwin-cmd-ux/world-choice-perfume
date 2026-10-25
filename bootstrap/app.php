<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // CSRF protection covers every browser-facing route in the `web`
        // group. The previous blanket ['*'] exemption switched it off for the
        // whole application, so any cross-site page could submit our forms.
        //
        // Only the stateless API group is exempt: the mobile app holds no
        // cookies and authenticates with the X-Staff-Session header, so it can
        // never present a CSRF token. Nothing else is exempt.
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'branch.access' => \App\Http\Middleware\BranchAccessMiddleware::class,
            'cashier.approved' => \App\Http\Middleware\EnsureCashierApproved::class,
            'staff.access' => \App\Http\Middleware\EnsureStaffAccess::class,
            'stock-manager.bottle-access' => \App\Http\Middleware\EnsureStockManagerBottleAccess::class,
            'cross-branch.access' => \App\Http\Middleware\EnsureCrossBranchAccess::class,
            'cross-branch.readonly' => \App\Http\Middleware\EnsureCrossBranchReadOnly::class,
            'cashier-cross-branch.access' => \App\Http\Middleware\EnsureCashierCrossBranchAccess::class,
            'cashier-cross-branch.readonly' => \App\Http\Middleware\EnsureCashierCrossBranchReadOnly::class,
            'customer-care.hq' => \App\Http\Middleware\EnsureHeadQuarterCustomerCare::class,
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

        // An image/post body over the PHP limits (upload_max_filesize = 50M,
        // post_max_size = 64M) throws PostTooLargeException before validation
        // can run. Render a friendly page telling the user to stay under 50MB
        // instead of a raw 413 error.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            $message = 'The uploaded file is too large. Images must be 50MB or less — please choose a smaller file and try again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['file' => [$message]]], 413);
            }

            return response()
                ->view('errors.413', ['message' => $message, 'previousUrl' => url()->previous()], 413);
        });
    })->create();
