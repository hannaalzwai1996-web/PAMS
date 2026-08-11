<?php

namespace App\Support\Exceptions;

/**
 * A referenced domain entity does not exist (Service-layer equivalent of
 * ModelNotFoundException, used when the "not found" is itself a business
 * outcome rather than a raw Eloquent lookup miss). Renders as 404.
 */
class NotFoundException extends DomainException
{
    public function __construct(string $message = 'The requested resource was not found.')
    {
        parent::__construct($message, statusCode: 404);
    }
}
