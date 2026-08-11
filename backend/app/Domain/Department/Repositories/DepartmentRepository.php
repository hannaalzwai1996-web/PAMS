<?php

namespace App\Domain\Department\Repositories;

use App\Domain\Department\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Domain\Program\Models\Program;
use App\Models\Department;
use App\Support\Repositories\EloquentRepository;

/**
 * @extends EloquentRepository<Department>
 */
class DepartmentRepository extends EloquentRepository implements DepartmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Department::class);
    }

    public function hasPrograms(string $departmentId): bool
    {
        return Program::query()->where('department_id', $departmentId)->exists();
    }
}
