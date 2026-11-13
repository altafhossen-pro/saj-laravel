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
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check_permission' => \App\Http\Middleware\CheckPermission::class,
            'admin_access' => \App\Http\Middleware\AdminAccess::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckBlockedIp::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/visitor/pulse',
            'api/visitor/offer/*',
            'api/steadfast/webhook',
        ]);

        $middleware->redirectUsersTo(function () {
            if (auth()->check() && auth()->user()->hasAdminAccess()) {
                return route('admin.dashboard');
            }
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 403 && $request->is('admin/*') && auth()->check() && auth()->user()->hasAdminAccess()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access Denied: You do not have permission to perform this action.',
                    ], 403);
                }
                return response()->view('admin.errors.unauthorized', [], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, $request) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
            $minutes = ceil($retryAfter / 60);

            $message = "You have requested too many verification codes. Please wait {$minutes} minute" . ($minutes > 1 ? 's' : '') . " before trying again.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'retry_after' => (int) $retryAfter
                ], 429);
            }

            return back()->withErrors(['email' => $message]);
        });
    })->create();
