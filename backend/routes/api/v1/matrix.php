<?php

use App\Domain\LearningOutcome\Models\ObjectiveOutcomeMatrix;
use App\Http\Controllers\Api\V1\ProgramMatrixController;
use Illuminate\Support\Facades\Route;

/*
| The PO-PLO Matrix Generation Engine (ARCH-0002). All four abilities
| resolve through MatrixPolicy, explicitly registered against
| ObjectiveOutcomeMatrix::class (see AppServiceProvider) since every
| ability takes a Program, not a matrix row — hence the
| [ObjectiveOutcomeMatrix::class, program] middleware argument form,
| same mechanism used by program-objectives.php / learning-outcomes.php
| for their viewAny/create abilities.
*/
Route::middleware('auth.api')
    ->prefix('programs/{program}/matrix')
    ->name('programs.matrix.')
    ->group(function () {
        Route::get('/', [ProgramMatrixController::class, 'review'])
            ->middleware('can:view,'.ObjectiveOutcomeMatrix::class.',program')
            ->name('review');

        Route::post('/generate', [ProgramMatrixController::class, 'generate'])
            ->middleware('can:generate,'.ObjectiveOutcomeMatrix::class.',program')
            ->name('generate');

        Route::put('/', [ProgramMatrixController::class, 'update'])
            ->middleware('can:update,'.ObjectiveOutcomeMatrix::class.',program')
            ->name('update');

        Route::get('/export', [ProgramMatrixController::class, 'export'])
            ->middleware('can:export,'.ObjectiveOutcomeMatrix::class.',program')
            ->name('export');
    });
