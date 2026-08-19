<?php

namespace App\Domain\Program\Services;

use App\Domain\Program\DTOs\AssignProgramCoordinatorDTO;
use App\Domain\Program\DTOs\ProgramDTO;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Repositories\Contracts\ProgramRepositoryInterface;
use App\Domain\User\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Support\Enums\ProgramStatus;
use App\Support\Enums\Role;
use App\Support\Exceptions\BusinessRuleException;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Program listing (read-only, role-scoped — see `list()`) plus, as of
 * P0.1/P0.2, the Program create/edit and coordinator-assignment workflow.
 * Submit/approve/versioning remain future work (ARCH note in
 * ProgramPolicy).
 */
class ProgramService
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programs,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * every authenticated role reaches this differently: admin/qa_officer
     * have cross-program oversight (Requirements Analysis §2 role table),
     * program_coordinator only sees programs they're assigned to (BR-5 /
     * FR-PROG-09). Same role split as AuthorizesProgramAccess, just
     * scoping a collection instead of a single Program.
     *
     * @return Collection<int, Program>
     */
    public function list(User $user): Collection
    {
        return $user->hasRole(['admin', 'qa_officer'])
            ? $this->programs->all()
            : $this->programs->forCoordinator($user);
    }

    /**
     * The route already resolved `$program` via implicit binding — this
     * only adds the relations a single-program view needs (department for
     * display, coordinators for the assignment UI) that the lighter list
     * endpoint doesn't eager-load.
     */
    public function show(Program $program): Program
    {
        return $program->load(['department', 'coordinators']);
    }

    /**
     * `status`/`current_version_no` are never taken from $dto — a client
     * can't influence them, they're always exactly 'draft'/1 for a new
     * Program (P0.1 scope is create/edit only). Set explicitly here
     * rather than left to the migration's column default: Eloquent's
     * create() returns the in-memory model with only the attributes it
     * was given, so a value that exists only as a DB-level default isn't
     * reflected on the object handed back to the Resource (the exact bug
     * already fixed once for UserFactory::is_active — same root cause).
     */
    public function create(ProgramDTO $dto): Program
    {
        $program = $this->programs->create([
            'department_id' => $dto->department_id,
            'code' => $dto->code,
            'name' => $dto->name,
            'level' => $dto->level,
            'description' => $dto->description,
            'duration_years' => $dto->duration_years,
            'status' => ProgramStatus::Draft->value,
            'current_version_no' => 1,
        ]);

        return $program->load('department');
    }

    /**
     * Mirrors the draft-only mutation guard already enforced for a
     * Program's Objectives/Outcomes/Matrix (ProgramObjectiveService et
     * al.) — applied here to the Program's own metadata for the same
     * reason (FR-PROG-02/BR-7): once submitted, the specification is
     * frozen until a new version exists.
     */
    public function update(Program $program, ProgramDTO $dto): Program
    {
        $this->guardProgramIsDraft($program);

        $attributes = array_filter([
            'department_id' => $dto->department_id,
            'code' => $dto->code,
            'name' => $dto->name,
            'level' => $dto->level,
            'description' => $dto->description,
            'duration_years' => $dto->duration_years,
        ], fn (mixed $value) => $value !== null);

        if ($attributes !== []) {
            $this->programs->update($program, $attributes);
        }

        return $program->fresh(['department', 'coordinators']);
    }

    public function delete(Program $program): void
    {
        $this->guardProgramIsDraft($program);

        $this->programs->delete($program);
    }

    /**
     * BR-5 stays intact even for a brand-new assignment: only a user who
     * actually holds the `program_coordinator` role can be attached
     * (business rule, not a format concern — hence a Service-level guard,
     * not a FormRequest rule), and a duplicate attach is rejected with a
     * clean 409 rather than surfacing the underlying unique-constraint
     * violation on (program_id, user_id).
     *
     * The schema places no unique constraint on program_id alone —
     * Program::coordinators() is a genuine many-to-many — so this adds a
     * coordinator, it does not replace one.
     */
    public function assignCoordinator(Program $program, AssignProgramCoordinatorDTO $dto): Program
    {
        $user = $this->users->find($dto->user_id);

        if (! $user) {
            throw new NotFoundException('User not found.');
        }

        if (! $user->hasRole(Role::ProgramCoordinator->value)) {
            throw new BusinessRuleException(
                'Only users with the Program Coordinator role can be assigned to a program.'
            );
        }

        if ($program->hasCoordinator($user)) {
            throw new ConflictException('This user is already assigned as a coordinator on this program.');
        }

        DB::transaction(function () use ($program, $user) {
            $program->coordinators()->attach($user->id, ['assigned_at' => now()]);
        });

        return $program->fresh('coordinators');
    }

    public function unassignCoordinator(Program $program, User $coordinator): Program
    {
        if (! $program->hasCoordinator($coordinator)) {
            throw new NotFoundException('This user is not assigned as a coordinator on this program.');
        }

        $program->coordinators()->detach($coordinator->id);

        return $program->fresh('coordinators');
    }

    /**
     * Mirrors FR-PROG-02 (a Program Specification may only be edited
     * while `draft`) for the Program's own metadata, same as its
     * Objectives/Outcomes/Matrix.
     */
    private function guardProgramIsDraft(Program $program): void
    {
        if (! $program->isDraft()) {
            throw new BusinessRuleException(
                'A program can only be edited or deleted while it is in draft status.'
            );
        }
    }
}
