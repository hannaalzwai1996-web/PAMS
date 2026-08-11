<?php

namespace App\Support\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Standard API response envelope per ADR-0001 §7 / SRS-0001 §8.4.
 *
 * Success:  { "data": ..., "meta": {...}, "links": {...} }
 * Error:    { "message": "...", "errors": {...} }
 *
 * API Resources/ResourceCollections already emit this envelope automatically
 * when returned directly from a controller. These helpers cover the
 * remaining ad hoc responses (auth, health check, simple acknowledgements)
 * so every endpoint — resource-backed or not — shares one response shape.
 */
trait ApiResponses
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $links
     */
    protected function success(mixed $data = null, array $meta = [], array $links = [], int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        if ($links !== []) {
            $payload['links'] = $links;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null): JsonResponse
    {
        return $this->success($data, status: Response::HTTP_CREATED);
    }

    protected function noContent(): Response
    {
        return response()->noContent();
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    protected function error(string $message, array $errors = [], int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
