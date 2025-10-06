<?php

use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/test-job', [TestController::class, 'test']);
Route::get('/test-notification/{module}/{ruleSlug}', [TestController::class, 'testNotification']);
Route::get('/test-dispatch-job/{rule_slug}', [TestController::class, 'testDispatchJob']);

Route::get('/test-mail-send', [TestController::class, 'testMailSend']);
// Route::get('reset-password-view', [App\Http\Controllers\Api\AuthController::class, 'ResetPasswordView'])->name('reset-password-view');


Route::get('/manifest.webmanifest', [App\Http\Controllers\ManifestController::class, 'manifest']);


Route::get('{any?}', function () {
    return view('application');
})->where('any', '.*');

// now getting this url on redirecting http://127.0.0.1:8000/.well-known/appspecific/com.chrome.devtools.json
