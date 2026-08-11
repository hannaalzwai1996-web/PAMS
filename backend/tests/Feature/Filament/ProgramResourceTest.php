<?php

use App\Domain\Program\Models\Program;
use App\Filament\Resources\ProgramResource;
use App\Filament\Resources\ProgramResource\Pages\ListPrograms;
use App\Filament\Resources\ProgramResource\Pages\ViewProgram;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an admin can render the program list page', function () {
    Program::factory()->count(3)->create();

    Livewire::actingAs(makeAdmin())
        ->test(ListPrograms::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Program::all());
});

test('an admin can view a program\'s detail page', function () {
    $program = Program::factory()->create();

    Livewire::actingAs(makeAdmin())
        ->test(ViewProgram::class, ['record' => $program->id])
        ->assertSuccessful();
});

test('the program resource offers no create, edit, or delete affordances', function () {
    expect(ProgramResource::canCreate())->toBeFalse();

    $program = Program::factory()->create();
    expect(ProgramResource::canEdit($program))->toBeFalse();
    expect(ProgramResource::canDelete($program))->toBeFalse();
});
