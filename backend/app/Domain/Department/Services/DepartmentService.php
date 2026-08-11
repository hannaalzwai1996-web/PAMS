<?php

namespace App\Domain\Department\Services;

use App\Domain\Department\DTOs\DepartmentDTO;
use App\Domain\Department\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Models\Department;
use App\Support\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Department is reference/lookup data (ADR-0001 §4), but it's still
 * mutated exclusively through this Service — never directly by Filament —
 * per ADR-0001 §3 ("Filament must NOT contain business logic").
 */
class DepartmentService
{
    public function __construct(private readonly DepartmentRepositoryInterface $departments) {}

    /**
     * @return Collection<int, Department>
     */
    public function list(): Collection
    {
        return $this->departments->all();
    }

    public function create(DepartmentDTO $dto): Department
    {
        return $this->departments->create([
            'code' => $dto->code,
            'name' => $dto->name,
        ]);
    }

    public function update(Department $department, DepartmentDTO $dto): Department
    {
        $attributes = array_filter([
            'code' => $dto->code,
            'name' => $dto->name,
        ], fn (?string $value) => $value !== null);

        return $this->departments->update($department, $attributes);
    }

    /**
     * A department that already owns programs can't be removed out from
     * under them — the FK is RESTRICT at the database level (DB Design
     * §5), but this gives a clean, actionable error instead of a raw SQL
     * constraint violation reaching the admin.
     */
    public function delete(Department $department): void
    {
        if ($this->departments->hasPrograms($department->id)) {
            throw new ConflictException(
                'This department cannot be deleted while it still has programs assigned to it.'
            );
        }

        $this->departments->delete($department);
    }
}
