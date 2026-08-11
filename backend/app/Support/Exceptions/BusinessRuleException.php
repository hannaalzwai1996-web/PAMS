<?php

namespace App\Support\Exceptions;

/**
 * A business rule was violated (e.g. BR-1: cannot submit a Program
 * Specification with zero Learning Outcomes). Renders as 422.
 */
class BusinessRuleException extends DomainException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message, statusCode: 422, errors: $errors);
    }
}
