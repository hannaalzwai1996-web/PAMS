<?php

namespace App\Domain\User\Services;

use App\Domain\User\DTOs\AssignRoleDTO;
use App\Domain\User\DTOs\SyncPermissionsDTO;
use App\Domain\User\DTOs\UserDTO;
use App\Domain\User\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Support\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * All account-provisioning business rules live here — Controllers only
 * validate input and delegate (ADR-0001 §2). This is also where
 * "Registration" as a business concept (admin-provisioned accounts, one
 * role per user, deactivation revokes access immediately) is actually
 * enforced, not in the Controller or a Policy.
 */
class UserService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /**
     * `$role` is optional — added for the Program Coordinator-assignment
     * picker (P0.2), which needs to list only `program_coordinator`
     * users. Existing callers (the Users admin page) pass no role and see
     * exactly the same unfiltered list as before.
     */
    public function list(int $perPage = 15, ?string $role = null): LengthAwarePaginator
    {
        return $this->users->paginateByRole($perPage, $role);
    }

    /**
     * Aggregate counts for the "Monitor system" admin dashboard
     * (ADR-0001 §3) — computed here, not queried directly by a Widget,
     * since "how many active users" is domain-meaningful data, not a
     * presentation concern.
     *
     * @return array{total: int, active: int, inactive: int, by_role: array<string, int>}
     */
    public function stats(): array
    {
        $all = $this->users->all();

        return [
            'total' => $all->count(),
            'active' => $all->where('is_active', true)->count(),
            'inactive' => $all->where('is_active', false)->count(),
            'by_role' => $all->flatMap(fn (User $user) => $user->roles->pluck('name'))
                ->countBy()
                ->all(),
        ];
    }

    /**
     * Creates an admin-provisioned account with exactly one role
     * (FR-USR-02). There is no public registration path — see
     * ARCH-0001 §0.
     */
    public function register(UserDTO $dto): User
    {
        $user = $this->users->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'is_active' => $dto->is_active ?? true,
        ]);

        $user->syncRoles([$dto->role]);

        return $user;
    }

    /**
     * Profile fields only (name, email, is_active). Role and permission
     * changes are separate operations — see assignRole()/syncPermissions()
     * — each with their own authorization ability and audit trail.
     */
    public function update(User $user, UserDTO $dto, User $actingUser): User
    {
        if ($dto->is_active === false && $user->is($actingUser)) {
            throw new BusinessRuleException('An administrator cannot deactivate their own account.');
        }

        $attributes = array_filter([
            'name' => $dto->name,
            'email' => $dto->email,
        ], fn (?string $value) => $value !== null);

        if ($attributes !== []) {
            $this->users->update($user, $attributes);
        }

        if ($dto->is_active !== null) {
            $this->users->update($user, ['is_active' => $dto->is_active]);

            if ($dto->is_active === false) {
                $this->revokeAccess($user);
            }
        }

        return $user->fresh();
    }

    /**
     * Replaces the user's single role (FR-USR-02: exactly one role per
     * user — syncRoles(), not addRole(), is what guarantees that).
     */
    public function assignRole(User $user, AssignRoleDTO $dto): User
    {
        $user->syncRoles([$dto->role]);

        return $user->fresh();
    }

    /**
     * Grants a user permissions directly, independent of their role — an
     * exception mechanism (e.g. one Program Coordinator temporarily needs
     * `accreditation.manage` without becoming a QA Officer). This does not
     * touch the permission catalog itself, which stays fixed reference
     * data owned by PermissionSeeder (ARCH-0001 §5) — only which of those
     * already-defined permissions this one user directly holds.
     */
    public function syncPermissions(User $user, SyncPermissionsDTO $dto): User
    {
        $user->syncPermissions($dto->permissions);

        return $user->fresh();
    }

    /**
     * Soft-deletes the account (User uses SoftDeletes — ADR-0001 §4: audit
     * history on records other data references, e.g. quality report
     * approvals, must survive). Access is revoked immediately, same as
     * deactivation; soft-deleting also removes the user from the default
     * Eloquent query scope Auth's user provider uses, so the account
     * can no longer authenticate even before tokens/sessions expire.
     */
    public function delete(User $user, User $actingUser): void
    {
        if ($user->is($actingUser)) {
            throw new BusinessRuleException('An administrator cannot delete their own account.');
        }

        $this->revokeAccess($user);

        $this->users->delete($user);
    }

    /**
     * FR-USR-03: deactivation must take effect immediately, not just block
     * future logins. Sanctum tokens are revoked outright; the database
     * session driver lets us drop any already-open session directly.
     */
    private function revokeAccess(User $user): void
    {
        $user->tokens()->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
