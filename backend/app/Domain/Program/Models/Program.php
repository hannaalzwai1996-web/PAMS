<?php

namespace App\Domain\Program\Models;

use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Models\Department;
use App\Models\User;
use App\Support\Enums\ProgramStatus;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Minimal Program model — see docs/database/0001-database-design.md §3.4.
 * Create/update (P0.1) and coordinator assignment (P0.2) are implemented
 * via ProgramService; submit/approve/versioning remain future work.
 */
#[Fillable(['department_id', 'code', 'name', 'level', 'description', 'duration_years', 'status', 'current_version_no'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The only levels ProgramFactory and the frontend's Program type have
     * ever recognized — centralized here so StoreProgramRequest and
     * UpdateProgramRequest validate against one source of truth instead
     * of each hard-coding the same four strings.
     *
     * @var array<int, string>
     */
    public const LEVELS = ['diploma', 'bachelor', 'master', 'phd'];

    /**
     * Laravel's factory auto-discovery only strips the `App\Models\`
     * prefix — models living elsewhere (like this one, under
     * `App\Domain\Program\Models`) need an explicit override.
     *
     * @return ProgramFactory
     */
    protected static function newFactory()
    {
        return ProgramFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProgramStatus::class,
            'duration_years' => 'integer',
            'current_version_no' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'program_coordinator_assignments')
            ->withPivot('assigned_at');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(ProgramObjective::class);
    }

    public function learningOutcomes(): HasMany
    {
        return $this->hasMany(LearningOutcome::class);
    }

    /**
     * BR-5 / FR-PROG-09: a coordinator may only act on programs they're
     * assigned to. Central here so every domain Policy checks it the same
     * way instead of re-querying the pivot table itself.
     */
    public function hasCoordinator(User $user): bool
    {
        return $this->coordinators()->whereKey($user->id)->exists();
    }

    public function isDraft(): bool
    {
        return $this->status === ProgramStatus::Draft;
    }
}
