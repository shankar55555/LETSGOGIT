<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Http\Controllers\VendorController;

// Temporarily remove auth for testing
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('product/last-purchase-number', [ProductController::class, 'lastPurchaseNumber'])->name('last-purchase-number');
    Route::post('upload-image', [ProductController::class, 'uploadImage'])->name('upload-image');
    Route::post('delete-image', [ProductController::class, 'deleteImage'])->name('delete-image');
    Route::apiResource('product', ProductController::class)->names('product');

    // Vendor routes
    Route::apiResource('vendors', VendorController::class)->names('vendors');
});
