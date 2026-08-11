<?php

namespace App\Domain\LearningOutcome\DTOs;

use App\Support\DTO\BaseDTO;

/**
 * `program_id` is deliberately not a field here — it comes from the route
 * (the parent Program the Controller already resolved), never from
 * request input, so a client can't move an objective to a different
 * program by tampering with the payload.
 */
final class ProgramObjectiveDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $statement = null,
    ) {}
}
