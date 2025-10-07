<?php
// routes/api.php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TableHeaderManageController;
use App\Http\Controllers\Api\UserTargetController;
use App\Http\Controllers\Api\UserAttendanceController;
use App\Http\Controllers\CountryController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'signIn']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/log-unauthenticated-access', [AuthController::class, 'logUnauthenticatedAccess']);
Route::get('/dropdown-user-list', [UserController::class, 'dropdownUserList'])->name('dropdown.user.list');
Route::post('/update-password/{user_id}', [UserController::class, 'updatePassword'])->name('update-password');
Route::post('/setting-list', [SettingController::class, 'index'])->name('setting.list');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
Route::post('/validate-reset-token', [AuthController::class, 'validateResetToken'])->name('validate-reset-token');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
Route::get('/reset-password-view', [AuthController::class, 'ResetPasswordView'])->name('reset-password-view');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::get('/logout', [AuthController::class, 'logout']);

    # User Login Log api
    Route::get('/user-login-logs', [AuthController::class, 'getUserLoginLogs']);
    Route::delete('/delete-login-log/{login_log_id}', [AuthController::class, 'deleteLoginLog'])->name('delete.login.log');

    # User api Name
    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [UserController::class, 'index'])->name('user.index');
        Route::get('/user-activity-timeline/{user_id}', [UserController::class, 'getUserActivityTimelineList'])->name('user.activity.timeline');
        Route::get('/{user_id}', [UserController::class, 'show'])->name('user.show');
        Route::post('/option-list', [UserController::class, 'userOptionList'])->name('user.option.list');
        Route::post('/status-update', [UserController::class, 'userStatusUpdate'])->name('user.status.update');
        Route::post('/role-update', [UserController::class, 'userRoleUpdate'])->name('user.role.update');
        Route::post('/create', [UserController::class, 'store'])->name('user.create');
        Route::post('/update', [UserController::class, 'update'])->name('user.update');
        Route::delete('/{user_id}', [UserController::class, 'destroy'])->name('user.delete');
    });

    # Table Header Manage Api
    Route::group(['prefix' => 'table-header'], function () {
        Route::get('/get', [TableHeaderManageController::class, 'getTableHeaders']);
        Route::post('/save', [TableHeaderManageController::class, 'saveTableHeaders']);
        Route::post('/sync', [TableHeaderManageController::class, 'tableHeaderSync']);
    });

    # Settings api Name
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/', [SettingController::class, 'update'])->name('settings.update');
        Route::put('/term-condition', [SettingController::class, 'termsUpdate'])->name('settings.termsUpdate');
        # For status setting
        Route::get('/page', [SettingController::class, 'pageList'])->name('settings.pageList');
        Route::get('/status-list', [SettingController::class, 'pageStatusList'])->name('settings.pageStatusList');
        Route::post('/page-status-create', [SettingController::class, 'pageStatusCreate'])->name('settings.pageStatusCreate');
        Route::post('/status-update/{status_id}', [SettingController::class, 'statusUpdate'])->name('settings.statusUpdate');
        Route::post('/change-color-status/{status_id}', [SettingController::class, 'changeColorStatus'])->name('settings.changeColorStatus');
        Route::put('/page-status-update/{status_id}', [SettingController::class, 'pageStatusUpdate'])->name('settings.pageStatusUpdate');
        Route::delete('/page-status-delete/{status_id}', [SettingController::class, 'pageStatusDelete'])->name('settings.pageStatusDelete');
    });

    // RESTFUL resource routes (index, store, show, update, destroy) for User Targets
    Route::resource('user-targets', UserTargetController::class);
    Route::put('/{userTarget}/update-incentive-amount', [UserTargetController::class, 'updateIncentiveAmount']);
    Route::post('/user-targets/mark-as-paid', [UserTargetController::class, 'markAsPaid']);

    // RESTFUL resource routes (index, store, show, update, destroy) for User Attendance
    Route::resource('user-attendance', UserAttendanceController::class);
    Route::get('/user-attendance/export', [UserAttendanceController::class, 'userAttendanceExportList']);
    Route::put('/{userAttendance}/update-status', [UserAttendanceController::class, 'updateStatus']);
    Route::post('/updateShift-time', [UserAttendanceController::class, 'updateShiftOutTime']);
    Route::get('/check-todays-attendance', [UserAttendanceController::class, 'checkTodaysAttendance']);

    # Country, State, City Option List
    Route::get('/dropdown-phone-code-emoji-list', [CountryController::class, 'getPhoneCodeEmojiList'])->name('dropdown.phone.code.emoji.list');
    Route::get('/dropdown-country-list', [CountryController::class, 'dropdownCountryList'])->name('dropdown.country.list');
    Route::get('/dropdown-state-list', [CountryController::class, 'dropdownStateList'])->name('dropdown.state.list');
    Route::get('/dropdown-city-list', [CountryController::class, 'dropdownCityList'])->name('dropdown.city.list');

    # Country Info
    Route::post('/country-list', [CountryController::class, 'countryList'])->name('country.list');
    Route::post('/country-create', [CountryController::class, 'countryCreate'])->name('country.create');
    Route::post('/country-update', [CountryController::class, 'countryUpdate'])->name('country.update');
    Route::post('/country-delete', [CountryController::class, 'countryDelete'])->name('country.delete');

    # State Info
    Route::post('/state-list', [CountryController::class, 'stateList'])->name('state.list');
    Route::post('/state-create', [CountryController::class, 'stateCreate'])->name('state.create');
    Route::post('/state-update', [CountryController::class, 'stateUpdate'])->name('state.update');
    Route::post('/state-delete', [CountryController::class, 'stateDelete'])->name('state.delete');

    # City Info
    Route::get('/city-list/{id}', [CountryController::class, 'getCityById']);
    Route::post('/city-list', [CountryController::class, 'cityList'])->name('city.list');
    Route::post('/city-create', [CountryController::class, 'cityCreate'])->name('city.create');
    Route::post('/city-update', [CountryController::class, 'cityUpdate'])->name('city.update');
    Route::post('/city-delete', [CountryController::class, 'cityDelete'])->name('city.delete');
});


Route::get('/calculate-salary', [UserAttendanceController::class, 'calculate']);
