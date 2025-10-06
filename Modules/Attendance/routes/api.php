<?php
use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;
Route::middleware(['auth:sanctum'])->group(function () {
    // Get attendance records
    Route::get('/attendance/records', [AttendanceController::class, 'records']);
    // Record login
    Route::post('/attendance/login', [AttendanceController::class, 'login']);
    // Record logout
    Route::post('/attendance/logout', [AttendanceController::class, 'logout']);
    // Manual attendance entry
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::put('/attendance/{id}', [AttendanceController::class, 'update']);
});
