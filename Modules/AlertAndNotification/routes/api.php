<?php

use Illuminate\Support\Facades\Route;
use Modules\AlertAndNotification\Http\Controllers\AppController;
use Modules\AlertAndNotification\Http\Controllers\BellNotificationController;
use Modules\AlertAndNotification\Http\Controllers\BToBController;
use Modules\AlertAndNotification\Http\Controllers\EmailController;
use Modules\AlertAndNotification\Http\Controllers\NotificationController;
use Modules\AlertAndNotification\Http\Controllers\RuleController;
use Modules\AlertAndNotification\Http\Controllers\WhatsAppController;
use Modules\AlertAndNotification\Http\Controllers\SmsController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/rule-status-update/{rule_id}', [RuleController::class, 'ruleStatusUpdate'])->name('ruleStatusUpdate');
    Route::get('/get-trigger-events', [RuleController::class, 'getTriggerEvents']);
    Route::apiResource('rules', RuleController::class)->names('rules');

    Route::get('/all-notification-count', [RuleController::class, 'allNotificationCount']);
    Route::get('/all-notification-latest-five-list', [RuleController::class, 'allNotificationLatestFiveList']);

    # Create Notification
    Route::post('/dropdown-nofication-list', [NotificationController::class, 'dropdownNoficationList']);
    Route::post('/create-notification', [NotificationController::class, 'createNotification']);
    Route::delete('/notification-category/delete/{notification_category_id}', [NotificationController::class, 'deleteNotificationCategory']);
    Route::delete('/notification-type/delete/{notification_type_id}', [NotificationController::class, 'deleteNotificationType']);

    # B2B api function
    Route::group(['prefix' => 'b2b'], function () {
        Route::get('/option-list', [BToBController::class, 'optionLeadList']);
        Route::get('/list', [BToBController::class, 'b2bUserList']);
        Route::post('/create', [BToBController::class, 'b2bUserCreate']);
        Route::post('/update', [BToBController::class, 'b2bUserUpdate']);
        Route::post('/status-update/{b2b_id}', [BToBController::class, 'b2bStatusUpdate'])->name('b2b.statusUpdate');
        Route::post('/user-import', [BToBController::class, 'BToBUserImport']);
        Route::post('/reachout-send-message', [BToBController::class, 'reachoutSendMessage']);
        Route::delete('/delete/{b2b_id}', [BToBController::class, 'b2bUserDelete']);
    });

    # Email api function
    Route::group(['prefix' => 'email'], function () {
        # Notification api
        Route::get('/notification-count', [EmailController::class, 'emailNotificationCount']);
        Route::get('/latest-five-notification-list', [EmailController::class, 'emailLatestFiveNotificationList']);
        Route::post('/mark-all-read-or-un-read', [EmailController::class, 'emailMarkAllReadOrUnRead']);
        Route::post('/is-read-notification', [EmailController::class, 'emailIsReadNotification']);

        # Notification Log api
        Route::get('/log-list', [EmailController::class, 'emailLogList']);
        Route::post('/update-read-status', [EmailController::class, 'emailUpdateReadStatusUpdate']);
        Route::post('/status-update', [EmailController::class, 'emailStatusUpdate'])->name('email.status.update');
        Route::delete('/delete-notification/{notification_type_id}', [EmailController::class, 'emailDeleteNotification']);

        # Utility api
        Route::get('/category-list', [EmailController::class, 'emailCategoryList']);
        Route::put('/create-update-template/{notification_type_id}', [EmailController::class, 'emailCreateUpdateTemplate']);
        Route::post('/preview', [EmailController::class, 'emailPreview']);
        Route::post('/send-notification', [EmailController::class, 'emailSendNotification']);
    });

    # Whats App api function
    Route::group(['prefix' => 'whatsApp'], function () {
        # Notification api
        Route::get('/notification-count', [WhatsAppController::class, 'whatsAppNotificationCount']);
        Route::get('/latest-five-notification-list', [WhatsAppController::class, 'whatsAppLatestFiveNotificationList']);
        Route::post('/mark-all-read-or-un-read', [WhatsAppController::class, 'whatsAppMarkAllReadOrUnRead']);
        Route::post('/is-read-notification', [WhatsAppController::class, 'whatsAppIsReadNotification']);

        # Notification Log api
        Route::get('/log-list', [WhatsAppController::class, 'whatsAppLogList']);
        Route::post('/update-read-status', [WhatsAppController::class, 'whatsAppUpdateReadStatusUpdate']);
        Route::post('/status-update', [WhatsAppController::class, 'whatsAppStatusUpdate'])->name('whatsApp.status.update');
        Route::delete('/delete-notification/{notification_type_id}', [WhatsAppController::class, 'whatsAppDeleteNotification']);

        # Utility api
        Route::get('/category-list', [WhatsAppController::class, 'whatsAppCategoryList']);
        Route::put('/create-update-template/{notification_type_id}', [WhatsAppController::class, 'whatsAppCreateUpdateTemplate']);
        Route::post('/preview', [WhatsAppController::class, 'whatsAppPreview']);
        Route::post('/send-notification', [WhatsAppController::class, 'whatsAppSendNotification']);
        Route::post('/reachout-send-message', [WhatsAppController::class, 'reachoutSendMessage']);
    });

    # Sms api function
    Route::group(['prefix' => 'sms'], function () {
        # Notification api
        Route::get('/notification-count', [SmsController::class, 'smsNotificationCount']);
        Route::get('/latest-five-notification-list', [SmsController::class, 'smsLatestFiveNotificationList']);
        Route::post('/mark-all-read-or-un-read', [SmsController::class, 'smsMarkAllReadOrUnRead']);
        Route::post('/is-read-notification', [SmsController::class, 'smsIsReadNotification']);

        # Notification Log api
        Route::get('/log-list', [SmsController::class, 'smsLogList']);
        Route::post('/update-read-status', [SmsController::class, 'smsUpdateReadStatusUpdate']);
        Route::post('/status-update', [SmsController::class, 'smsStatusUpdate'])->name('sms.status.update');
        Route::delete('/delete-notification/{notification_type_id}', [SmsController::class, 'smsDeleteNotification']);

        # Utility api
        Route::get('/category-list', [SmsController::class, 'smsCategoryList']);
        Route::put('/create-update-template/{notification_type_id}', [SmsController::class, 'smsCreateUpdateTemplate']);
        Route::post('/preview', [SmsController::class, 'smsPreview']);
        Route::post('/send-notification', [SmsController::class, 'smsSendNotification']);
    });

    # App api function
    Route::group(['prefix' => 'app'], function () {
        # Notification api
        Route::get('/notification-count', [AppController::class, 'appNotificationCount']);
        Route::get('/latest-five-notification-list', [AppController::class, 'appLatestFiveNotificationList']);
        Route::post('/mark-all-read-or-un-read', [AppController::class, 'appMarkAllReadOrUnRead']);
        Route::post('/is-read-notification', [AppController::class, 'appIsReadNotification']);

        # Notification Log api
        Route::get('/log-list', [AppController::class, 'appLogList']);
        Route::post('/update-read-status', [AppController::class, 'appUpdateReadStatusUpdate']);
        Route::post('/status-update', [AppController::class, 'appStatusUpdate'])->name('app.status.update');
        Route::delete('/delete-notification/{notification_type_id}', [AppController::class, 'appDeleteNotification']);

        # Utility api
        Route::get('/category-list', [AppController::class, 'appCategoryList']);
        Route::put('/create-update-template/{notification_type_id}', [AppController::class, 'appCreateUpdateTemplate']);
        Route::post('/preview', [AppController::class, 'appPreview']);
        Route::post('/send-notification', [AppController::class, 'appSendNotification']);
    });

    # Bell Notification api function
    Route::group(['prefix' => 'bell'], function () {
        # Notification api
        Route::get('/notification-count', [BellNotificationController::class, 'bellNotificationCount']);
        Route::get('/latest-five-notification-list', [BellNotificationController::class, 'bellLatestFiveNotificationList']);
        Route::post('/mark-all-read-or-un-read', [BellNotificationController::class, 'bellMarkAllReadOrUnRead']);
        Route::post('/is-read-notification', [BellNotificationController::class, 'bellIsReadNotification']);

        # Notification Log api
        Route::get('/log-list', [BellNotificationController::class, 'bellLogList']);
        Route::post('/update-read-status', [BellNotificationController::class, 'bellUpdateReadStatusUpdate']);
        Route::post('/status-update', [BellNotificationController::class, 'bellStatusUpdate'])->name('bell.status.update');
        Route::delete('/delete-notification/{notification_type_id}', [BellNotificationController::class, 'bellDeleteNotification']);

        # Utility api
        Route::get('/category-list', [BellNotificationController::class, 'bellCategoryList']);
        Route::put('/create-update-template/{notification_type_id}', [BellNotificationController::class, 'bellCreateUpdateTemplate']);
        Route::post('/preview', [BellNotificationController::class, 'bellPreview']);
        Route::post('/send-notification', [BellNotificationController::class, 'bellSendNotification']);
    });
});
