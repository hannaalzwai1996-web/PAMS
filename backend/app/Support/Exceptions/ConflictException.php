<?php

namespace App\Support\Exceptions;

/**
 * The request conflicts with existing state (e.g. BR-8: a program already
 * has an open Quality Report for the requested period). Renders as 409.
 */
class ConflictException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, statusCode: 409);
    }
}
