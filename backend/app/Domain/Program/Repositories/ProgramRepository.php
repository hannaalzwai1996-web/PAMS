<?php

namespace App\Domain\Program\Repositories;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Repositories\Contracts\ProgramRepositoryInterface;
use App\Models\User;
use App\Support\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Program>
 */
class ProgramRepository extends EloquentRepository implements ProgramRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Program::class);
    }

    /**
     * Every listing consumer (admin/qa_officer's full list, a
     * coordinator's assigned list) wants the same shape — department plus
     * cheap counts instead of loading every objective/outcome row just to
     * size them.
     */
    public function all(): Collection
    {
        return $this->newQuery()
            ->with('department')
            ->withCount(['objectives', 'learningOutcomes'])
            ->orderBy('name')
            ->get();
    }

    public function forCoordinator(User $user): Collection
    {
        return $this->newQuery()
            ->whereHas('coordinators', fn ($query) => $query->whereKey($user->id))
            ->with('department')
            ->withCount(['objectives', 'learningOutcomes'])
            ->orderBy('name')
            ->get();
    }
}
