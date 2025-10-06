<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('dashboard', DashboardController::class)->names('dashboard');

    Route::get('upcoming-team-events', [DashboardController::class, 'teamUpcomingEvents'])->name('team-upcoming-events');
    Route::get('upcoming-lead-events', [DashboardController::class, 'upcomingLeadEvents'])->name('upcoming-lead-events');
    Route::get('upcoming-client-events', [DashboardController::class, 'upcomingClientEvents'])->name('upcoming-client-events');

    Route::post('calendar-events', [DashboardController::class, 'calendarEvents'])->name('calendar-events');
    Route::get('upcoming-srm', [DashboardController::class, 'upcomingSiteRiskManagement'])->name('upcoming-srm');
    Route::get('/user-information-chart-list', [DashboardController::class, 'userInformationChartList']);
    Route::get('/lead-info-chart-list', [DashboardController::class, 'leadInfoChartList']);
    Route::get('/client-info-chart-list', [DashboardController::class, 'clientInfoChartList']);
});
