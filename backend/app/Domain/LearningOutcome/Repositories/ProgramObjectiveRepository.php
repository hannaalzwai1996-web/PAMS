<?php

namespace App\Domain\LearningOutcome\Repositories;

use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Domain\LearningOutcome\Repositories\Contracts\ProgramObjectiveRepositoryInterface;
use App\Domain\Program\Models\Program;
use App\Support\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<ProgramObjective>
 */
class ProgramObjectiveRepository extends EloquentRepository implements ProgramObjectiveRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ProgramObjective::class);
    }

    public function forProgram(Program $program): Collection
    {
        return $this->newQuery()->where('program_id', $program->id)->orderBy('code')->get();
    }

    public function codeExistsForProgram(Program $program, string $code, ?string $excludingId = null): bool
    {
        return $this->newQuery()
            ->where('program_id', $program->id)
            ->where('code', $code)
            ->when($excludingId, fn ($query) => $query->whereKeyNot($excludingId))
            ->exists();
    }
}
