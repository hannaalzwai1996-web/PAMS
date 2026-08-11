<?php

namespace App\Domain\Program\Policies;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Policies\Concerns\AuthorizesProgramAccess;
use App\Models\User;

/**
 * Deliberately minimal — the full Program workflow (create/submit/approve/
 * version) is separate, future work. `view` exists now specifically for
 * the Reporting Module (reports are read-only, program-scoped exports —
 * same access rule as viewing objectives/outcomes/matrix).
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
}
