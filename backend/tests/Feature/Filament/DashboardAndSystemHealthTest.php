<?php

use App\Domain\Program\Models\Program;
use App\Filament\Pages\SystemHealth;
use App\Filament\Widgets\ProgramStatsWidget;
use App\Filament\Widgets\UserStatsWidget;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Pages\Dashboard;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an admin can render the dashboard with the stats widgets', function () {
    User::factory()->count(2)->create()->each(fn (User $u) => $u->assignRole('program_coordinator'));
    Program::factory()->count(3)->create();

    Livewire::actingAs(makeAdmin())
        ->test(Dashboard::class)
        ->assertSuccessful();
});

test('the user stats widget reports accurate counts', function () {
    $admin = makeAdmin();
    $inactive = User::factory()->create(['is_active' => false]);
    $inactive->assignRole('qa_officer');

    Livewire::actingAs($admin)
        ->test(UserStatsWidget::class)
        ->assertSuccessful()
        ->assertSee('Total Users')
        ->assertSee('2') // admin + inactive qa_officer
        ->assertSee('1 inactive');
});

test('the program stats widget reports accurate counts', function () {
    Program::factory()->approved()->create();
    Program::factory()->create(); // draft

    Livewire::actingAs(makeAdmin())
        ->test(ProgramStatsWidget::class)
        ->assertSuccessful()
        ->assertSee('Total Programs')
        ->assertSee('2');
});

test('an admin can view the system health page and it reports a healthy database', function () {
    Livewire::actingAs(makeAdmin())
        ->test(SystemHealth::class)
        ->assertSuccessful()
        ->assertSet('checks.Database.status', 'ok');
});

test('the refresh action re-runs the health checks', function () {
    Livewire::actingAs(makeAdmin())
        ->test(SystemHealth::class)
        ->callAction('refresh')
        ->assertSet('checks.Cache.status', 'ok');
});
