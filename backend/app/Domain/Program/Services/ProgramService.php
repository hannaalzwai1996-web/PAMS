<?php

namespace App\Domain\Program\Services;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Repositories\Contracts\ProgramRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-only program listing for the "which programs can I see" question —
 * every authenticated role reaches this differently: admin/qa_officer have
 * cross-program oversight (Requirements Analysis §2 role table),
 * program_coordinator only sees programs they're assigned to (BR-5 /
 * FR-PROG-09). Same role split as AuthorizesProgramAccess, just scoping a
 * collection instead of a single Program.
 */
class ProgramService
{
    public function __construct(private readonly ProgramRepositoryInterface $programs) {}

    /**
     * @return Collection<int, Program>
     */
    public function list(User $user): Collection
    {
        return $user->hasRole(['admin', 'qa_officer'])
            ? $this->programs->all()
            : $this->programs->forCoordinator($user);
    }
}
