<?php

use App\Domain\Program\Models\Program;
use App\Http\Controllers\Api\V1\ProgramController;
use Illuminate\Support\Facades\Route;

/*
| Program CRUD (P0.1) and coordinator assignment (P0.2). Everything else
| (objectives, learning-outcomes, matrix, reports) nests under
| /programs/{program}/... in its own route file and assumes the caller
| already has a program id — this is how they get one: admin/qa_officer
| see every program, a program_coordinator sees only the ones they're
| assigned to (ProgramService::list()).
|
| No scopeBindings() here: unlike {objective}/{learning_outcome}, none of
| these params are children resolved through a Program relationship —
| {program} is the top-level resource itself, and {coordinator} is a
| plain User looked up by id, unrelated to Program's own binding tree.
*/
Route::middleware('auth.api')
    ->prefix('programs')
    ->name('programs.')
    ->group(function () {
        Route::get('/', [ProgramController::class, 'index'])
            ->middleware('can:viewAny,'.Program::class)
            ->name('index');

        Route::post('/', [ProgramController::class, 'store'])
            ->middleware('can:create,'.Program::class)
            ->name('store');

        Route::get('/{program}', [ProgramController::class, 'show'])
            ->middleware('can:view,program')
            ->name('show');

        Route::patch('/{program}', [ProgramController::class, 'update'])
            ->middleware('can:update,program')
            ->name('update');

        Route::delete('/{program}', [ProgramController::class, 'destroy'])
            ->middleware('can:delete,program')
            ->name('destroy');

        Route::post('/{program}/coordinators', [ProgramController::class, 'assignCoordinator'])
            ->middleware('can:assignCoordinator,program')
            ->name('coordinators.store');

        Route::delete('/{program}/coordinators/{coordinator}', [ProgramController::class, 'unassignCoordinator'])
            ->middleware('can:assignCoordinator,program')
            ->name('coordinators.destroy');
    });
