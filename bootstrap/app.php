<?php

use App\Http\Middleware\ActiveRoleMiddleware;
use App\Http\Middleware\EnsureLetterModuleActive;
use App\Http\Middleware\RecordActivity;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Http\Middleware\ValidatePathEncoding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->remove(ValidatePathEncoding::class);
        $middleware->remove(InvokeDeferredCallbacks::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'verified' => EnsureEmailIsVerified::class,
            'active.role' => ActiveRoleMiddleware::class,
            'letter.active' => EnsureLetterModuleActive::class,
        ]);

        $middleware->appendToGroup('web', [
            ActiveRoleMiddleware::class,
            RecordActivity::class,
        ]);

        // $middleware->prepend(\App\Http\Middleware\InstallerMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($e instanceof HttpException && $e->getStatusCode() === 403) {
                Log::error('403 Forbidden Triggered', [
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'active_role' => session('active_role'),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(10)->toArray(),
                ]);
            }
        });
    })->create();
