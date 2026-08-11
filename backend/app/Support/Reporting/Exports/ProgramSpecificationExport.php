<?php

namespace App\Support\Reporting\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The Program Specification Report as a 4-sheet workbook. Reuses
 * ArrayExport for every sheet — Laravel Excel accepts any
 * FromArray+WithTitle object here, so no bespoke per-sheet class is
 * needed.
 */
class ProgramSpecificationExport implements WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $data  Shape produced by ProgramReportService::specification().
     */
    public function __construct(private readonly array $data) {}

    public function sheets(): array
    {
        $program = $this->data['program'];

        return [
            'Program' => new ArrayExport(
                headings: ['Field', 'Value'],
                rows: collect($program)
                    ->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), (string) $value])
                    ->values()
                    ->all(),
                title: 'Program',
            ),
            'Objectives' => new ArrayExport(
                headings: ['Code', 'Statement'],
                rows: collect($this->data['objectives'])->map(fn ($row) => array_values($row))->all(),
                title: 'Program Objectives',
            ),
            'Learning Outcomes' => new ArrayExport(
                headings: ['Code', 'Statement', 'Category'],
                rows: collect($this->data['learning_outcomes'])->map(fn ($row) => array_values($row))->all(),
                title: 'Learning Outcomes',
            ),
            'Matrix Summary' => new ArrayExport(
                headings: ['Metric', 'Count'],
                rows: collect($this->data['matrix_summary'])
                    ->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), (string) $value])
                    ->values()
                    ->all(),
                title: 'Matrix Summary',
            ),
        ];
    }
}
