<?php

namespace App\Domain\LearningOutcome\DTOs;

use App\Support\DTO\BaseDTO;

final class SyncObjectiveMappingsDTO extends BaseDTO
{
    /**
     * @param  array<int, array{objective_id: string, correlation_level: int}>  $mappings
     */
    public function __construct(
        public readonly array $mappings = [],
    ) {}
}
