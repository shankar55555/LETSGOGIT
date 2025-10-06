<?php

use Illuminate\Support\Facades\Route;
use Modules\Targets\Http\Controllers\TargetController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Use distinct API route names to avoid collision with web resource (targets.index)
    Route::apiResource('targets', TargetController::class)->names('targets-api');
});
