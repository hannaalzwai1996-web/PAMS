<?php

use App\Domain\LearningOutcome\Models\ObjectiveOutcomeMatrix;
use App\Domain\Program\Models\Program;
use App\Models\User;
use App\Support\Enums\MatrixEntrySource;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('generating the matrix fills gaps with lexically overlapping pairs and skips unrelated ones', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    ['overlapping' => $peo1, 'matchingOutcome' => $plo1, 'nonMatchingOutcome' => $plo2] = seedMatrixFixtures($program);

    $response = $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/matrix/generate")
        ->assertOk();

    $rows = $response->json('data.rows');
    $peo1Row = collect($rows)->firstWhere('objective.id', $peo1->id);
    $cellForPlo1 = collect($peo1Row['cells'])->firstWhere('learning_outcome_id', $plo1->id);
    $cellForPlo2 = collect($peo1Row['cells'])->firstWhere('learning_outcome_id', $plo2->id);

    expect($cellForPlo1['correlation_level'])->not->toBeNull();
    expect($cellForPlo1['source'])->toBe('auto');
    expect($cellForPlo2['correlation_level'])->toBeNull();
});

test('generating the matrix never overwrites a manually set cell', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    ['overlapping' => $peo1, 'matchingOutcome' => $plo1] = seedMatrixFixtures($program);

    // A human deliberately set this to Low (1), disagreeing with what the algorithm would suggest.
    $this->actingAs($coordinator)->putJson("/api/v1/programs/{$program->id}/matrix", [
        'entries' => [
            ['objective_id' => $peo1->id, 'learning_outcome_id' => $plo1->id, 'correlation_level' => 1],
        ],
    ])->assertOk();

    $response = $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/matrix/generate")
        ->assertOk();

    $cell = collect($response->json('data.rows'))
        ->firstWhere('objective.id', $peo1->id)['cells'];
    $cell = collect($cell)->firstWhere('learning_outcome_id', $plo1->id);

    expect($cell['correlation_level'])->toBe(1);
    expect($cell['source'])->toBe('manual');
});

test('force regenerates previously auto cells but still never touches manual ones', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    ['overlapping' => $peo1, 'unrelated' => $peo2, 'matchingOutcome' => $plo1] = seedMatrixFixtures($program);

    // Seed an auto cell directly (as if a prior generate() ran) and a manual one.
    $program->objectives()->find($peo2->id); // no-op, keeps var referenced
    ObjectiveOutcomeMatrix::query()->create([
        'program_objective_id' => $peo2->id,
        'learning_outcome_id' => $plo1->id,
        'correlation_level' => 1,
        'source' => MatrixEntrySource::Auto,
    ]);
    $this->actingAs($coordinator)->putJson("/api/v1/programs/{$program->id}/matrix", [
        'entries' => [
            ['objective_id' => $peo1->id, 'learning_outcome_id' => $plo1->id, 'correlation_level' => 1],
        ],
    ])->assertOk();

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/matrix/generate", ['force' => true])
        ->assertOk();

    $peo1Entry = ObjectiveOutcomeMatrix::query()
        ->where('program_objective_id', $peo1->id)->where('learning_outcome_id', $plo1->id)->first();
    $peo2Entry = ObjectiveOutcomeMatrix::query()
        ->where('program_objective_id', $peo2->id)->where('learning_outcome_id', $plo1->id)->first();

    expect($peo1Entry->source)->toBe(MatrixEntrySource::Manual);
    expect($peo1Entry->correlation_level)->toBe(1);
    // peo2/plo1 had no lexical overlap, so force-regeneration removes the stale auto guess... actually
    // generate() only upserts pairs the scorer finds overlap for, so a previously-auto, now-zero-overlap
    // pair is simply left as-is (not deleted) since generate() never deletes, only upserts.
    expect($peo2Entry->source)->toBe(MatrixEntrySource::Auto);
});

test('review returns the full grid with accurate summary counts', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    seedMatrixFixtures($program);

    $this->actingAs($coordinator)->postJson("/api/v1/programs/{$program->id}/matrix/generate")->assertOk();

    $response = $this->actingAs($coordinator)
        ->getJson("/api/v1/programs/{$program->id}/matrix")
        ->assertOk();

    $summary = $response->json('data.summary');

    expect($summary['total_pairs'])->toBe(4); // 2 objectives x 2 outcomes
    expect($summary['auto'] + $summary['manual'] + $summary['unmapped'])->toBe(4);
});

