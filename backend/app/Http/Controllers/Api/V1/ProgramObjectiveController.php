<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\LearningOutcome\DTOs\ProgramObjectiveDTO;
use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Domain\LearningOutcome\Services\ProgramObjectiveService;
use App\Domain\Program\Models\Program;
use App\Http\Requests\ProgramObjective\StoreProgramObjectiveRequest;
use App\Http\Requests\ProgramObjective\UpdateProgramObjectiveRequest;
use App\Http\Resources\Api\V1\ProgramObjectiveResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Authorization for every action here is already resolved by the `can:`
 * route middleware (ProgramObjectivePolicy) before the method body runs
 * (routes/api/v1/program-objectives.php), and `{objective}` is scoped to
 * `{program}` at the routing layer (Route::scopeBindings()) — so these
 * methods only validate-and-delegate, per ADR-0001 §2. No business logic
 * lives in this controller.
 */
class ProgramObjectiveController extends Controller
{
    public function __construct(private readonly ProgramObjectiveService $objectives) {}

    public function index(Program $program): AnonymousResourceCollection
    {
        return ProgramObjectiveResource::collection($this->objectives->list($program));
    }

    public function store(StoreProgramObjectiveRequest $request, Program $program): JsonResponse
    {
        $objective = $this->objectives->create($program, ProgramObjectiveDTO::fromRequest($request));

        return $this->created(ProgramObjectiveResource::make($objective));
    }

    public function show(Program $program, ProgramObjective $objective): ProgramObjectiveResource
    {
        return ProgramObjectiveResource::make($objective);
    }

    public function update(UpdateProgramObjectiveRequest $request, Program $program, ProgramObjective $objective): ProgramObjectiveResource
    {
        $updated = $this->objectives->update($program, $objective, ProgramObjectiveDTO::fromRequest($request));

        return ProgramObjectiveResource::make($updated);
    }

    public function destroy(Program $program, ProgramObjective $objective): Response
    {
        $this->objectives->delete($program, $objective);

        return $this->noContent();
    }
}
