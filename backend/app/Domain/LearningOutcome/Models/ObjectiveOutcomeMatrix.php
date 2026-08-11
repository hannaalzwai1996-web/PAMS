<?php

namespace App\Domain\LearningOutcome\Models;

use App\Support\Enums\MatrixEntrySource;
use Database\Factories\ObjectiveOutcomeMatrixFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One cell of the "PO-PLO Matrix" — see
 * docs/database/0001-database-design.md §3.9 and
 * docs/architecture/0002-po-plo-matrix-engine.md. Also serves as the
 * custom pivot model for LearningOutcome::objectives() /
 * ProgramObjective::learningOutcomes(), so pivot access
 * (`$outcome->pivot`) returns a real, typed model instead of a generic
 * Eloquent Pivot.
 */
#[Fillable(['program_objective_id', 'learning_outcome_id', 'correlation_level', 'source'])]
class ObjectiveOutcomeMatrix extends Pivot
{
    /** @use HasFactory<ObjectiveOutcomeMatrixFactory> */
    use HasFactory;

    public $incrementing = true;

    protected $table = 'objective_outcome_matrix';

    /**
     * See Program::newFactory() for why this override is needed.
     *
     * @return ObjectiveOutcomeMatrixFactory
     */
    protected static function newFactory()
    {
        return ObjectiveOutcomeMatrixFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correlation_level' => 'integer',
            'source' => MatrixEntrySource::class,
        ];
    }

    public function programObjective(): BelongsTo
    {
        return $this->belongsTo(ProgramObjective::class);
    }

    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class);
    }
}
