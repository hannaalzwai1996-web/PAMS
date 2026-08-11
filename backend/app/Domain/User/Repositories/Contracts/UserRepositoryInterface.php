<?php

namespace App\Domain\User\Repositories\Contracts;

use App\Models\User;
use App\Support\Repositories\Contracts\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;
}
