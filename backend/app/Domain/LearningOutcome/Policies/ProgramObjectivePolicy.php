<?php

namespace App\Domain\LearningOutcome\Policies;

use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Policies\Concerns\AuthorizesProgramAccess;
use App\Models\User;

/**
 * Follows the pattern fixed in docs/architecture/0001-authentication-architecture.md
 * §6: `admin`/`qa_officer` have cross-program oversight (Requirements
 * Analysis §2 role table); a `program_coordinator` may only touch programs
 * they're assigned to (BR-5/FR-PROG-09), enforced via
 * Program::hasCoordinator() rather than re-querying the pivot table here.
 */
class ProgramObjectivePolicy
{
    use AuthorizesProgramAccess;

    public function viewAny(User $user, Program $program): bool
    {
        return $this->canAccessProgram($user, $program);
    }

    public function view(User $user, ProgramObjective $objective): bool
    {
        return $this->canAccessProgram($user, $objective->program);
    }

    public function create(User $user, Program $program): bool
    {
        return $this->canManage($user, $program);
    }

    public function update(User $user, ProgramObjective $objective): bool
    {
        return $this->canManage($user, $objective->program);
    }

    public function delete(User $user, ProgramObjective $objective): bool
    {
        return $this->canManage($user, $objective->program);
    }

    private function canManage(User $user, Program $program): bool
    {
        return $user->hasPermissionTo('program-objectives.manage')
            && $program->hasCoordinator($user);
    }
}
