<?php

use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Http\Controllers\Api\V1\ProgramObjectiveController;
use Illuminate\Support\Facades\Route;

/*
| Nested under /programs/{program}/objectives. scopeBindings() makes
| Laravel resolve {objective} through $program->objectives() instead of
| the global program_objectives table, so an objective belonging to a
| different program 404s automatically — no manual ownership check
| needed in the Controller or Service.
|
| viewAny/create pass [ProgramObjective::class, program] so the Policy
| gets the parent Program even though no objective instance exists yet
| (ARCH-0001-style Policy resolution — see Gate::callPolicyMethod).
*/
Route::middleware('auth.api')
    ->scopeBindings()
    ->prefix('programs/{program}/objectives')
    ->name('programs.objectives.')
    ->group(function () {
        Route::get('/', [ProgramObjectiveController::class, 'index'])
            ->middleware('can:viewAny,'.ProgramObjective::class.',program')
            ->name('index');

        Route::post('/', [ProgramObjectiveController::class, 'store'])
            ->middleware('can:create,'.ProgramObjective::class.',program')
            ->name('store');

        Route::get('/{objective}', [ProgramObjectiveController::class, 'show'])
            ->middleware('can:view,objective')
            ->name('show');

        Route::patch('/{objective}', [ProgramObjectiveController::class, 'update'])
            ->middleware('can:update,objective')
            ->name('update');

        Route::delete('/{objective}', [ProgramObjectiveController::class, 'destroy'])
            ->middleware('can:delete,objective')
            ->name('destroy');
    });
