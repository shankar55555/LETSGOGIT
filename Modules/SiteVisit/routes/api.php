<?php

use Illuminate\Support\Facades\Route;
use Modules\SiteVisit\Http\Controllers\SiteVisitController;
use Modules\SiteVisit\Http\Controllers\SiteRiskManagementController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Site Visit Routes
    Route::apiResource('site-visit', SiteVisitController::class)->names('site.visit');
    Route::get('generate-challan', [SiteVisitController::class, 'generateChallan']);
    Route::post('update-direct-visitSite-status', [SiteVisitController::class, 'StatusUpdate']);

    // Site Risk Management Routes
    Route::prefix('site-visit/{id}')->group(function () {
        Route::get('risk-management', [SiteRiskManagementController::class, 'show']);
        Route::post('risk-management', [SiteRiskManagementController::class, 'store']);
        Route::get('site-risk-media', [SiteRiskManagementController::class, 'getSiteRiskMedia']);
        Route::post('site-risk-media', [SiteRiskManagementController::class, 'uploadSiteRiskMedia']);
        Route::delete('site-risk-media/{media_id}', [SiteRiskManagementController::class, 'deleteSiteRiskMedia']);
    });
});
