<?php

use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Http\Controllers\Api\V1\LearningOutcomeController;
use Illuminate\Support\Facades\Route;

/*
| Nested under /programs/{program}/learning-outcomes, mirroring
| program-objectives.php's pattern (scopeBindings(), the [Class, program]
| middleware argument form for viewAny/create).
|
| The child parameter is named {learning_outcome}, not {outcome}: Laravel's
| scoped-binding resolver guesses the parent relationship method from
| Str::plural(Str::camel($routeParamName)) — "learning_outcome" guesses
| "learningOutcomes", matching Program::learningOutcomes() exactly, while
| "outcome" would have guessed "outcomes", which doesn't exist.
*/
Route::middleware('auth.api')
    ->scopeBindings()
    ->prefix('programs/{program}/learning-outcomes')
    ->name('programs.learning-outcomes.')
    ->group(function () {
        Route::get('/', [LearningOutcomeController::class, 'index'])
            ->middleware('can:viewAny,'.LearningOutcome::class.',program')
            ->name('index');

        Route::post('/', [LearningOutcomeController::class, 'store'])
            ->middleware('can:create,'.LearningOutcome::class.',program')
            ->name('store');

        Route::get('/{learning_outcome}', [LearningOutcomeController::class, 'show'])
            ->middleware('can:view,learning_outcome')
            ->name('show');

        Route::patch('/{learning_outcome}', [LearningOutcomeController::class, 'update'])
            ->middleware('can:update,learning_outcome')
            ->name('update');

        Route::delete('/{learning_outcome}', [LearningOutcomeController::class, 'destroy'])
            ->middleware('can:delete,learning_outcome')
            ->name('destroy');

        // The PO-PLO Matrix: replaces this outcome's Program Objective correlations.
        Route::put('/{learning_outcome}/objectives', [LearningOutcomeController::class, 'syncObjectiveMappings'])
            ->middleware('can:manageMappings,learning_outcome')
            ->name('sync-objectives');
    });
