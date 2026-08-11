<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Blocks a request whose authenticated user has been deactivated
 * (FR-USR-03), even if their session cookie or token is still otherwise
 * valid. Without this, deactivating a user only stops *future* logins —
 * an already-open session would keep working until it naturally expired.
 *
 * Always paired with `auth:sanctum` via the `auth.api` middleware group
 * (bootstrap/app.php) — this middleware assumes a user has already been
 * resolved onto the request.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->is_active === false) {
            throw new HttpException(403, 'This account has been deactivated.');
        }

        return $next($request);
    }
}
