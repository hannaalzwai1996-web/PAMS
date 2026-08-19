<?php

use App\Http\Controllers\Api\V1\DepartmentController;
use App\Models\Department;
use Illuminate\Support\Facades\Route;

/*
| Read-only — the Program create/edit form's department picker (P0.1).
| Department itself stays Filament-managed (DepartmentService already
| exists and is unchanged); this only exposes the existing list to the SPA.
*/
Route::middleware('auth.api')
    ->prefix('departments')
    ->name('departments.')
    ->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])
            ->middleware('can:viewAny,'.Department::class)
            ->name('index');
    });
