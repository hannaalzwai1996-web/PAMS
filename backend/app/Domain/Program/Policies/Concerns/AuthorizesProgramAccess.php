<?php

namespace App\Domain\Program\Policies\Concerns;

use App\Domain\Program\Models\Program;
use App\Models\User;

/**
 * Shared by every Policy that needs "can this user see this program's
 * data at all" — ProgramObjectivePolicy, LearningOutcomePolicy,
 * MatrixPolicy, ReportPolicy. Extracted rather than left duplicated four
 * times: `admin`/`qa_officer` have cross-program oversight (Requirements
 * Analysis §2 role table), a `program_coordinator` may only see programs
 * they're assigned to (BR-5/FR-PROG-09).
 *
 * Only the *view* check is shared — each Policy's *manage* check stays
 * bespoke, since the permission slug differs per domain
 * (`program-objectives.manage` vs `learning-outcomes.manage`, etc.).
 */
trait AuthorizesProgramAccess
{
    protected function canAccessProgram(User $user, Program $program): bool
    {
        return $user->hasRole(['admin', 'qa_officer'])
            || $program->hasCoordinator($user);
    }
}
