<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Support\Exceptions\Handlers\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enables cookie-based Sanctum auth for the React SPA on the 'api' group (ADR-0001 §8).
        $middleware->statefulApi();

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            // Registered for Filament/non-REST use; REST routes authorize via
            // Policies through the `can:` middleware instead (see ARCH-0001 §6).
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        // The one alias every protected REST route uses — "authenticated" and
        // "not deactivated" must never be applied inconsistently (ARCH-0001 §4).
        $middleware->group('auth.api', ['auth:sanctum', 'active']);

        // Without this, an unauthenticated request to an api/* route that
        // doesn't send Accept:application/json (any plain `get()`/curl call
        // without that header) falls through to Laravel's default guest
        // redirect, which tries to resolve a route literally named 'login' —
        // one was never registered (ours are api.v1.auth.login and
        // Filament's filament.admin.auth.login), so it 500s instead of
        // cleanly 401ing. API routes must never redirect a guest anywhere.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        ApiExceptionRenderer::register($exceptions);
    })->create();
