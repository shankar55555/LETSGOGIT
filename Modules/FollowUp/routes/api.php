<?php

use Illuminate\Support\Facades\Route;
use Modules\FollowUp\Http\Controllers\FollowUpController;
Route::middleware(['auth:sanctum'])->group(function () {
    // Use distinct API route names to avoid collision with web resource (followup.index)
    Route::apiResource('followup', FollowUpController::class)->names('followup-api');
});
