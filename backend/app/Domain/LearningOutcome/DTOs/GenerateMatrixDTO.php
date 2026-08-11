<?php

namespace App\Domain\LearningOutcome\DTOs;

use App\Support\DTO\BaseDTO;

final class GenerateMatrixDTO extends BaseDTO
{
    public function __construct(
        public readonly bool $force = false,
    ) {}
}
