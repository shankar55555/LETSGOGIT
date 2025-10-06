<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounts\Http\Controllers\AccountController;
use Modules\Accounts\Http\Controllers\PurchaseBillController;
use Modules\Accounts\Http\Controllers\JournalEntryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Purchase Bills Routes
    Route::apiResource('purchase-bills', PurchaseBillController::class)->names('purchase-bills');

    // Journal Entry Routes
    Route::get('journal-entries/statistics', [JournalEntryController::class, 'statistics'])->name('journal-entries.statistics');
    Route::patch('journal-entries/{journalEntry}/status', [JournalEntryController::class, 'updateStatus'])->name('journal-entries.update-status');
    Route::apiResource('journal-entries', JournalEntryController::class)->names('journal-entries');

    // Accounts/Groups Routes
    Route::get('accounts/parent-options', [AccountController::class, 'getParentOptions'])->name('accounts.parent-options');
    Route::get('accounts/ledgers', [AccountController::class, 'getLedgers'])->name('accounts.ledgers');
    Route::get('accounts/balance-sheet', [AccountController::class, 'getBalanceSheet'])->name('accounts.balance-sheet');
    Route::get('accounts/profit-and-loss', [AccountController::class, 'getProfitAndLoss'])->name('accounts.profit-and-loss');
    // Use a distinct base name to avoid colliding with web routes ('accounts.index')
    Route::apiResource('accounts', AccountController::class)->names('accounts-api');
});
