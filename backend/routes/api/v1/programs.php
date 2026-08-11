<?php

use App\Domain\Program\Models\Program;
use App\Http\Controllers\Api\V1\ProgramController;
use Illuminate\Support\Facades\Route;

/*
| The only unscoped Program route — everything else (objectives,
| learning-outcomes, matrix, reports) nests under /programs/{program}/...
| and assumes the caller already has a program id. This is how they get
| one: admin/qa_officer see every program, a program_coordinator sees only
| the ones they're assigned to (ProgramService::list()).
*/
Route::middleware('auth.api')
    ->prefix('programs')
    ->name('programs.')
    ->group(function () {
        Route::get('/', [ProgramController::class, 'index'])
            ->middleware('can:viewAny,'.Program::class)
            ->name('index');
    });
