<?php

namespace App\Domain\User\DTOs;

use App\Support\DTO\BaseDTO;

final class SyncPermissionsDTO extends BaseDTO
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public readonly array $permissions = [],
    ) {}
}
