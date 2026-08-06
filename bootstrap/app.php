<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureBlProfileComplete;
use App\Http\Middleware\CountPostView;
use App\Http\Middleware\CheckMaintenanceMode;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware untuk maintenance mode
        $middleware->web(append: [
            CheckMaintenanceMode::class,
        ]);
        
        $middleware->alias([
            'bl.profile'        => EnsureBlProfileComplete::class,
            'count.post.view'   => CountPostView::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle "Method Not Allowed" error pada halaman login
        // Ini terjadi ketika CSRF token/session expired dan user submit form
        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            // Cek apakah error terjadi di route admin/login
            if (str_contains($request->path(), 'admin/login')) {
                return redirect()->route('filament.admin.auth.login')
                    ->with('status', 'Sesi Anda telah berakhir. Silakan login kembali.');
            }
        });
    })->create();
