<?php

namespace App\Support\Exceptions\Handlers;

use App\Support\Exceptions\DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Maps every exception type the API can throw onto the single JSON error
 * envelope defined in ADR-0001 §7 / SRS-0001 §8.4:
 *
 *   { "message": "...", "errors": { "field": ["..."] } }
 *
 * with the correct HTTP status code. This is the one place that translates
 * "what went wrong" into "what the client sees" — Services and Controllers
 * never format an error response themselves.
 *
 * Each callback only handles JSON-expecting (API) requests and returns null
 * otherwise, so web requests keep Laravel's normal HTML error rendering.
 */
class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => 'Unauthenticated.'], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'This action is unauthorized.',
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => 'Resource not found.'], 404);
        });

        $exceptions->render(function (DomainException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return static::jsonError($e->getMessage(), $e->errors(), $e->statusCode());
        });

        // Catch-all for framework HTTP exceptions (404 route miss, 405, 429 throttle, ...).
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return static::jsonError(
                $e->getMessage() ?: 'An error occurred.',
                [],
                $e->getStatusCode(),
            );
        });

        // Last-resort fallback for anything unanticipated — never leak internals in production.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $message = app()->hasDebugModeEnabled() ? $e->getMessage() : 'Server error.';

            return static::jsonError($message, [], 500);
        });
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private static function jsonError(string $message, array $errors, int $status): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
