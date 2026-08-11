<?php

namespace App\Domain\LearningOutcome\Services;

use App\Domain\LearningOutcome\DTOs\LearningOutcomeDTO;
use App\Domain\LearningOutcome\DTOs\SyncObjectiveMappingsDTO;
use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\LearningOutcome\Repositories\Contracts\LearningOutcomeRepositoryInterface;
use App\Domain\Program\Models\Program;
use App\Support\Enums\MatrixEntrySource;
use App\Support\Exceptions\BusinessRuleException;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Collection;

/**
 * All Program Learning Outcome business rules live here — Controllers only
 * validate input and delegate (ADR-0001 §2).
 */
class LearningOutcomeService
{
    public function __construct(private readonly LearningOutcomeRepositoryInterface $outcomes) {}

    /**
     * @return Collection<int, LearningOutcome>
     */
    public function list(Program $program): Collection
    {
        return $this->outcomes->forProgram($program);
    }

    public function find(Program $program, string $id): LearningOutcome
    {
        $outcome = $this->outcomes->find($id);

        if (! $outcome || $outcome->program_id !== $program->id) {
            throw new NotFoundException('Program learning outcome not found.');
        }

        return $outcome;
    }

    public function create(Program $program, LearningOutcomeDTO $dto): LearningOutcome
    {
        $this->guardProgramIsDraft($program);
        $this->guardCodeIsUnique($program, $dto->code);

        return $this->outcomes->create([
            'program_id' => $program->id,
            'code' => $dto->code,
            'statement' => $dto->statement,
            'category' => $dto->category,
        ]);
    }

    public function update(Program $program, LearningOutcome $outcome, LearningOutcomeDTO $dto): LearningOutcome
    {
        $this->guardProgramIsDraft($program);

        if ($dto->code !== null && $dto->code !== $outcome->code) {
            $this->guardCodeIsUnique($program, $dto->code, excludingId: $outcome->id);
        }

        $attributes = array_filter([
            'code' => $dto->code,
            'statement' => $dto->statement,
            'category' => $dto->category,
        ], fn (?string $value) => $value !== null);

        return $this->outcomes->update($outcome, $attributes);
    }

    public function delete(Program $program, LearningOutcome $outcome): void
    {
        $this->guardProgramIsDraft($program);

        $this->outcomes->delete($outcome);
    }

    /**
     * Replaces this outcome's PO-PLO correlations wholesale. Each
     * objective_id is validated (by SyncObjectiveMappingsRequest) to
     * belong to the same program before this ever runs, but the guard is
     * repeated here since a Service must not trust that every caller
     * validated correctly (ADR-0001 §2 — business rules live in the
     * Service, not just at the request boundary).
     *
     * Always writes source=manual (ARCH-0002 §1, BR-MTX-4): a human used
     * this endpoint deliberately, so these cells become immune to being
     * overwritten by a later PoPloMatrixService::generate() run.
     */
    public function syncObjectiveMappings(Program $program, LearningOutcome $outcome, SyncObjectiveMappingsDTO $dto): LearningOutcome
    {
        $this->guardProgramIsDraft($program);

        $programObjectiveIds = $program->objectives()->pluck('id');

        $syncData = [];
        foreach ($dto->mappings as $mapping) {
            if (! $programObjectiveIds->contains($mapping['objective_id'])) {
                throw new BusinessRuleException(
                    'Every mapped objective must belong to the same program as the learning outcome.'
                );
            }

            $syncData[$mapping['objective_id']] = [
                'correlation_level' => $mapping['correlation_level'],
                'source' => MatrixEntrySource::Manual->value,
            ];
        }

        $outcome->objectives()->sync($syncData);

        return $outcome->fresh('objectives');
    }

    /**
     * Mirrors FR-PROG-02 (a Program Specification may only be edited while
     * `draft`) for its learning outcomes and their objective mappings.
     */
    private function guardProgramIsDraft(Program $program): void
    {
        if (! $program->isDraft()) {
            throw new BusinessRuleException(
                'Program learning outcomes can only be changed while the program is in draft status.'
            );
        }
    }

    private function guardCodeIsUnique(Program $program, string $code, ?string $excludingId = null): void
    {
        if ($this->outcomes->codeExistsForProgram($program, $code, $excludingId)) {
            throw new ConflictException("Learning outcome code \"{$code}\" is already used in this program.");
        }
    }
}
