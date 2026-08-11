<?php

namespace App\Domain\LearningOutcome\DTOs;

use App\Support\DTO\BaseDTO;

/**
 * `program_id` is deliberately not a field — it comes from the route (the
 * parent Program the Controller already resolved), never from request
 * input.
 */
final class LearningOutcomeDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $statement = null,
        public readonly ?string $category = null,
    ) {}
}
