<?php

namespace App\Domain\Program\DTOs;

use App\Support\DTO\BaseDTO;

final class AssignProgramCoordinatorDTO extends BaseDTO
{
    public function __construct(
        public readonly string $user_id,
    ) {}
}
