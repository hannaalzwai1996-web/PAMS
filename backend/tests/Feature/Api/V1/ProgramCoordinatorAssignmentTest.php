<?php

use App\Domain\Program\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an admin can assign a program coordinator user to a program', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');

    $this->actingAs($admin)
        ->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $coordinator->id])
        ->assertOk()
        ->assertJsonPath('data.coordinators.0.id', $coordinator->id);

    expect($program->fresh()->hasCoordinator($coordinator))->toBeTrue();
});

test('a program can have more than one coordinator (schema is many-to-many, not one-to-one)', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();
    $first = User::factory()->create();
    $first->assignRole('program_coordinator');
    $second = User::factory()->create();
    $second->assignRole('program_coordinator');

    $this->actingAs($admin)->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $first->id])->assertOk();

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $second->id])
        ->assertOk();

    expect($response->json('data.coordinators'))->toHaveCount(2);
    expect($program->fresh()->coordinators()->count())->toBe(2);
});

test('a qa officer cannot assign a coordinator', function () {
    $qaOfficer = makeQaOfficer();
    $program = Program::factory()->create();
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');

    $this->actingAs($qaOfficer)
        ->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $coordinator->id])
        ->assertForbidden();
});

test('a program coordinator cannot assign a coordinator, even to their own program', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);
    $another = User::factory()->create();
    $another->assignRole('program_coordinator');

    $this->actingAs($coordinator)
        ->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $another->id])
        ->assertForbidden();
});

test('an unauthenticated request cannot assign a coordinator', function () {
    $program = Program::factory()->create();
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');

    $this->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $coordinator->id])
        ->assertUnauthorized();
});

test('a user without the program coordinator role is rejected', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();
    $qaOfficer = makeQaOfficer();

    $this->actingAs($admin)
        ->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $qaOfficer->id])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Only users with the Program Coordinator role can be assigned to a program.');

    expect($program->fresh()->hasCoordinator($qaOfficer))->toBeFalse();
});

test('assigning the same coordinator twice is rejected as a conflict', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($admin)
        ->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => $coordinator->id])
        ->assertStatus(409);

    expect($program->fresh()->coordinators()->count())->toBe(1);
});

test('assigning a non-existent user is rejected', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/programs/{$program->id}/coordinators", ['user_id' => '01k00000000000000000000000'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);
});

test('an admin can unassign a coordinator', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/programs/{$program->id}/coordinators/{$coordinator->id}")
        ->assertOk();

    expect($program->fresh()->hasCoordinator($coordinator))->toBeFalse();
});

test('unassigning a user who is not a coordinator on the program is rejected', function () {
    $admin = makeAdmin();
    $program = Program::factory()->create();
    $notACoordinator = User::factory()->create();
    $notACoordinator->assignRole('program_coordinator');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/programs/{$program->id}/coordinators/{$notACoordinator->id}")
        ->assertStatus(404);
});

test('a qa officer cannot unassign a coordinator', function () {
    $qaOfficer = makeQaOfficer();
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($qaOfficer)
        ->deleteJson("/api/v1/programs/{$program->id}/coordinators/{$coordinator->id}")
        ->assertForbidden();

    expect($program->fresh()->hasCoordinator($coordinator))->toBeTrue();
});
