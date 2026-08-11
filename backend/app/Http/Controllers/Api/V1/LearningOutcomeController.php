<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\LearningOutcome\DTOs\LearningOutcomeDTO;
use App\Domain\LearningOutcome\DTOs\SyncObjectiveMappingsDTO;
use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\LearningOutcome\Services\LearningOutcomeService;
use App\Domain\Program\Models\Program;
use App\Http\Requests\LearningOutcome\StoreLearningOutcomeRequest;
use App\Http\Requests\LearningOutcome\SyncObjectiveMappingsRequest;
use App\Http\Requests\LearningOutcome\UpdateLearningOutcomeRequest;
use App\Http\Resources\Api\V1\LearningOutcomeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Authorization for every action here is already resolved by the `can:`
 * route middleware (LearningOutcomePolicy) before the method body runs
 * (routes/api/v1/learning-outcomes.php), and `{learning_outcome}` is
 * scoped to `{program}` at the routing layer (Route::scopeBindings()) —
 * so these methods only validate-and-delegate, per ADR-0001 §2. No
 * business logic lives in this controller.
 */
class LearningOutcomeController extends Controller
{
    public function __construct(private readonly LearningOutcomeService $outcomes) {}

    public function index(Program $program): AnonymousResourceCollection
    {
        return LearningOutcomeResource::collection($this->outcomes->list($program));
    }

    public function store(StoreLearningOutcomeRequest $request, Program $program): JsonResponse
    {
        $outcome = $this->outcomes->create($program, LearningOutcomeDTO::fromRequest($request));

        return $this->created(LearningOutcomeResource::make($outcome));
    }

    public function show(Program $program, LearningOutcome $learning_outcome): LearningOutcomeResource
    {
        return LearningOutcomeResource::make($learning_outcome->load('objectives'));
    }

    public function update(UpdateLearningOutcomeRequest $request, Program $program, LearningOutcome $learning_outcome): LearningOutcomeResource
    {
        $updated = $this->outcomes->update($program, $learning_outcome, LearningOutcomeDTO::fromRequest($request));

        return LearningOutcomeResource::make($updated);
    }

    public function destroy(Program $program, LearningOutcome $learning_outcome): Response
    {
        $this->outcomes->delete($program, $learning_outcome);

        return $this->noContent();
    }

    public function syncObjectiveMappings(SyncObjectiveMappingsRequest $request, Program $program, LearningOutcome $learning_outcome): LearningOutcomeResource
    {
        $updated = $this->outcomes->syncObjectiveMappings(
            $program,
            $learning_outcome,
            SyncObjectiveMappingsDTO::fromRequest($request),
        );

        return LearningOutcomeResource::make($updated);
    }
}
