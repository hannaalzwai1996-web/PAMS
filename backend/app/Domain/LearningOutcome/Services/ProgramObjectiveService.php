<?php

namespace App\Domain\LearningOutcome\Services;

use App\Domain\LearningOutcome\DTOs\ProgramObjectiveDTO;
use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Domain\LearningOutcome\Repositories\Contracts\ProgramObjectiveRepositoryInterface;
use App\Domain\Program\Models\Program;
use App\Support\Exceptions\BusinessRuleException;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Collection;

/**
 * All Program Objective business rules live here — Controllers only
 * validate input and delegate (ADR-0001 §2).
 */
class ProgramObjectiveService
{
    public function __construct(private readonly ProgramObjectiveRepositoryInterface $objectives) {}

    /**
     * @return Collection<int, ProgramObjective>
     */
    public function list(Program $program): Collection
    {
        return $this->objectives->forProgram($program);
    }

    public function find(Program $program, string $id): ProgramObjective
    {
        $objective = $this->objectives->find($id);

        if (! $objective || $objective->program_id !== $program->id) {
            throw new NotFoundException('Program objective not found.');
        }

        return $objective;
    }

    public function create(Program $program, ProgramObjectiveDTO $dto): ProgramObjective
    {
        $this->guardProgramIsDraft($program);
        $this->guardCodeIsUnique($program, $dto->code);

        return $this->objectives->create([
            'program_id' => $program->id,
            'code' => $dto->code,
            'statement' => $dto->statement,
        ]);
    }

    public function update(Program $program, ProgramObjective $objective, ProgramObjectiveDTO $dto): ProgramObjective
    {
        $this->guardProgramIsDraft($program);

        if ($dto->code !== null && $dto->code !== $objective->code) {
            $this->guardCodeIsUnique($program, $dto->code, excludingId: $objective->id);
        }

        $attributes = array_filter([
            'code' => $dto->code,
            'statement' => $dto->statement,
        ], fn (?string $value) => $value !== null);

        return $this->objectives->update($objective, $attributes);
    }

    public function delete(Program $program, ProgramObjective $objective): void
    {
        $this->guardProgramIsDraft($program);

        $this->objectives->delete($objective);
    }

    /**
     * Mirrors FR-PROG-02 (a Program Specification may only be edited while
     * `draft`) for its objectives: once a program is submitted/approved,
     * its content is frozen until a new version is created (BR-7).
     */
    private function guardProgramIsDraft(Program $program): void
    {
        if (! $program->isDraft()) {
            throw new BusinessRuleException(
                'Program objectives can only be changed while the program is in draft status.'
            );
        }
    }

    private function guardCodeIsUnique(Program $program, string $code, ?string $excludingId = null): void
    {
        if ($this->objectives->codeExistsForProgram($program, $code, $excludingId)) {
            throw new ConflictException("Objective code \"{$code}\" is already used in this program.");
        }
    }
}
