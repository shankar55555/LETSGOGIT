<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounts\app\Http\Controllers\AccountController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('accounts', AccountController::class)->names('accounts');
});
