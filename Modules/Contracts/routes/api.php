<?php

use Illuminate\Support\Facades\Route;
use Modules\Contracts\Http\Controllers\ContractController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Use a distinct base name to prevent collision with web resource names (contracts.index)
    Route::apiResource('contracts', ContractController::class)->names('contracts-api');
});
