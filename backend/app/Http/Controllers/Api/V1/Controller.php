<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Support\Http\Concerns\ApiResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller for all /api/v1 endpoints.
 *
 * Per ADR-0001 §2.2, controllers are thin: they validate via a FormRequest
 * (handled by route-model injection before the method body runs), delegate
 * all business logic to a Service, and shape the response via an API
 * Resource or the ApiResponses helpers below. Controllers must never call a
 * Repository or Model directly.
 */
abstract class Controller extends BaseController
{
    use ApiResponses, AuthorizesRequests;
}
