<?php

namespace App\Domain\User\DTOs;

use App\Support\DTO\BaseDTO;

/**
 * All fields are optional at the DTO level because the same class serves
 * both creation (StoreUserRequest requires name/email/password/role) and
 * partial updates (UpdateUserRequest — everything `sometimes`); each
 * FormRequest's own validation rules are what actually enforce which
 * fields are required for a given operation.
 */
final class UserDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?string $role = null,
        public readonly ?bool $is_active = null,
    ) {}
}
