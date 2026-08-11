<?php

namespace App\Domain\Department\Repositories\Contracts;

use App\Support\Repositories\Contracts\RepositoryInterface;

interface DepartmentRepositoryInterface extends RepositoryInterface
{
    public function hasPrograms(string $departmentId): bool;
}
