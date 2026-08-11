<?php

namespace App\Domain\LearningOutcome\Models;

use App\Domain\Program\Models\Program;
use Database\Factories\ProgramObjectiveFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Program Educational Objective (PEO) — see
 * docs/database/0001-database-design.md §3.7.
 */
#[Fillable(['program_id', 'code', 'statement'])]
class ProgramObjective extends Model
{
    /** @use HasFactory<ProgramObjectiveFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * See Program::newFactory() for why this override is needed.
     *
     * @return ProgramObjectiveFactory
     */
    protected static function newFactory()
    {
        return ProgramObjectiveFactory::new();
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * The reciprocal side of LearningOutcome::objectives() — see
     * docs/database/0001-database-design.md §3.9.
     */
    public function learningOutcomes(): BelongsToMany
    {
        return $this->belongsToMany(
            LearningOutcome::class,
            'objective_outcome_matrix',
            'program_objective_id',
            'learning_outcome_id',
        )->using(ObjectiveOutcomeMatrix::class)
            ->withPivot('correlation_level', 'source')
            ->withTimestamps();
    }
}
