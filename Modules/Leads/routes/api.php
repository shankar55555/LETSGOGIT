<?php

use Illuminate\Support\Facades\Route;
use Modules\Leads\Http\Controllers\LeadController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get("/option-lead-list", [LeadController::class, 'optionLeadList'])->name('option.lead.list');
    Route::POST("/update-direct-lead-status", [LeadController::class, 'updateDirectLeadStatus']);
    Route::get("/lead-attachments/{id}", [LeadController::class, 'leadAttachments'])->name('lead.attachments');

    // Excel Import/Export Routes
    Route::get('leads/download-sample', [LeadController::class, 'downloadSample']);
    Route::post('leads/import', [LeadController::class, 'import']);
    Route::get('leads/export', [LeadController::class, 'export']);
    Route::get('/dashboard-lead-list', [LeadController::class, 'dashboardLeadList']);

    Route::apiResource('leads', LeadController::class)->names('leads');
});
