<?php

namespace App\Domain\LearningOutcome\Repositories\Contracts;

use App\Domain\LearningOutcome\Models\ObjectiveOutcomeMatrix;
use App\Domain\Program\Models\Program;
use App\Support\Enums\MatrixEntrySource;
use App\Support\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface MatrixRepositoryInterface extends RepositoryInterface
{
    /**
     * Every PO-PLO Matrix cell belonging to the given program — see
     * docs/architecture/0002-po-plo-matrix-engine.md §4 for why this joins
     * through program_objectives rather than a direct program_id column.
     *
     * @return Collection<int, ObjectiveOutcomeMatrix>
     */
    public function gridForProgram(Program $program): Collection;

    /**
     * @param  array<int, array{program_objective_id: string, learning_outcome_id: string, correlation_level: int, source: MatrixEntrySource}>  $rows
     */
    public function upsertMany(array $rows): void;
}
