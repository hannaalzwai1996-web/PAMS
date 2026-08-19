<?php

namespace App\Domain\User\Repositories\Contracts;

use App\Models\User;
use App\Support\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * Same eager-loading as `paginate()`, plus an optional role filter —
     * added for the Program Coordinator-assignment picker (P0.2), which
     * needs "only users who actually hold `program_coordinator`," not
     * every user.
     */
    public function paginateByRole(int $perPage = 15, ?string $role = null): LengthAwarePaginator;
}
