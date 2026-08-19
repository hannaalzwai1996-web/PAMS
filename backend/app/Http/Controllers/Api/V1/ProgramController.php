<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Program\DTOs\AssignProgramCoordinatorDTO;
use App\Domain\Program\DTOs\ProgramDTO;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Services\ProgramService;
use App\Http\Requests\Program\AssignProgramCoordinatorRequest;
use App\Http\Requests\Program\StoreProgramRequest;
use App\Http\Requests\Program\UpdateProgramRequest;
use App\Http\Resources\Api\V1\ProgramResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Authorization is already resolved by the `can:` route middleware
 * (ProgramPolicy) before this method runs (routes/api/v1/programs.php) —
 * per ADR-0001 §2, this only delegates. The per-role filtering (all
 * programs vs. just the caller's assigned ones) lives in
 * ProgramService::list(), not here.
 */
class ProgramController extends Controller
{
    public function __construct(private readonly ProgramService $programs) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ProgramResource::collection($this->programs->list($request->user()));
    }

    public function show(Program $program): ProgramResource
    {
        return ProgramResource::make($this->programs->show($program));
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        $program = $this->programs->create(ProgramDTO::fromRequest($request));

        return $this->created(ProgramResource::make($program));
    }

    public function update(UpdateProgramRequest $request, Program $program): ProgramResource
    {
        $updated = $this->programs->update($program, ProgramDTO::fromRequest($request));

        return ProgramResource::make($updated);
    }

    public function destroy(Program $program): Response
    {
        $this->programs->delete($program);

        return $this->noContent();
    }

    public function assignCoordinator(AssignProgramCoordinatorRequest $request, Program $program): ProgramResource
    {
        $updated = $this->programs->assignCoordinator($program, AssignProgramCoordinatorDTO::fromRequest($request));

        return ProgramResource::make($updated);
    }

    public function unassignCoordinator(Program $program, User $coordinator): ProgramResource
    {
        $updated = $this->programs->unassignCoordinator($program, $coordinator);

        return ProgramResource::make($updated);
    }
}
