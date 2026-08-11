<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\LearningOutcome\DTOs\BulkUpdateMatrixDTO;
use App\Domain\LearningOutcome\DTOs\GenerateMatrixDTO;
use App\Domain\LearningOutcome\Services\PoPloMatrixService;
use App\Domain\Program\Models\Program;
use App\Http\Requests\Matrix\GenerateMatrixRequest;
use App\Http\Requests\Matrix\UpdateMatrixRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Authorization for every action here is already resolved by the `can:`
 * route middleware (MatrixPolicy) before the method body runs
 * (routes/api/v1/matrix.php) — these methods only validate-and-delegate to
 * PoPloMatrixService, per ADR-0001 §2 and ARCH-0002 §5. No matrix logic
 * lives in this controller.
 */
class ProgramMatrixController extends Controller
{
    public function __construct(private readonly PoPloMatrixService $matrix) {}

    public function review(Program $program): JsonResponse
    {
        return $this->success($this->matrix->review($program));
    }

    public function generate(GenerateMatrixRequest $request, Program $program): JsonResponse
    {
        return $this->success($this->matrix->generate($program, GenerateMatrixDTO::fromRequest($request)));
    }

    public function update(UpdateMatrixRequest $request, Program $program): JsonResponse
    {
        return $this->success($this->matrix->bulkUpdate($program, BulkUpdateMatrixDTO::fromRequest($request)));
    }

    /**
     * CSV streaming is an HTTP delivery concern, not a business rule — the
     * Service hands back plain rows (ARCH-0002 §3.2), this method's only
     * job is turning them into a downloadable response.
     */
    public function export(Program $program): StreamedResponse
    {
        $rows = $this->matrix->exportRows($program);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, "po-plo-matrix-{$program->code}.csv", ['Content-Type' => 'text/csv']);
    }
}
