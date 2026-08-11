<?php

namespace App\Support\Reporting\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One reusable Excel sheet for any tabular report (PO, PLO, PO-PLO
 * Matrix) — the report data is already shaped as [headings, rows] by
 * ProgramReportService, so nothing report-specific needs to live here.
 * Also used as an individual sheet inside ProgramSpecificationExport's
 * WithMultipleSheets (Laravel Excel accepts any FromArray+WithTitle
 * object as a sheet).
 */
class ArrayExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        // Excel sheet titles are capped at 31 characters and can't contain: \ / ? * [ ]
        return substr(preg_replace('/[\\\\\/?*\[\]]/', '-', $this->title), 0, 31);
    }
}
