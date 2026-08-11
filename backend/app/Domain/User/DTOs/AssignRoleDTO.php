<?php

namespace App\Domain\User\DTOs;

use App\Support\DTO\BaseDTO;

final class AssignRoleDTO extends BaseDTO
{
    public function __construct(
        public readonly string $role,
    ) {}
}
