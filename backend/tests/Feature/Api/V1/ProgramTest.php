<?php

use App\Domain\Program\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an admin sees every program', function () {
    Program::factory()->count(3)->create();
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->getJson('/api/v1/programs')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('a qa officer sees every program', function () {
    Program::factory()->count(2)->create();
    $qaOfficer = User::factory()->create();
    $qaOfficer->assignRole('qa_officer');

    $this->actingAs($qaOfficer)
        ->getJson('/api/v1/programs')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('a coordinator only sees their assigned programs', function () {
    $assigned = Program::factory()->create();
    Program::factory()->create(); // not assigned to this coordinator
    $coordinator = makeCoordinatorFor($assigned);

    $this->actingAs($coordinator)
        ->getJson('/api/v1/programs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $assigned->id);
});

test('the response includes department and objective/outcome counts', function () {
    $program = Program::factory()->create();
    $program->objectives()->create(['code' => 'PEO1', 'statement' => 'Statement.']);
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->getJson('/api/v1/programs')
        ->assertOk()
        ->assertJsonPath('data.0.objectives_count', 1)
        ->assertJsonPath('data.0.learning_outcomes_count', 0)
        ->assertJsonPath('data.0.department.id', $program->department_id);
});

test('an unauthenticated request is rejected', function () {
    $this->getJson('/api/v1/programs')->assertUnauthorized();
});
