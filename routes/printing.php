<?php

use App\Http\Controllers\Api\PrintAgentController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('print-agent')->group(function () {

        /*
    |--------------------------------------------------------------------------
    | Synchronization
    |--------------------------------------------------------------------------
    */

        Route::get('/jobs', [
            PrintAgentController::class,
            'jobs',
        ]);


        /*
    |--------------------------------------------------------------------------
    | Print Job Status
    |--------------------------------------------------------------------------
    */

        Route::post('/jobs/{uuid}/processing', [
            PrintAgentController::class,
            'processing',
        ]);

        Route::post('/jobs/{uuid}/complete', [
            PrintAgentController::class,
            'complete',
        ]);

        Route::post('/jobs/{uuid}/failed', [
            PrintAgentController::class,
            'failed',
        ]);
    });
});
