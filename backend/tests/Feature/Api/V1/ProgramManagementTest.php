<?php

use App\Domain\Program\Models\Program;
use App\Models\Department;
use App\Models\User;
use App\Support\Enums\ProgramStatus;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

function validProgramPayload(array $overrides = []): array
{
    return array_merge([
        'department_id' => Department::factory()->create()->id,
        'code' => 'PRG-'.fake()->unique()->numberBetween(100, 99999),
        'name' => 'BSc Software Engineering',
        'level' => 'bachelor',
        'description' => 'A program description.',
        'duration_years' => 4,
    ], $overrides);
}

// --- Creation ---------------------------------------------------------

test('an admin can create a program', function () {
    $admin = makeAdmin();
    $department = Department::factory()->create();

    $response = $this->actingAs($admin)->postJson('/api/v1/programs', validProgramPayload([
        'department_id' => $department->id,
        'code' => 'PRG-001',
        'name' => 'BSc Computer Engineering',
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.code', 'PRG-001')
        ->assertJsonPath('data.name', 'BSc Computer Engineering')
        ->assertJsonPath('data.department.id', $department->id);

    expect(Program::where('code', 'PRG-001')->exists())->toBeTrue();
});

test('a program is created in draft status with version 1 regardless of what the client sends', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->postJson('/api/v1/programs', validProgramPayload([
        'code' => 'PRG-002',
        // A client cannot smuggle a status/version through the payload —
        // ProgramDTO doesn't even declare these fields.
        'status' => 'approved',
        'current_version_no' => 99,
    ]));

    $response->assertCreated()->assertJsonPath('data.status', 'draft');

    $program = Program::where('code', 'PRG-002')->firstOrFail();
    expect($program->status)->toBe(ProgramStatus::Draft);
    expect($program->current_version_no)->toBe(1);
});

test('a qa officer cannot create a program', function () {
    $qaOfficer = makeQaOfficer();

    $this->actingAs($qaOfficer)
        ->postJson('/api/v1/programs', validProgramPayload())
        ->assertForbidden();
});

test('a program coordinator cannot create a program', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->postJson('/api/v1/programs', validProgramPayload())
        ->assertForbidden();
});

test('an unauthenticated request cannot create a program', function () {
    $this->postJson('/api/v1/programs', validProgramPayload())->assertUnauthorized();
});

test('creating a program fails validation when required fields are missing', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/programs', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['department_id', 'code', 'name', 'level', 'duration_years']);
});

test('creating a program with an unknown level fails validation', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/programs', validProgramPayload(['level' => 'postdoc']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['level']);
});

test('creating a program with a duplicate code is rejected', function () {
    $admin = makeAdmin();
    Program::factory()->create(['code' => 'PRG-DUP']);

    $this->actingAs($admin)
        ->postJson('/api/v1/programs', validProgramPayload(['code' => 'PRG-DUP']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('creating a program with a non-existent department is rejected', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/programs', validProgramPayload(['department_id' => 999999]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['department_id']);
});

// --- Update -------------------------------------------------------------

test('an admin can update a draft program', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->patchJson("/api/v1/programs/{$program->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    expect($program->fresh()->name)->toBe('New Name');
});

test('the assigned coordinator can update their own draft program', function () {
    $program = Program::factory()->create(['name' => 'Old Name']);
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->patchJson("/api/v1/programs/{$program->id}", ['name' => 'Coordinator Edited'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Coordinator Edited');
});

test('a coordinator not assigned to the program cannot update it', function () {
    $program = Program::factory()->create();
    $outsider = User::factory()->create();
    $outsider->assignRole('program_coordinator');

    $this->actingAs($outsider)
        ->patchJson("/api/v1/programs/{$program->id}", ['name' => 'Should not apply'])
        ->assertForbidden();
});

test('a qa officer cannot update a program', function () {
    $program = Program::factory()->create();
    $qaOfficer = makeQaOfficer();

    $this->actingAs($qaOfficer)
        ->patchJson("/api/v1/programs/{$program->id}", ['name' => 'Should not apply'])
        ->assertForbidden();
});

test('a program cannot be updated once it is no longer in draft', function () {
    $admin = makeAdmin();
    $program = Program::factory()->approved()->create();

    $this->actingAs($admin)
        ->patchJson("/api/v1/programs/{$program->id}", ['name' => 'Should not apply'])
        ->assertStatus(422);
});

test('updating a program to a duplicate code is rejected', function () {
    $admin = makeAdmin();
    Program::factory()->create(['code' => 'PRG-TAKEN']);
    $program = Program::factory()->create(['code' => 'PRG-FREE']);

    $this->actingAs($admin)
        ->patchJson("/api/v1/programs/{$program->id}", ['code' => 'PRG-TAKEN'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('a program keeps its own code when updated with the same value', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create(['code' => 'PRG-SAME']);

    $this->actingAs($admin)
        ->patchJson("/api/v1/programs/{$program->id}", ['code' => 'PRG-SAME', 'name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.code', 'PRG-SAME');
});

// --- Delete ---------------------------------------------------------------

test('an admin can delete a draft program', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/v1/programs/{$program->id}")
        ->assertNoContent();

    expect(Program::find($program->id))->toBeNull();
    expect(Program::withTrashed()->find($program->id))->not->toBeNull();
});

test('a program coordinator cannot delete a program even if assigned to it', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->deleteJson("/api/v1/programs/{$program->id}")
        ->assertForbidden();

    expect(Program::find($program->id))->not->toBeNull();
});

test('a program cannot be deleted once it is no longer in draft', function () {
    $admin = makeAdmin();
    $program = Program::factory()->approved()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/v1/programs/{$program->id}")
        ->assertStatus(422);

    expect(Program::find($program->id))->not->toBeNull();
});

// --- Show -------------------------------------------------------------

test('show returns the department and coordinators', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($admin)
        ->getJson("/api/v1/programs/{$program->id}")
        ->assertOk()
        ->assertJsonPath('data.department.id', $program->department_id)
        ->assertJsonPath('data.coordinators.0.id', $coordinator->id);
});
