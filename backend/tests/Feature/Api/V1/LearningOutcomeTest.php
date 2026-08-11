<?php

use App\Domain\Program\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an assigned coordinator can add a learning outcome with a valid category', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/learning-outcomes", [
            'code' => 'PLO1',
            'statement' => 'Graduates will apply core theoretical knowledge.',
            'category' => 'A',
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'PLO1')
        ->assertJsonPath('data.category', 'A')
        ->assertJsonPath('data.category_label', 'Knowledge');

    expect($program->learningOutcomes()->count())->toBe(1);
});

test('validation rejects a category outside A-D', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/learning-outcomes", [
            'code' => 'PLO1',
            'statement' => 'Invalid category.',
            'category' => 'E',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['category']);
});

test('validation fails when required fields are missing', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/learning-outcomes", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'statement', 'category']);
});

test('adding a duplicate outcome code within the same program is rejected', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'Existing.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/learning-outcomes", [
            'code' => 'PLO1',
            'statement' => 'A different statement.',
            'category' => 'B',
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Learning outcome code "PLO1" is already used in this program.');
});

test('a coordinator not assigned to the program cannot add a learning outcome', function () {
    $program = Program::factory()->create();
    $outsider = User::factory()->create();
    $outsider->assignRole('program_coordinator');

    $this->actingAs($outsider)
        ->postJson("/api/v1/programs/{$program->id}/learning-outcomes", [
            'code' => 'PLO1',
            'statement' => 'Should not be allowed.',
            'category' => 'A',
        ])
        ->assertForbidden();
});

test('a qa officer cannot add learning outcomes but can view them', function () {
    $program = Program::factory()->create();
    $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'Existing.', 'category' => 'A']);

    $qaOfficer = User::factory()->create();
    $qaOfficer->assignRole('qa_officer');

    $this->actingAs($qaOfficer)
        ->postJson("/api/v1/programs/{$program->id}/learning-outcomes", [
            'code' => 'PLO2',
            'statement' => 'Should not be allowed.',
            'category' => 'B',
        ])
        ->assertForbidden();

    $this->actingAs($qaOfficer)
        ->getJson("/api/v1/programs/{$program->id}/learning-outcomes")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('an admin can view learning outcomes for any program without being a coordinator', function () {
    $program = Program::factory()->create();
    $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'Existing.', 'category' => 'A']);

    $this->actingAs(makeAdmin())
        ->getJson("/api/v1/programs/{$program->id}/learning-outcomes")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('an assigned coordinator can edit a learning outcome', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'Original.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->patchJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}", [
            'statement' => 'Revised statement.',
            'category' => 'C',
        ])
        ->assertOk()
        ->assertJsonPath('data.statement', 'Revised statement.')
        ->assertJsonPath('data.category', 'C')
        ->assertJsonPath('data.category_label', 'Practical Skills');
});

test('learning outcomes cannot be edited once the program is no longer in draft', function () {
    $program = Program::factory()->approved()->create();
    $coordinator = makeCoordinatorFor($program);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'Original.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->patchJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}", [
            'statement' => 'Revised.',
        ])
        ->assertStatus(422)
        ->assertJsonPath(
            'message',
            'Program learning outcomes can only be changed while the program is in draft status.'
        );
});

test('an assigned coordinator can delete a learning outcome, which is soft-deleted', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'To remove.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->deleteJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}")
        ->assertNoContent();

    expect($program->learningOutcomes()->count())->toBe(0);
    expect($program->learningOutcomes()->withTrashed()->count())->toBe(1);
});

test('learning outcomes cannot be deleted once the program is no longer in draft', function () {
    $program = Program::factory()->approved()->create();
    $coordinator = makeCoordinatorFor($program);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'Cannot delete.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->deleteJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}")
        ->assertStatus(422);

    expect($program->learningOutcomes()->count())->toBe(1);
});

test('a learning outcome is not reachable through a program it does not belong to', function () {
    $programA = Program::factory()->create();
    $programB = Program::factory()->create();
    $outcome = $programB->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'Belongs to B.', 'category' => 'A']);

    $this->actingAs(makeAdmin())
        ->getJson("/api/v1/programs/{$programA->id}/learning-outcomes/{$outcome->id}")
        ->assertNotFound();
});

test('an unauthenticated request is rejected', function () {
    $program = Program::factory()->create();

    $this->getJson("/api/v1/programs/{$program->id}/learning-outcomes")
        ->assertUnauthorized();
});

test('an assigned coordinator can map a learning outcome to a program objective', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $objective = $program->objectives()->create(['code' => 'PEO1', 'statement' => 'An objective.']);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'An outcome.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}/objectives", [
            'mappings' => [
                ['objective_id' => $objective->id, 'correlation_level' => 3],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.objectives.0.id', $objective->id)
        ->assertJsonPath('data.objectives.0.correlation_level', 3);

    expect($outcome->objectives()->count())->toBe(1);
});

test('mapping to an objective from a different program is rejected', function () {
    $program = Program::factory()->create();
    $otherProgram = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $foreignObjective = $otherProgram->objectives()->create(['code' => 'PEO1', 'statement' => 'Belongs elsewhere.']);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'An outcome.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}/objectives", [
            'mappings' => [
                ['objective_id' => $foreignObjective->id, 'correlation_level' => 2],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['mappings.0.objective_id']);

    expect($outcome->objectives()->count())->toBe(0);
});

test('correlation level must be between 1 and 3', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $objective = $program->objectives()->create(['code' => 'PEO1', 'statement' => 'An objective.']);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'An outcome.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}/objectives", [
            'mappings' => [
                ['objective_id' => $objective->id, 'correlation_level' => 5],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['mappings.0.correlation_level']);
});

test('mappings cannot be changed once the program is no longer in draft', function () {
    $program = Program::factory()->approved()->create();
    $coordinator = makeCoordinatorFor($program);
    $objective = $program->objectives()->create(['code' => 'PEO1', 'statement' => 'An objective.']);
    $outcome = $program->learningOutcomes()->create(['code' => 'PLO1', 'statement' => 'An outcome.', 'category' => 'A']);

    $this->actingAs($coordinator)
        ->putJson("/api/v1/programs/{$program->id}/learning-outcomes/{$outcome->id}/objectives", [
            'mappings' => [
                ['objective_id' => $objective->id, 'correlation_level' => 1],
            ],
        ])
        ->assertStatus(422);
});
