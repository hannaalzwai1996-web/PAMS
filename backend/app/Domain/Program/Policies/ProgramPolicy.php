<?php

namespace App\Domain\Program\Policies;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Policies\Concerns\AuthorizesProgramAccess;
use App\Models\User;

/**
 * P0.1/P0.2 (create/edit/delete/coordinator-assignment) implemented here;
 * submit/approve/versioning remain future work.
 *
 * `create`/`delete`/`assignCoordinator` are admin-only: the acting Program
 * Coordinator relationship (`hasCoordinator()`) can't decide "who may
 * create a not-yet-existing Program," and coordinator assignment is what
 * *establishes* that relationship in the first place, so it can't depend
 * on it either. `update` is the one ability a Program's own assigned
 * coordinator also holds — via the `programs.edit` permission the
 * PermissionSeeder catalog already grants to `program_coordinator` — so
 * they can maintain their own draft Program's metadata the same way they
 * already maintain its Objectives/Outcomes (BR-5).
 *
 * Auto-discovered: App\Domain\Program\Models\Program → this class, per
 * Laravel's \Models\→\Policies\ convention.
 */
class ProgramPolicy
{
    use AuthorizesProgramAccess;

    /**
     * Every authenticated role reaches the listing endpoint — there's no
     * "can't see the list at all" case, only "which programs are in it,"
     * and that filtering happens in ProgramService::list(), not here.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Program $program): bool
    {
        return $this->canAccessProgram($user, $program);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Program $program): bool
    {
        return $user->hasRole('admin')
            || ($user->hasPermissionTo('programs.edit') && $program->hasCoordinator($user));
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->hasRole('admin');
    }

    public function assignCoordinator(User $user, Program $program): bool
    {
        return $user->hasRole('admin');
    }
}
