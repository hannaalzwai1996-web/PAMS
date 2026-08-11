<?php

namespace App\Domain\LearningOutcome\DTOs;

use App\Support\DTO\BaseDTO;

final class BulkUpdateMatrixDTO extends BaseDTO
{
    /**
     * @param  array<int, array{objective_id: string, learning_outcome_id: string, correlation_level: int}>  $entries
     */
    public function __construct(
        public readonly array $entries = [],
    ) {}
}
