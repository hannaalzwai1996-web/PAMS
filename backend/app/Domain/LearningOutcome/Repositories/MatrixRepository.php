<?php

namespace App\Domain\LearningOutcome\Repositories;

use App\Domain\LearningOutcome\Models\ObjectiveOutcomeMatrix;
use App\Domain\LearningOutcome\Repositories\Contracts\MatrixRepositoryInterface;
use App\Domain\Program\Models\Program;
use App\Support\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends EloquentRepository<ObjectiveOutcomeMatrix>
 */
class MatrixRepository extends EloquentRepository implements MatrixRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ObjectiveOutcomeMatrix::class);
    }

    public function gridForProgram(Program $program): Collection
    {
        return $this->newQuery()
            ->whereHas('programObjective', fn ($query) => $query->where('program_id', $program->id))
            ->get();
    }

    /**
     * One transaction, one updateOrCreate per pair — deliberate, not an
     * oversight: PAMS's realistic matrix size (a handful of POs × PLOs per
     * program) makes this fast enough while staying on Eloquent, so casts
     * and model events keep working. See ARCH-0002 §4.
     */
    public function upsertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                ObjectiveOutcomeMatrix::query()->updateOrCreate(
                    [
                        'program_objective_id' => $row['program_objective_id'],
                        'learning_outcome_id' => $row['learning_outcome_id'],
                    ],
                    [
                        'correlation_level' => $row['correlation_level'],
                        'source' => $row['source'],
                    ]
                );
            }
        });
    }
}
