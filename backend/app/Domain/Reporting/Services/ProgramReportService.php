<?php

namespace App\Domain\Reporting\Services;

use App\Domain\LearningOutcome\Repositories\Contracts\LearningOutcomeRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\Contracts\ProgramObjectiveRepositoryInterface;
use App\Domain\LearningOutcome\Services\PoPloMatrixService;
use App\Domain\Program\Models\Program;

/**
 * Produces the data behind all four Reporting Module report types. This
 * Service owns no data of its own — it composes what Program,
 * ProgramObjective, LearningOutcome, and PoPloMatrixService already
 * expose, exactly once, so the PDF/Excel renderers (App\Support\Reporting)
 * and the Controller never touch a Repository or re-derive anything
 * (ADR-0001 §2, §12).
 *
 * Every method returns a plain, format-agnostic array — the same
 * "Service returns data, Controller decides delivery" split established
 * by PoPloMatrixService::exportRows() (ARCH-0002 §3.2).
 */
class ProgramReportService
{
    public function __construct(
        private readonly ProgramObjectiveRepositoryInterface $objectives,
        private readonly LearningOutcomeRepositoryInterface $outcomes,
        private readonly PoPloMatrixService $matrix,
    ) {}

    /**
     * @return array{title: string, program: array<string, mixed>, objectives: array<int, array<string, string>>, learning_outcomes: array<int, array<string, string>>, matrix_summary: array<string, int>}
     */
    public function specification(Program $program): array
    {
        $program->loadMissing('department');

        return [
            'title' => "Program Specification: {$program->name}",
            'program' => [
                'code' => $program->code,
                'name' => $program->name,
                'level' => ucfirst($program->level),
                'department' => $program->department->name,
                'status' => ucfirst($program->status->value),
                'duration_years' => $program->duration_years,
                'description' => $program->description,
            ],
            'objectives' => $this->objectives->forProgram($program)
                ->map(fn ($objective) => ['code' => $objective->code, 'statement' => $objective->statement])
                ->all(),
            'learning_outcomes' => $this->outcomes->forProgram($program)
                ->map(fn ($outcome) => [
                    'code' => $outcome->code,
                    'statement' => $outcome->statement,
                    'category' => "{$outcome->category->value} — {$outcome->category->label()}",
                ])
                ->all(),
            'matrix_summary' => $this->matrix->review($program)['summary'],
        ];
    }

    /**
     * The PO Report.
     *
     * @return array{title: string, headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function objectives(Program $program): array
    {
        $rows = $this->objectives->forProgram($program)
            ->map(fn ($objective) => [$objective->code, $objective->statement])
            ->all();

        return [
            'title' => "Program Objectives (PO): {$program->name}",
            'headings' => ['Code', 'Statement'],
            'rows' => $rows,
        ];
    }

    /**
     * The PLO Report.
     *
     * @return array{title: string, headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function learningOutcomes(Program $program): array
    {
        $rows = $this->outcomes->forProgram($program)
            ->map(fn ($outcome) => [
                $outcome->code,
                $outcome->statement,
                "{$outcome->category->value} — {$outcome->category->label()}",
            ])
            ->all();

        return [
            'title' => "Program Learning Outcomes (PLO): {$program->name}",
            'headings' => ['Code', 'Statement', 'Category'],
            'rows' => $rows,
        ];
    }

    /**
     * The PO-PLO Matrix Report — reuses PoPloMatrixService::exportRows()
     * (already exactly [headings, ...rows]) rather than re-flattening the
     * grid a third time (the CSV export built for ARCH-0002 is the first;
     * this is the same shape feeding two more formats).
     *
     * @return array{title: string, headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function matrix(Program $program): array
    {
        // PHP doesn't support spread in a destructuring assignment target
        // ([$a, ...$b] = ... is a literal, not valid here) — array_shift is
        // the correct way to split off the first row.
        $rows = $this->matrix->exportRows($program);
        $headings = array_shift($rows);

        return [
            'title' => "PO-PLO Matrix: {$program->name}",
            'headings' => $headings,
            'rows' => $rows,
        ];
    }
}
