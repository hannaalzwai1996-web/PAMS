<?php

namespace App\Domain\Program\DTOs;

use App\Support\DTO\BaseDTO;

/**
 * Serves both creation (StoreProgramRequest requires every field) and
 * partial updates (UpdateProgramRequest — everything `sometimes`); each
 * FormRequest's own rules enforce which fields are required for a given
 * operation. `status` and `current_version_no` are deliberately absent —
 * the program lifecycle is never client-settable through this DTO (P0.1
 * scope is create/edit only; submit/approve is a future phase).
 */
final class ProgramDTO extends BaseDTO
{
    public function __construct(
        public readonly ?int $department_id = null,
        public readonly ?string $code = null,
        public readonly ?string $name = null,
        public readonly ?string $level = null,
        public readonly ?string $description = null,
        public readonly ?int $duration_years = null,
    ) {}
}
