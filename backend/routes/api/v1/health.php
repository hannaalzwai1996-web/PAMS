<?php

use Illuminate\Support\Facades\Route;

/*
| Unauthenticated liveness check — separate from the framework's built-in
| /up (which checks app boot only). This confirms the versioned API surface
| itself is reachable and responds with the standard envelope.
*/
Route::get('/health', function () {
    return response()->json([
        'data' => [
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ],
    ]);
})->name('health');
