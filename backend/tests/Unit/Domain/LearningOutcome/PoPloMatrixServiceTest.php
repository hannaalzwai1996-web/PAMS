<?php

use App\Domain\LearningOutcome\DTOs\BulkUpdateMatrixDTO;
use App\Domain\LearningOutcome\DTOs\GenerateMatrixDTO;
use App\Domain\LearningOutcome\Repositories\Contracts\LearningOutcomeRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\Contracts\MatrixRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\Contracts\ProgramObjectiveRepositoryInterface;
use App\Domain\LearningOutcome\Services\PoPloMatrixService;
use App\Domain\LearningOutcome\Services\Support\LexicalCorrelationScorer;
use App\Domain\Program\Models\Program;
use App\Support\Enums\LearningOutcomeCategory;
use App\Support\Enums\MatrixEntrySource;
use App\Support\Enums\ProgramStatus;
use App\Support\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Collection;

/**
 * True isolated unit tests: every dependency PoPloMatrixService takes is
 * either mocked (the three Repository interfaces) or the real, pure,
 * facade-free LexicalCorrelationScorer — no database, no HTTP, no
 * Laravel application boot. Distinct from
 * tests/Feature/Api/V1/ProgramMatrixTest.php, which proves the same
 * rules hold end-to-end through the real stack; these prove the Service's
 * own decision logic in isolation, and run in milliseconds.
 */
function draftProgram(): Program
{
    return new Program(['status' => ProgramStatus::Draft]);
}

function stubObjective(string $id, string $code, string $statement): stdClass
{
    return (object) ['id' => $id, 'code' => $code, 'statement' => $statement];
}

function stubOutcome(string $id, string $code, string $statement): stdClass
{
    return (object) ['id' => $id, 'code' => $code, 'statement' => $statement, 'category' => LearningOutcomeCategory::Knowledge];
}

function stubMatrixEntry(string $objectiveId, string $outcomeId, int $level, MatrixEntrySource $source): stdClass
{
    return (object) [
        'program_objective_id' => $objectiveId,
        'learning_outcome_id' => $outcomeId,
        'correlation_level' => $level,
        'source' => $source,
    ];
}

beforeEach(function () {
    $this->matrixRepo = Mockery::mock(MatrixRepositoryInterface::class);
    $this->objectivesRepo = Mockery::mock(ProgramObjectiveRepositoryInterface::class);
    $this->outcomesRepo = Mockery::mock(LearningOutcomeRepositoryInterface::class);

    $this->service = new PoPloMatrixService(
        $this->matrixRepo,
        $this->objectivesRepo,
        $this->outcomesRepo,
        new LexicalCorrelationScorer,
    );
});

afterEach(function () {
    Mockery::close();
});

test('generate() skips a pair the scorer finds no overlap for', function () {
    $program = draftProgram();
    $objective = stubObjective('obj-1', 'PEO1', 'Graduates apply engineering analysis techniques.');
    $outcome = stubOutcome('out-1', 'PLO1', 'Students demonstrate ethical workplace communication.');

    $this->objectivesRepo->shouldReceive('forProgram')->andReturn(new Collection([$objective]));
    $this->outcomesRepo->shouldReceive('forProgram')->andReturn(new Collection([$outcome]));
    $this->matrixRepo->shouldReceive('gridForProgram')->andReturn(new Collection([]));

    // The whole point of BR-MTX-7: zero-overlap pairs must not be upserted at all.
    $this->matrixRepo->shouldReceive('upsertMany')->once()->with([]);

    $this->service->generate($program, new GenerateMatrixDTO(force: false));
});

