<?php

use App\Domain\Program\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an assigned coordinator can add an objective to their draft program', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/objectives", [
            'code' => 'PEO1',
            'statement' => 'Graduates will demonstrate professional competence.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'PEO1')
        ->assertJsonPath('data.program_id', $program->id);

    expect($program->objectives()->count())->toBe(1);
});

test('adding a duplicate objective code within the same program is rejected', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $program->objectives()->create(['code' => 'PEO1', 'statement' => 'Existing objective.']);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/objectives", [
            'code' => 'PEO1',
            'statement' => 'A different statement.',
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Objective code "PEO1" is already used in this program.');
});

test('validation fails when required fields are missing', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/objectives", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'statement']);
});

test('a coordinator not assigned to the program cannot add an objective', function () {
    $program = Program::factory()->create();
    $outsider = User::factory()->create();
    $outsider->assignRole('program_coordinator');

    $this->actingAs($outsider)
        ->postJson("/api/v1/programs/{$program->id}/objectives", [
            'code' => 'PEO1',
            'statement' => 'Should not be allowed.',
        ])
        ->assertForbidden();
});

test('a qa officer cannot add objectives but can view them', function () {
    $program = Program::factory()->create();
    $program->objectives()->create(['code' => 'PEO1', 'statement' => 'Existing objective.']);

    $qaOfficer = User::factory()->create();
    $qaOfficer->assignRole('qa_officer');

    $this->actingAs($qaOfficer)
        ->postJson("/api/v1/programs/{$program->id}/objectives", [
            'code' => 'PEO2',
            'statement' => 'Should not be allowed.',
        ])
        ->assertForbidden();

    $this->actingAs($qaOfficer)
        ->getJson("/api/v1/programs/{$program->id}/objectives")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('an admin can view objectives for any program without being a coordinator', function () {
    $program = Program::factory()->create();
    $program->objectives()->create(['code' => 'PEO1', 'statement' => 'Existing objective.']);

    $this->actingAs(makeAdmin())
        ->getJson("/api/v1/programs/{$program->id}/objectives")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('an assigned coordinator can edit an objective', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $objective = $program->objectives()->create(['code' => 'PEO1', 'statement' => 'Original statement.']);

    $this->actingAs($coordinator)
        ->patchJson("/api/v1/programs/{$program->id}/objectives/{$objective->id}", [
            'statement' => 'Revised statement.',
        ])
        ->assertOk()
        ->assertJsonPath('data.statement', 'Revised statement.')
        ->assertJsonPath('data.code', 'PEO1');
});

test('objectives cannot be edited once the program is no longer in draft', function () {
    $program = Program::factory()->approved()->create();
    $coordinator = makeCoordinatorFor($program);
    $objective = $program->objectives()->create(['code' => 'PEO1', 'statement' => 'Original statement.']);

    $this->actingAs($coordinator)
        ->patchJson("/api/v1/programs/{$program->id}/objectives/{$objective->id}", [
            'statement' => 'Revised statement.',
        ])
        ->assertStatus(422)
        ->assertJsonPath(
            'message',
            'Program objectives can only be changed while the program is in draft status.'
        );
});

test('an assigned coordinator can delete an objective, which is soft-deleted', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $objective = $program->objectives()->create(['code' => 'PEO1', 'statement' => 'To be removed.']);

    $this->actingAs($coordinator)
        ->deleteJson("/api/v1/programs/{$program->id}/objectives/{$objective->id}")
        ->assertNoContent();

    expect($program->objectives()->count())->toBe(0);
    expect($program->objectives()->withTrashed()->count())->toBe(1);
});

test('objectives cannot be deleted once the program is no longer in draft', function () {
    $program = Program::factory()->approved()->create();
    $coordinator = makeCoordinatorFor($program);
    $objective = $program->objectives()->create(['code' => 'PEO1', 'statement' => 'Cannot delete.']);

    $this->actingAs($coordinator)
        ->deleteJson("/api/v1/programs/{$program->id}/objectives/{$objective->id}")
        ->assertStatus(422);

    expect($program->objectives()->count())->toBe(1);
});

test('an objective is not reachable through a program it does not belong to', function () {
    $programA = Program::factory()->create();
    $programB = Program::factory()->create();
    $objective = $programB->objectives()->create(['code' => 'PEO1', 'statement' => 'Belongs to B.']);

    $this->actingAs(makeAdmin())
        ->getJson("/api/v1/programs/{$programA->id}/objectives/{$objective->id}")
        ->assertNotFound();
});

test('an unauthenticated request is rejected', function () {
    $program = Program::factory()->create();

    $this->getJson("/api/v1/programs/{$program->id}/objectives")
        ->assertUnauthorized();
});
