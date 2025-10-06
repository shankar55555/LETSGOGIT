<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;

Route::middleware(['auth'])->group(function () {
    Route::get('{any?}', function () {
        return view('application');
    })->where('any', '.*');
});