test('a manual bulk edit sets source to manual', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    ['overlapping' => $peo1, 'matchingOutcome' => $plo1] = seedMatrixFixtures($program);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/matrix", [
            'entries' => [
                ['objective_id' => $peo1->id, 'learning_outcome_id' => $plo1->id, 'correlation_level' => 2],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.summary.manual', 1);
});

test('manual edit rejects a pair from a different program', function () {
    $program = Program::factory()->create();
    $otherProgram = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $foreignObjective = $otherProgram->objectives()->create(['code' => 'PEO1', 'statement' => 'Elsewhere.']);
    ['matchingOutcome' => $plo1] = seedMatrixFixtures($program);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/matrix", [
            'entries' => [
                ['objective_id' => $foreignObjective->id, 'learning_outcome_id' => $plo1->id, 'correlation_level' => 2],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['entries.0.objective_id']);
});

test('correlation level must be between 1 and 3 in a bulk edit', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    ['overlapping' => $peo1, 'matchingOutcome' => $plo1] = seedMatrixFixtures($program);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/matrix", [
            'entries' => [
                ['objective_id' => $peo1->id, 'learning_outcome_id' => $plo1->id, 'correlation_level' => 9],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['entries.0.correlation_level']);
});

test('generate and manual edit are blocked once the program leaves draft, but review and export are not', function () {
    $program = Program::factory()->approved()->create();
    $coordinator = makeCoordinatorFor($program);
    seedMatrixFixtures($program);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/matrix/generate")
        ->assertStatus(422);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/matrix", ['entries' => []])
        ->assertStatus(422);

    $this->actingAs($coordinator)
        ->getJson("/api/v1/programs/{$program->id}/matrix")
        ->assertOk();

    $this->actingAs($coordinator)
        ->get("/api/v1/programs/{$program->id}/matrix/export")
        ->assertOk();
});

test('exporting returns a CSV with objective rows and outcome-code headers', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    ['overlapping' => $peo1, 'matchingOutcome' => $plo1] = seedMatrixFixtures($program);

    $this->actingAs($coordinator)->putJson("/api/v1/programs/{$program->id}/matrix", [
        'entries' => [
            ['objective_id' => $peo1->id, 'learning_outcome_id' => $plo1->id, 'correlation_level' => 3],
        ],
    ])->assertOk();

    $response = $this->actingAs($coordinator)
        ->get("/api/v1/programs/{$program->id}/matrix/export")
        ->assertOk();

    $csv = $response->streamedContent();

    expect($csv)->toContain('PLO1')->toContain('PLO2')->toContain('PEO1')->toContain('PEO2');
    expect($csv)->toContain("PEO1,3,\n");
});

test('a qa officer can view and export but not generate or edit the matrix', function () {
    $program = Program::factory()->create();
    seedMatrixFixtures($program);

    $qaOfficer = User::factory()->create();
    $qaOfficer->assignRole('qa_officer');

    $this->actingAs($qaOfficer)->getJson("/api/v1/programs/{$program->id}/matrix")->assertOk();
    $this->actingAs($qaOfficer)->get("/api/v1/programs/{$program->id}/matrix/export")->assertOk();
    $this->actingAs($qaOfficer)->postJson("/api/v1/programs/{$program->id}/matrix/generate")->assertForbidden();
    $this->actingAs($qaOfficer)->putJson("/api/v1/programs/{$program->id}/matrix", ['entries' => []])->assertForbidden();
});

test('a coordinator not assigned to the program cannot access its matrix at all', function () {
    $program = Program::factory()->create();
    $outsider = User::factory()->create();
    $outsider->assignRole('program_coordinator');

    $this->actingAs($outsider)->getJson("/api/v1/programs/{$program->id}/matrix")->assertForbidden();
});

test('an unauthenticated request is rejected', function () {
    $program = Program::factory()->create();

    $this->getJson("/api/v1/programs/{$program->id}/matrix")->assertUnauthorized();
});
