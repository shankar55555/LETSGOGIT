<?php

use Illuminate\Support\Facades\Route;
use Modules\Targets\Http\Controllers\TargetController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('targets', TargetController::class)->names('targets');
});
