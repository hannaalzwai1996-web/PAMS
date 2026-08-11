<?php

namespace App\Support\Exceptions;

/**
 * A role-scoped business rule blocked the action (e.g. BR-3: a Program
 * Coordinator cannot approve their own submission). This is distinct from
 * Laravel's AuthorizationException/Policy denial, which is used for
 * ability-level checks — this one is for Service-level rule enforcement
 * that isn't naturally expressed as a Policy. Renders as 403.
 */
class UnauthorizedActionException extends DomainException
{
    public function __construct(string $message = 'This action is not permitted.')
    {
        parent::__construct($message, statusCode: 403);
    }
}
