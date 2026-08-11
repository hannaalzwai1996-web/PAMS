<?php

namespace App\Domain\Department\DTOs;

use App\Support\DTO\BaseDTO;

final class DepartmentDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $name = null,
    ) {}
}
