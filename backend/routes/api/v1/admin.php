<?php

use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
| Admin-only user management — this is PAMS's "registration" surface.
| Per SRS FR-USR-01/FR-USR-02 (and the "no self-service" assumption in the
| Requirements Analysis), there is no public sign-up route: accounts are
| created and role-assigned by an Administrator only.
|
| Authorization is Policy-driven via `can:` middleware (UserPolicy) — never
| an inline role check here (ADR-0001 §12, immutable).
*/
Route::middleware('auth.api')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:viewAny,'.User::class)
        ->name('users.index');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('can:create,'.User::class)
        ->name('users.store');

    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->middleware('can:update,user')
        ->name('users.update');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:delete,user')
        ->name('users.destroy');

    Route::post('/users/{user}/role', [UserController::class, 'assignRole'])
        ->middleware('can:assignRole,user')
        ->name('users.assign-role');

    Route::put('/users/{user}/permissions', [UserController::class, 'syncPermissions'])
        ->middleware('can:managePermissions,user')
        ->name('users.sync-permissions');
});
