<?php

use App\Constants\CommonConst;
use Carbon\Carbon;
use App\Mail\MailSend;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Models\NotificationType;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\AlertAndNotification\Constants\EmailConst;
use Modules\AlertAndNotification\Jobs\EmailJob;
use Modules\AlertAndNotification\Models\NotificationVariable;
use Modules\Clients\Models\Client;
use Modules\Leads\Models\Lead;
use Modules\RolePermission\Constants\RolePermissionConst;
use Stevebauman\Location\Facades\Location;

const EMAIL_HELPER = 'Helpers / Email Helper';

/**
 * Get a list of all Admin and Super Admin users (including soft-deleted).
 *
 * @return array List of users with basic info
 */
function adminAndSuperAdminUserList()
{
    i(EMAIL_HELPER . " Fetching admin and super admin user list.");
    $users = User::withTrashed()->whereHas('user_role', function ($qu) {
        $qu->whereHas('role', function ($q) {
            $q->whereIn('slug', [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN]);
        });
    })->select('uuid', 'name', 'user_name', 'email', 'country_code', 'phone')->get()->toArray();

    i(EMAIL_HELPER . " Total admin/super-admin users found: " . count($users));
    return $users;
}

/**
 * Get email-related user info by ID and type.
 *
 * @param string|null $user_id
 * @param string|null $type "Lead", "Client", or null for normal User
 * @return array
 */
function emailUserInfo(?string $user_id = null, ?string $type = null)
{
    i(EMAIL_HELPER . " Fetching email user info for ID: $user_id | Type: $type");

    $list = [];

    if ($user_id) {
        if ($type === "Lead") {
            $list = Lead::withTrashed()->where('id', $user_id)
                ->select('id as uuid', 'name', 'name as user_name', 'email', 'country_code', 'phone')
                ->get()->toArray();
        } elseif ($type === "Client") {
            $list = Client::withTrashed()->where('id', $user_id)
                ->select('id as uuid', 'name', 'name as user_name', 'email', 'country_code', 'phone')
                ->get()->toArray();
        } else {
            $list = User::withTrashed()->where('uuid', $user_id)
                ->select('uuid', 'name', 'user_name', 'email', 'country_code', 'phone')
                ->get()->toArray();
        }
    }

    i(EMAIL_HELPER . " User info found: " . json_encode($list));
    return $list ?? [];
}


/**
 * Get formatted HTML device info for email notifications.
 *
 * @param \Illuminate\Http\Request $request
 * @return string
 */
function addEmailDeviceInfo($request)
{
    $browser = $request->header('User-Agent');
    $ip = getIpAddress();
    $location = Location::get($ip);

    $country = $location?->countryName ?? null;
    $state   = $location?->regionName ?? null;
    $city    = $location?->cityName ?? null;

    $time = Carbon::now()->format('M, d Y H:i:s A');

    $info = "<strong style='font-size: 20px;'>Device Information : </strong><br>";
    $info .= "<strong>When</strong> - $time<br>";
    $info .= "<strong>IP Address</strong> - $ip<br>";
    $info .= "<strong>Device Type</strong> - $browser<br>";

    if ($country || $state || $city) {
        $info .= "<strong>Location</strong> - ";
        $parts = array_filter([$city, $state, $country]);
        $info .= implode(', ', $parts) . "<br>";
    }

    i(EMAIL_HELPER . " Email device info generated: " . strip_tags($info));
    return $info;
}


/**
 * Get device info in plain text for SMS or internal logs.
 *
 * @param \Illuminate\Http\Request $request
 * @return string
 */
function addMessageDeviceInfo($request)
{
    $browser = $request->header('User-Agent');
    $ip = getIpAddress();
    $location = Location::get($ip);

    $country = $location?->countryName ?? null;
    $state   = $location?->regionName ?? null;
    $city    = $location?->cityName ?? null;

    $time = Carbon::now()->format('M, d Y H:i:s A');

    $info = "Device Information : ";
    $info .= "When - $time, ";
    $info .= "IP Address - $ip, ";
    $info .= "Device Type - $browser, ";

    if ($country || $state || $city) {
        $parts = array_filter([$city, $state, $country]);
        $info .= "Location - " . implode(', ', $parts);
    }

    i(EMAIL_HELPER . " Message device info generated: " . $info);
    return $info;
}

/**
 * Prepare email/message content using template and variables.
 *
 * @param array $replacements Key-value pairs for placeholder replacement
 * @param int $notification_type_id Template type (login, password reset, etc.)
 * @param string $type Notification channel (EMAIL, WHATSAPP, etc.)
 * @return object Result with content and status
 */
