<?php

use App\Http\Controllers\Api\OpenClawTriggerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['openclaw.token'])->prefix('openclaw')->group(function () {
    Route::get('/triggers/pending', [OpenClawTriggerController::class, 'pending']);
    Route::post('/triggers/claim', [OpenClawTriggerController::class, 'claim']);
    Route::post('/triggers/{trigger}/ack', [OpenClawTriggerController::class, 'ack']);
    Route::post('/triggers/{trigger}/fail', [OpenClawTriggerController::class, 'fail']);
});

