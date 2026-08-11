<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Program\Models\Program;
use App\Domain\Reporting\Services\ProgramReportService;
use App\Support\Reporting\Exports\ArrayExport;
use App\Support\Reporting\Exports\ProgramSpecificationExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Authorization for every action here is already resolved by the
 * `can:view,program` route middleware (ProgramPolicy) before the method
 * body runs (routes/api/v1/reports.php) — these methods only fetch report
 * data from ProgramReportService and pick a file format, per ADR-0001 §2.
 * No report content is computed here.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ProgramReportService $reports) {}

    public function specification(Program $program, string $format): Response|BinaryFileResponse
    {
        $data = $this->reports->specification($program);

        if ($format === 'excel') {
            return Excel::download(new ProgramSpecificationExport($data), $this->filename($program, 'specification', 'xlsx'));
        }

        return Pdf::loadView('reports.specification', $data)
            ->download($this->filename($program, 'specification', 'pdf'));
    }

    public function objectives(Program $program, string $format): Response|BinaryFileResponse
    {
        return $this->respondTabular($this->reports->objectives($program), $program, 'objectives', $format);
    }

    public function learningOutcomes(Program $program, string $format): Response|BinaryFileResponse
    {
        return $this->respondTabular($this->reports->learningOutcomes($program), $program, 'learning-outcomes', $format);
    }

    public function matrix(Program $program, string $format): Response|BinaryFileResponse
    {
        return $this->respondTabular($this->reports->matrix($program), $program, 'matrix', $format);
    }

    /**
     * @param  array{title: string, headings: array<int, string>, rows: array<int, array<int, string>>}  $data
     */
    private function respondTabular(array $data, Program $program, string $slug, string $format): Response|BinaryFileResponse
    {
        if ($format === 'excel') {
            $export = new ArrayExport($data['headings'], $data['rows'], $data['title']);

            return Excel::download($export, $this->filename($program, $slug, 'xlsx'));
        }

        return Pdf::loadView('reports.tabular', $data)->download($this->filename($program, $slug, 'pdf'));
    }

    private function filename(Program $program, string $slug, string $extension): string
    {
        return "{$program->code}-{$slug}.{$extension}";
    }
}
