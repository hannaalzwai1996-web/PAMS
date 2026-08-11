<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Support\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends EloquentRepository<User>
 */
class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    /**
     * Overridden (not just inherited) to eager-load roles/permissions:
     * UserResource calls getRoleNames()/getDirectPermissions()/
     * getAllPermissions() for every user in the collection, which without
     * this is a classic N+1 (2-3 extra queries per row on every
     * GET /admin/users page load). `roles.permissions` (not just `roles`)
     * is required too — Spatie's getAllPermissions() walks each role's own
     * permissions to compute the effective set, and would otherwise lazy
     * (re-)load that per user regardless of the `roles` eager load above.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()->with(['roles.permissions', 'permissions'])->paginate($perPage);
    }
}
