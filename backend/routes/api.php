<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Entry point only. Per ADR-0001 §7, every endpoint is versioned under
| /api/v1 and each domain module gets its own route file under
| routes/api/v1/ — as each module is built (course-mappings.php,
| accreditation.php, quality-reports.php are still future work). Nothing
| beyond routing wiring belongs in this file.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    require __DIR__.'/api/v1/health.php';
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/admin.php';
    require __DIR__.'/api/v1/programs.php';
    require __DIR__.'/api/v1/program-objectives.php';
    require __DIR__.'/api/v1/learning-outcomes.php';
    require __DIR__.'/api/v1/matrix.php';
    require __DIR__.'/api/v1/reports.php';
});
