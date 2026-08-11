<?php

use App\Domain\Program\Models\Program;
use App\Filament\Resources\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use App\Models\Department;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an admin can render the department list page', function () {
    Livewire::actingAs(makeAdmin())
        ->test(ListDepartments::class)
        ->assertSuccessful();
});

test('an admin can create a department through the Filament form', function () {
    Livewire::actingAs(makeAdmin())
        ->test(CreateDepartment::class)
        ->fillForm(['code' => 'CS', 'name' => 'Computer Science'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Department::where('code', 'CS')->exists())->toBeTrue();
});

test('an admin cannot delete a department that still has programs assigned', function () {
    $department = Department::factory()->create();
    Program::factory()->create(['department_id' => $department->id]);

    Livewire::actingAs(makeAdmin())
        ->test(EditDepartment::class, ['record' => $department->id])
        ->callAction('delete');

    expect(Department::find($department->id))->not->toBeNull();
});
