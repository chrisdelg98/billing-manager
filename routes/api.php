<?php

use App\Http\Controllers\Api\LicenseStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/license/status', [LicenseStatusController::class, 'show'])
        ->middleware('throttle:license-api')
        ->name('api.v1.license.status');
});
