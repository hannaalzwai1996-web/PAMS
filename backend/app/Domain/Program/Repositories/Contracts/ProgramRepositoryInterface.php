<?php

namespace App\Domain\Program\Repositories\Contracts;

use App\Models\User;
use App\Support\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Minimal for now — only what ProgramOverviewService's dashboard stats and
 * ProgramService's listing need. The full Program workflow module
 * (create/submit/approve/version) is separate, future work that will add
 * its own methods to this same interface rather than replacing it.
 */
interface ProgramRepositoryInterface extends RepositoryInterface
{
    /**
     * Programs the given user is an assigned coordinator on (BR-5 /
     * FR-PROG-09) — used by ProgramService::list() for the
     * program_coordinator branch, where `all()` would leak every
     * program in the system.
     *
     * @return Collection<int, \App\Domain\Program\Models\Program>
     */
    public function forCoordinator(User $user): Collection;
}
