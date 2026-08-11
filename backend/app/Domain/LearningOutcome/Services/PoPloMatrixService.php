<?php

namespace App\Domain\LearningOutcome\Services;

use App\Domain\LearningOutcome\DTOs\BulkUpdateMatrixDTO;
use App\Domain\LearningOutcome\DTOs\GenerateMatrixDTO;
use App\Domain\LearningOutcome\Models\ObjectiveOutcomeMatrix;
use App\Domain\LearningOutcome\Repositories\Contracts\LearningOutcomeRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\Contracts\MatrixRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\Contracts\ProgramObjectiveRepositoryInterface;
use App\Domain\LearningOutcome\Services\Support\LexicalCorrelationScorer;
use App\Domain\Program\Models\Program;
use App\Support\Enums\MatrixEntrySource;
use App\Support\Exceptions\BusinessRuleException;
use Illuminate\Support\Collection;

/**
 * The PO-PLO Matrix Generation Engine. Every business rule from
 * docs/architecture/0002-po-plo-matrix-engine.md §1 is enforced here —
 * Controllers only validate input and delegate (ADR-0001 §2), and neither
 * Filament nor the frontend re-implements any part of this.
 */
class PoPloMatrixService
{
    public function __construct(
        private readonly MatrixRepositoryInterface $matrix,
        private readonly ProgramObjectiveRepositoryInterface $objectives,
        private readonly LearningOutcomeRepositoryInterface $outcomes,
        private readonly LexicalCorrelationScorer $scorer,
    ) {}

    /**
     * Auto-generates suggested correlations for every (PO, PLO) pair that
     * doesn't already have one, using the lexical-overlap heuristic
     * (§2). Never touches a `manual` cell (BR-MTX-2). With `force`, also
     * refreshes previously `auto` cells — still never `manual` ones
     * (BR-MTX-3).
     *
     * @return array<string, mixed>
     */
    public function generate(Program $program, GenerateMatrixDTO $dto): array
    {
        $this->guardProgramIsDraft($program);

        $objectives = $this->objectives->forProgram($program);
        $outcomes = $this->outcomes->forProgram($program);
        $existing = $this->indexByPair($this->matrix->gridForProgram($program));

        $rows = [];
        foreach ($objectives as $objective) {
            foreach ($outcomes as $outcome) {
                $current = $existing->get("{$objective->id}:{$outcome->id}");

                if ($current && ($current->source === MatrixEntrySource::Manual || ! $dto->force)) {
                    continue;
                }

                $level = $this->scorer->suggestLevel($objective->statement, $outcome->statement);

                if ($level === null) {
                    continue;
                }

                $rows[] = [
                    'program_objective_id' => $objective->id,
                    'learning_outcome_id' => $outcome->id,
                    'correlation_level' => $level,
                    'source' => MatrixEntrySource::Auto->value,
                ];
            }
        }

        $this->matrix->upsertMany($rows);

        return $this->review($program);
    }

    /**
     * The full PO×PLO grid, mapped or not, plus a summary a reviewer can
     * act on at a glance.
     *
     * @return array<string, mixed>
     */
    public function review(Program $program): array
    {
        $objectives = $this->objectives->forProgram($program);
        $outcomes = $this->outcomes->forProgram($program);
        $existing = $this->indexByPair($this->matrix->gridForProgram($program));

        $summary = ['auto' => 0, 'manual' => 0, 'unmapped' => 0];

        $rows = $objectives->map(function ($objective) use ($outcomes, $existing, &$summary) {
            $cells = $outcomes->map(function ($outcome) use ($objective, $existing, &$summary) {
                $entry = $existing->get("{$objective->id}:{$outcome->id}");

                if (! $entry) {
                    $summary['unmapped']++;

                    return [
                        'learning_outcome_id' => $outcome->id,
                        'code' => $outcome->code,
                        'correlation_level' => null,
                        'source' => null,
                    ];
                }

                $summary[$entry->source->value]++;

                return [
                    'learning_outcome_id' => $outcome->id,
                    'code' => $outcome->code,
                    'correlation_level' => $entry->correlation_level,
                    'source' => $entry->source->value,
                ];
            })->values();

            return [
                'objective' => ['id' => $objective->id, 'code' => $objective->code],
                'cells' => $cells,
            ];
        })->values();

        return [
            'objectives' => $objectives->map(fn ($o) => ['id' => $o->id, 'code' => $o->code])->values(),
            'outcomes' => $outcomes->map(fn ($o) => ['id' => $o->id, 'code' => $o->code, 'category' => $o->category->value])->values(),
            'rows' => $rows,
            'summary' => [...$summary, 'total_pairs' => $objectives->count() * $outcomes->count()],
        ];
    }

    /**
     * Manual bulk edit — every written cell becomes source=manual
     * (BR-MTX-4), making it immune to future generate() runs.
     *
     * @return array<string, mixed>
     */
    public function bulkUpdate(Program $program, BulkUpdateMatrixDTO $dto): array
    {
        $this->guardProgramIsDraft($program);

        $objectiveIds = $this->objectives->forProgram($program)->pluck('id');
        $outcomeIds = $this->outcomes->forProgram($program)->pluck('id');

        $rows = [];
        foreach ($dto->entries as $entry) {
            if (! $objectiveIds->contains($entry['objective_id']) || ! $outcomeIds->contains($entry['learning_outcome_id'])) {
                throw new BusinessRuleException(
                    'Every mapped pair must belong to the same program (BR-MTX-6).'
                );
            }

            $rows[] = [
                'program_objective_id' => $entry['objective_id'],
                'learning_outcome_id' => $entry['learning_outcome_id'],
                'correlation_level' => $entry['correlation_level'],
                'source' => MatrixEntrySource::Manual->value,
            ];
        }

        $this->matrix->upsertMany($rows);

        return $this->review($program);
    }

    /**
     * The review grid reshaped into CSV rows (header row first). Pure
     * data — the Controller decides how to stream it as a download
     * (ARCH-0002 §3.2: that's an HTTP delivery concern, not a business
     * rule).
     *
     * @return array<int, array<int, string>>
     */
    public function exportRows(Program $program): array
    {
        $data = $this->review($program);

        $header = ['Objective', ...$data['outcomes']->pluck('code')->all()];

        $rows = $data['rows']->map(function (array $row) {
            $cells = collect($row['cells'])->map(
                fn (array $cell) => $cell['correlation_level'] !== null ? (string) $cell['correlation_level'] : ''
            )->all();

            return [$row['objective']['code'], ...$cells];
        })->all();

        return [$header, ...$rows];
    }

    /**
     * Mirrors FR-PROG-02/BR-7 for the matrix: mutating actions are
     * draft-only, review/export are not (BR-MTX-1/8).
     */
    private function guardProgramIsDraft(Program $program): void
    {
        if (! $program->isDraft()) {
            throw new BusinessRuleException(
                'The PO-PLO matrix can only be changed while the program is in draft status.'
            );
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, ObjectiveOutcomeMatrix>  $entries
     * @return Collection<string, ObjectiveOutcomeMatrix>
     */
    private function indexByPair($entries)
    {
        return $entries->keyBy(fn ($entry) => "{$entry->program_objective_id}:{$entry->learning_outcome_id}");
    }
}
