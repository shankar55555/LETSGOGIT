<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Http\Controllers\InvoiceController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Use distinct API route names to avoid collision with web resource (invoices.index)
    Route::apiResource('invoices', InvoiceController::class)->names('invoices-api');
    Route::post('pay-invoice', [InvoiceController::class, "payInvoice"]);
    Route::post('invoices/{id}/cancel', [InvoiceController::class, "cancelInvoice"]);
    Route::get('/invoice/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoice.pdf');
    Route::post('/invoice/status-update', [InvoiceController::class, 'invoiceStatusUpdate'])->name('invoice.status.update');
    Route::post('/invoice/send-message', [InvoiceController::class, 'invoiceSendMessage'])->name('invoice.send-message');
});
