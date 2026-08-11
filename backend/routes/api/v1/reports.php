<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

/*
| The Reporting Module. All four report types share the same
| authorization (can:view,program → ProgramPolicy — same read-only,
| program-scoped rule as viewing objectives/outcomes/matrix) and the same
| {format} constraint (pdf|excel) rather than separate routes per format,
| keeping the four report types visually distinct while the format is
| just a delivery detail.
*/
Route::middleware('auth.api')
    ->prefix('programs/{program}/reports')
    ->name('programs.reports.')
    ->where(['format' => 'pdf|excel'])
    ->group(function () {
        Route::get('/specification/{format}', [ReportController::class, 'specification'])
            ->middleware('can:view,program')
            ->name('specification');

        Route::get('/objectives/{format}', [ReportController::class, 'objectives'])
            ->middleware('can:view,program')
            ->name('objectives');

        Route::get('/learning-outcomes/{format}', [ReportController::class, 'learningOutcomes'])
            ->middleware('can:view,program')
            ->name('learning-outcomes');

        Route::get('/matrix/{format}', [ReportController::class, 'matrix'])
            ->middleware('can:view,program')
            ->name('matrix');
    });
