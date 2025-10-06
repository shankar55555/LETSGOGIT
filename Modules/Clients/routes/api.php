<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Http\Controllers\ClientController;

Route::get("/option-client-list", [ClientController::class, 'optionClientList']);
Route::apiResource('clients', ClientController::class)->names('clients');
Route::put('clients/{client}/status', [ClientController::class, 'updateStatus'])->name('clients.updateStatus');
Route::get("/client-attachments/{id}", [ClientController::class, 'clientAttachments'])->name('clients.clientAttachments');
