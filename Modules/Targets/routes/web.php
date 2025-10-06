<?php

use Illuminate\Support\Facades\Route;
use Modules\Targets\Http\Controllers\TargetController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('targets', TargetController::class)->names('targets');
});
