<?php

namespace App\Domain\LearningOutcome\Policies;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Policies\Concerns\AuthorizesProgramAccess;
use App\Models\User;

/**
 * Authorizes the PO-PLO Matrix engine (ARCH-0002). Every ability takes a
 * Program, not an ObjectiveOutcomeMatrix instance — so this is registered
 * explicitly, via `Gate::policy(ObjectiveOutcomeMatrix::class,
 * MatrixPolicy::class)` in AppServiceProvider, rather than relying on
 * Laravel's naming-convention auto-discovery (which would guess
 * `ObjectiveOutcomeMatrixPolicy`, a class that doesn't exist).
 *
 * Deliberately NOT registered against `Program::class`: ProgramPolicy
 * (view-only for now) owns that slot, and Gate::policy() only allows one
 * policy per model class.
 *
 * Same admin/qa_officer/assigned-coordinator shape as
 * LearningOutcomePolicy — review/export are read-only so they use the
 * broader "can access this program" check; generate/update require the
 * management permission (BR-MTX-1: still coordinator-owned content).
 */
class MatrixPolicy
{
    use AuthorizesProgramAccess;

    public function view(User $user, Program $program): bool
    {
        return $this->canAccessProgram($user, $program);
    }

    public function export(User $user, Program $program): bool
    {
        return $this->canAccessProgram($user, $program);
    }

    public function generate(User $user, Program $program): bool
    {
        return $this->canManage($user, $program);
    }

    public function update(User $user, Program $program): bool
    {
        return $this->canManage($user, $program);
    }

    private function canManage(User $user, Program $program): bool
    {
        return $user->hasPermissionTo('learning-outcomes.manage')
            && $program->hasCoordinator($user);
    }
}
