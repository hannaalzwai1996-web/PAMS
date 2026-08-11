<?php

namespace App\Domain\LearningOutcome\Models;

use App\Domain\Program\Models\Program;
use App\Support\Enums\LearningOutcomeCategory;
use Database\Factories\LearningOutcomeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Program Learning Outcome (PLO) — see
 * docs/database/0001-database-design.md §3.8.
 */
#[Fillable(['program_id', 'code', 'statement', 'category'])]
class LearningOutcome extends Model
{
    /** @use HasFactory<LearningOutcomeFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * See Program::newFactory() for why this override is needed.
     *
     * @return LearningOutcomeFactory
     */
    protected static function newFactory()
    {
        return LearningOutcomeFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => LearningOutcomeCategory::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * The "PO-PLO Matrix" (docs/database/0001-database-design.md §3.9):
     * which Program Objectives this outcome supports, and how strongly.
     */
    public function objectives(): BelongsToMany
    {
        return $this->belongsToMany(
            ProgramObjective::class,
            'objective_outcome_matrix',
            'learning_outcome_id',
            'program_objective_id',
        )->using(ObjectiveOutcomeMatrix::class)
            ->withPivot('correlation_level', 'source')
            ->withTimestamps();
    }
}
