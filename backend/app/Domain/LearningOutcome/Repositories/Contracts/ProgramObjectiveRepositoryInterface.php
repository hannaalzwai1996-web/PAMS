<?php

namespace App\Domain\LearningOutcome\Repositories\Contracts;

use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Domain\Program\Models\Program;
use App\Support\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface ProgramObjectiveRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, ProgramObjective>
     */
    public function forProgram(Program $program): Collection;

    public function codeExistsForProgram(Program $program, string $code, ?string $excludingId = null): bool;
}
