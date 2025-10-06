<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotations\Http\Controllers\QuotationController;
use Modules\Quotations\Http\Controllers\GstChallanController;
use App\Http\Controllers\SettingController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get("/option-quotation-client-list", [QuotationController::class, 'optionClientList']);
    Route::get("/option-quotation-lead-list", [QuotationController::class, 'optionLeadList'])->name('option.lead.list');
    Route::apiResource('quotations', QuotationController::class)->names('quotations');
    Route::post('update-direct-quotation-status', [QuotationController::class, 'updateDirectQuotationStatus']);
    Route::post('generate-invoices', [QuotationController::class, 'generateInvoices']);
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'downloadPdf'])->name('quotations.pdf');
    Route::post('/quotation/send-message', [QuotationController::class, 'quotationSendMessage']);
    Route::post('/quotations/gst-challan-pdf', [QuotationController::class, 'downloadGstChallanPdf']);


    /**************************GST Challlan  ***************************/
    Route::post('/quotations/gst-challans/store', [GstChallanController::class, 'store']);


    // GST Challan Routes
    // Route::prefix('quotations/gst-challans')->group(function () {
    //     Route::get('/', 'GstChallanController@index');
    //     Route::post('/store', 'GstChallanController@store');
    //     Route::get('/{id}', 'GstChallanController@show');
    //     Route::put('/{id}', 'GstChallanController@update');
    //     Route::delete('/{id}', 'GstChallanController@destroy');
    //     Route::post('/{id}/restore', 'GstChallanController@restore');
    //     Route::post('/generate-pdf', 'GstChallanController@generatePdf');
    // });
});
