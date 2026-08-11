<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
| Sanctum cookie-based SPA auth (ADR-0001 §8). The React app first GETs
| /sanctum/csrf-cookie (registered automatically by Sanctum), then POSTs
| here — no bearer token is issued for this flow, the session cookie
| carries auth on subsequent /api/v1/* requests via the statefulApi()
| middleware configured in bootstrap/app.php.
*/
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('auth.login');

Route::middleware('auth.api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
});
