<?php

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('a non-admin is forbidden from the admin panel', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');

    $this->actingAs($coordinator)->get('/admin/users')->assertForbidden();
});

test('a deactivated admin cannot access the admin panel', function () {
    $admin = User::factory()->create(['is_active' => false]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/users')->assertForbidden();
});

test('an admin can render the user list page', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)->get('/admin/users')->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->assertSuccessful();
});

test('an admin can create a user through the Filament form, routed through UserService', function () {
    $admin = makeAdmin();

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Filament Created',
            'email' => 'filament-created@example.com',
            'password' => 'a-strong-password',
            'is_active' => true,
            'role' => 'program_coordinator',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::where('email', 'filament-created@example.com')->firstOrFail();
    expect($created->hasRole('program_coordinator'))->toBeTrue();
});

test('an admin can edit a user and change their role through the Filament form', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();
    $target->assignRole('program_coordinator');

    Livewire::actingAs($admin)
        ->test(EditUser::class, ['record' => $target->id])
        ->fillForm([
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => true,
            'role' => 'qa_officer',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $target->refresh();
    expect($target->hasRole('qa_officer'))->toBeTrue();
    expect($target->hasRole('program_coordinator'))->toBeFalse();
});

test('an admin editing their own record cannot deactivate themselves via the panel', function () {
    $admin = makeAdmin();

    Livewire::actingAs($admin)
        ->test(EditUser::class, ['record' => $admin->id])
        ->fillForm([
            'name' => $admin->name,
            'email' => $admin->email,
            'is_active' => false,
            'role' => 'admin',
        ])
        ->call('save');

    expect($admin->fresh()->is_active)->toBeTrue();
});