function makeMessageContent($replacements, $notification_type_id, $type)
{
    try {
        $template = NotificationTemplateSection::where('notification_type_id', $notification_type_id)->first();

        if (!$template) {
            er(EMAIL_HELPER . " Email template not found for type_id: $notification_type_id");
            return (object)['status' => false, "message" => "Email Template Not Found!"];
        }

        $setting = getSettingInfo();
        $replacements['company_name'] = $setting['company_name'] ?? 'No Name';

        $templateFields = [
            CommonConst::EMAIL => 'email_body',
            CommonConst::WHATSAPP => 'whats_app_message',
            CommonConst::SMS => 'sms_message',
            CommonConst::BELL_NOTIFICATION => 'bell_notification_message',
            CommonConst::APP => 'app_message',
        ];
        $responseKeys = [
            CommonConst::EMAIL => ['content', 'simple_content'],
            CommonConst::WHATSAPP => 'whats_app_message',
            CommonConst::SMS => 'sms_message',
            CommonConst::BELL_NOTIFICATION => 'bell_notification_message',
            CommonConst::APP => 'app_message',
        ];

        $content = $template->{$templateFields[$type] ?? 'email_body'};
        $priority = $template->priority;
        $hidden_pre_header = $template->hidden_pre_header;
        $subject = $template->email_subject;

        if ($type === CommonConst::EMAIL && $template->is_enable === 'Disable') {
            w(EMAIL_HELPER . " Template disabled. Email will not be sent.");
            return (object)['status' => false, "message" => 'Template is disabled. So not email send.'];
        }

        $variables = NotificationVariable::where('notification_type_id', $notification_type_id)->pluck('variables');

        foreach ($variables as $variable) {
            if (isset($replacements[$variable])) {
                $value = $replacements[$variable];
                $content = str_replace("[[**$variable**]]", $value, $content);
                $subject = str_replace("[[**$variable**]]", $value, $subject);
                $content = str_replace("[[***$variable***]]", "<a href='$value'>Link</a>", $content);
                $hidden_pre_header = str_replace("[[**$variable**]]", $value, $hidden_pre_header);
            }
        }

        $response = [
            'status' => true,
            'hidden_pre_header' => $hidden_pre_header,
            'subject' => $subject,
            'priority' => $priority,
        ];

        switch ($type) {
            case CommonConst::EMAIL:
                $response['content'] = (string)view('email.email_content', compact('content', 'hidden_pre_header', 'setting'));
                $response['simple_content'] = $content;
                break;
            case CommonConst::WHATSAPP:
            case CommonConst::SMS:
            case CommonConst::APP:
            case CommonConst::BELL_NOTIFICATION:
                $response[$responseKeys[$type]] = $content;
                break;
        }

        i(EMAIL_HELPER . " makeMessageContent generated successfully for type_id: $notification_type_id");
        return (object)$response;
    } catch (\Exception $e) {
        er(EMAIL_HELPER . " Exception in makeMessageContent: " . $e->getMessage());
        createExceptionError($e, EMAIL_HELPER, __FUNCTION__);
        return (object)['status' => false, "message" => $e->getMessage()];
    }
}


/**
 * Send an email notification and log the attempt in the NotificationLog table.
 *
 * @param array $email_content_info The email body data used to generate the email content.
 * @param array $additional_info Additional parameters such as:
 *                                - notification_type_id (int)
 *                                - receiver_contact (string)
 *                                - receiver_id (int)
 *                                - sender_id (int|null)
 *                                - module_id (int|null)
 *                                - is_notification (bool)
 *                                - attachment_path (string|null)
 *                                - attachment_original_name (string|null)
 * @return object An object containing status (bool), message (string), and optionally email_log (NotificationLog)
 */
