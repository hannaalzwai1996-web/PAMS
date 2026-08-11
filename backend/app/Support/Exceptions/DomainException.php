<?php

namespace App\Support\Exceptions;

use RuntimeException;

/**
 * Base class for all business-rule exceptions thrown from the Service layer.
 *
 * Services throw a DomainException subclass instead of an HTTP-aware
 * exception (they must stay unaware of HTTP — ADR-0001 §2). The exception
 * carries enough information for ApiExceptionRenderer to map it onto the
 * ADR-0001 §7 JSON error envelope without the Service knowing anything about
 * status codes.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        string $message,
        protected int $statusCode = 422,
        protected array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
