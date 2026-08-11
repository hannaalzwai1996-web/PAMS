<?php

namespace App\Domain\LearningOutcome\Policies;

use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Policies\Concerns\AuthorizesProgramAccess;
use App\Models\User;

/**
 * Mirrors ProgramObjectivePolicy exactly — see
 * docs/architecture/0001-authentication-architecture.md §6. Managing the
 * PO-PLO mapping uses the same `learning-outcomes.manage` permission as
 * plain CRUD: it's a facet of managing this outcome, not a separate
 * capability, so it doesn't get its own permission slug.
 */
class LearningOutcomePolicy
{
    use AuthorizesProgramAccess;

    public function viewAny(User $user, Program $program): bool
    {
        return $this->canAccessProgram($user, $program);
    }

    public function view(User $user, LearningOutcome $outcome): bool
    {
        return $this->canAccessProgram($user, $outcome->program);
    }

    public function create(User $user, Program $program): bool
    {
        return $this->canManage($user, $program);
    }

    public function update(User $user, LearningOutcome $outcome): bool
    {
        return $this->canManage($user, $outcome->program);
    }

    public function delete(User $user, LearningOutcome $outcome): bool
    {
        return $this->canManage($user, $outcome->program);
    }

    public function manageMappings(User $user, LearningOutcome $outcome): bool
    {
        return $this->canManage($user, $outcome->program);
    }

    private function canManage(User $user, Program $program): bool
    {
        return $user->hasPermissionTo('learning-outcomes.manage')
            && $program->hasCoordinator($user);
    }
}