function commonSendEmailFun($email_content_info, $additional_info)
{
    i(EMAIL_HELPER . " commonSendEmailFun STARTED");
    i(EMAIL_HELPER . " Email content info: " . json_encode($email_content_info));
    i(EMAIL_HELPER . " Additional info: " . json_encode($additional_info));

    $notification_type_id = $additional_info['notification_type_id'];
    $receiver_contact = $additional_info['receiver_contact'];
    $is_notification = $additional_info['is_notification'] ?? true;

    $mailContent = makeMessageContent($email_content_info, $notification_type_id, CommonConst::EMAIL);

    if ($mailContent->status) {
        i(EMAIL_HELPER . " Mail content created successfully. Subject: {$mailContent->subject}");

        $additional_info['hidden_pre_header'] =  $mailContent->hidden_pre_header;

        try {
            $receiver_id = $additional_info['receiver_id'];

            $logData = [
                'receiver_contact' => $receiver_contact,
                'subject' => $mailContent->subject,
                'content' => $mailContent->content,
                'priority' => $mailContent->priority,
                'status' => CommonConst::PENDING,
                'notification_type_id' => $notification_type_id,
                'receiver_id' => $receiver_id,
                'section_type' => CommonConst::EMAIL,
                'is_notification' => $is_notification,
                'email_body' => json_encode($email_content_info),
                'additional_info' => json_encode($additional_info),
                'sender_id' => $additional_info['sender_id'] ?? null,
                'module_id' => $additional_info['module_id'] ?? null
            ];

            $email_log = NotificationLog::create($logData);

            if ($email_log) {
                i(EMAIL_HELPER . " NotificationLog created with ID: {$email_log->id}");

                try {
                    if (!$is_notification) {
                        $email_info = [
                            'subject' => $mailContent->subject,
                            'attachment_path' => $additional_info['attachment_path'] ?? null,
                            'attachment_original_name' => $additional_info['attachment_original_name'] ?? null,
                            'hidden_pre_header' => $mailContent->hidden_pre_header,
                            'content' => $mailContent->simple_content,
                        ];

                        i(EMAIL_HELPER . " Sending email to: {$receiver_contact}");
                        Mail::to($receiver_contact)->send(new MailSend($email_info));
                        i(EMAIL_HELPER . " Email sent successfully to: {$receiver_contact}");
                    } else {
                        w(EMAIL_HELPER . " Notification-only flag is true. Email not actually sent to: {$receiver_contact}");
                    }

                    $email_log->status = CommonConst::SUCCESS;
                    $email_log->save();
                    i(EMAIL_HELPER . " Email log updated to SUCCESS for ID: {$email_log->id}");

                    i(EMAIL_HELPER . " commonSendEmailFun COMPLETED");
                    return (object)['status' => true, 'email_log' => $email_log];
                } catch (\Exception $e) {
                    er(EMAIL_HELPER . " Email sending failed: " . $e->getMessage());
                    createExceptionError($e, COMMON_HELPER, __FUNCTION__);

                    $email_log->status = CommonConst::FAILED;
                    $email_log->message = $e->getMessage();
                    $email_log->save();

                    return (object)['status' => false, 'message' => $e->getMessage(), 'email_log' => $email_log];
                }
            }
        } catch (\Exception $e) {
            er(EMAIL_HELPER . " Failed to create NotificationLog: " . $e->getMessage());
            createExceptionError($e, COMMON_HELPER, __FUNCTION__);
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    w(EMAIL_HELPER . " Mail content creation failed: " . $mailContent->message);
    return (object)['status' => false, 'message' => $mailContent->message];
}

/**
 * Notify all admins (excluding the logged-in user) about an admin login event.
 *
 * Sends notification-only emails (no Gmail delivery) to all admins/super admins
 * when a user logs into the admin panel. The message includes device info and user identity.
 *
 * @param \App\Models\User $user     The logged-in user.
 * @param \Illuminate\Http\Request $request  The request object to extract device/browser info.
 * @return object  Status object: {status: bool, message: string, list?: array}
 */
function adminAddLoginUserLog($user, $request)
{
    i(EMAIL_HELPER . " adminAddLoginUserLog STARTED for user: {$user->uuid} ({$user->name})");

    try {
        $notification_type = NotificationType::where('type_key', CommonConst::ACCOUNT_LOGIN)
            ->select('id', 'title')->first();

        if (!$notification_type) {
            er(EMAIL_HELPER . " NotificationType not found for ACCOUNT_LOGIN");
            return (object)['status' => false, 'message' => 'Notification type not found'];
        }

        // $additional_info = [
        //     'notification_type_id' => $notification_type->id,
        //     'sender_id' => $user->uuid,
        //     'attachment_path' => null,
        //     'attachment_original_name' => null,
        //     "is_notification" => false, # Gmail account Send Mail
        //     'module_id' => null
        // ];
        // $log_id = null;
        // EmailJob::dispatch($log_id, $adminList, $email_content_info, $additional_info);
        // return;

        $request_device_info = addEmailDeviceInfo($request);
        i(EMAIL_HELPER . " Request device info: " . json_encode($request_device_info));

        $adminList = adminAndSuperAdminUserList();
        i(EMAIL_HELPER . " Admin list fetched: " . json_encode($adminList));

        $email_content_info = [
            'name' => $user->name,
            'request_device_info' => $request_device_info,
        ];

        $not_send = false;
        $list = [];

        foreach ($adminList as $admin) {
            if ($admin['uuid'] === $user->uuid) {
                w(EMAIL_HELPER . " Skipping email to logged-in user: {$user->uuid}");
                continue;
            }

            $additional_info = [
                'receiver_id' => $admin['uuid'],
                'receiver_contact' => $admin['email'],
                'notification_type_id' => $notification_type->id,
                'sender_id' => $user->uuid,
                'attachment_path' => null,
                'attachment_original_name' => null,
                'is_notification' => true, // Only notification, no email
                'module_id' => null
            ];

            i(EMAIL_HELPER . " Sending login notification to admin: {$admin['uuid']} ({$admin['email']})");

            $info = commonSendEmailFun($email_content_info, $additional_info);

            if (!$info->status) {
                $not_send = true;
                $list[] = $info->message;
                er(EMAIL_HELPER . " Failed to send login notification to {$admin['email']}: {$info->message}");
            } else {
                i(EMAIL_HELPER . " Notification sent successfully to: {$admin['email']}");
            }
        }

        if ($not_send) {
            w(EMAIL_HELPER . " One or more notifications failed.");
            return (object)['status' => false, 'list' => $list];
        }

        i(EMAIL_HELPER . " adminAddLoginUserLog COMPLETED successfully");
        return (object)['status' => true, 'message' => 'Mail sent successfully'];
    } catch (\Exception $e) {
        er(EMAIL_HELPER . " Exception occurred in adminAddLoginUserLog: " . $e->getMessage());
        createExceptionError($e, EMAIL_HELPER, __FUNCTION__);
        return (object)['status' => false, 'message' => $e->getMessage()];
    }
}