test('generate() never overwrites a manually-sourced cell, even with force', function () {
    $program = draftProgram();
    $objective = stubObjective('obj-1', 'PEO1', 'Graduates apply engineering analysis techniques.');
    $outcome = stubOutcome('out-1', 'PLO1', 'Students apply engineering analysis techniques.');
    $manualEntry = stubMatrixEntry('obj-1', 'out-1', 1, MatrixEntrySource::Manual);

    $this->objectivesRepo->shouldReceive('forProgram')->andReturn(new Collection([$objective]));
    $this->outcomesRepo->shouldReceive('forProgram')->andReturn(new Collection([$outcome]));
    $this->matrixRepo->shouldReceive('gridForProgram')->andReturn(new Collection([$manualEntry]));

    // BR-MTX-2: a manual cell is untouchable — upsertMany must receive an
    // empty set even though the scorer would otherwise suggest a level.
    $this->matrixRepo->shouldReceive('upsertMany')->once()->with([]);

    $this->service->generate($program, new GenerateMatrixDTO(force: true));
});

test('generate() refreshes a stale auto cell only when forced', function () {
    $program = draftProgram();
    $objective = stubObjective('obj-1', 'PEO1', 'Graduates apply engineering analysis techniques.');
    $outcome = stubOutcome('out-1', 'PLO1', 'Students apply engineering analysis techniques.');
    $autoEntry = stubMatrixEntry('obj-1', 'out-1', 1, MatrixEntrySource::Auto);

    $this->objectivesRepo->shouldReceive('forProgram')->andReturn(new Collection([$objective]));
    $this->outcomesRepo->shouldReceive('forProgram')->andReturn(new Collection([$outcome]));
    $this->matrixRepo->shouldReceive('gridForProgram')->andReturn(new Collection([$autoEntry]));

    // BR-MTX-3: without force, an existing auto cell is left alone too.
    $this->matrixRepo->shouldReceive('upsertMany')->once()->with([]);

    $this->service->generate($program, new GenerateMatrixDTO(force: false));
});

test('generate() refuses to run when the program is not draft', function () {
    $program = new Program(['status' => ProgramStatus::Approved]);

    $this->objectivesRepo->shouldNotReceive('forProgram');
    $this->matrixRepo->shouldNotReceive('upsertMany');

    $this->service->generate($program, new GenerateMatrixDTO(force: false));
})->throws(BusinessRuleException::class, 'The PO-PLO matrix can only be changed while the program is in draft status.');

test('bulkUpdate() rejects a pair whose objective does not belong to the program', function () {
    $program = draftProgram();

    $this->objectivesRepo->shouldReceive('forProgram')->andReturn(new Collection([stubObjective('obj-1', 'PEO1', 'x')]));
    $this->outcomesRepo->shouldReceive('forProgram')->andReturn(new Collection([stubOutcome('out-1', 'PLO1', 'y')]));
    $this->matrixRepo->shouldNotReceive('upsertMany');

    $dto = new BulkUpdateMatrixDTO(entries: [
        ['objective_id' => 'foreign-objective', 'learning_outcome_id' => 'out-1', 'correlation_level' => 2],
    ]);

    $this->service->bulkUpdate($program, $dto);
})->throws(BusinessRuleException::class);

test('bulkUpdate() writes every valid pair as source=manual', function () {
    $program = draftProgram();
    $objective = stubObjective('obj-1', 'PEO1', 'x');
    $outcome = stubOutcome('out-1', 'PLO1', 'y');

    $this->objectivesRepo->shouldReceive('forProgram')->andReturn(new Collection([$objective]));
    $this->outcomesRepo->shouldReceive('forProgram')->andReturn(new Collection([$outcome]));
    $this->matrixRepo->shouldReceive('gridForProgram')->andReturn(new Collection([]));

    $this->matrixRepo->shouldReceive('upsertMany')->once()->with([
        [
            'program_objective_id' => 'obj-1',
            'learning_outcome_id' => 'out-1',
            'correlation_level' => 3,
            'source' => 'manual',
        ],
    ]);

    $dto = new BulkUpdateMatrixDTO(entries: [
        ['objective_id' => 'obj-1', 'learning_outcome_id' => 'out-1', 'correlation_level' => 3],
    ]);

    $this->service->bulkUpdate($program, $dto);
});
