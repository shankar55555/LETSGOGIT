<?php

namespace Modules\AlertAndNotification\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AlertAndNotification\Models\NotificationCategory;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\RolePermission\Constants\RolePermissionConst;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Services\TwilioService;

class SmsController extends Controller
{
    const CONTROLLER_NAME = "Whats App Controller";

    protected $referer;
    protected $login_user;

    public function __construct()
    {
        $this->login_user = request()->user() ?? Auth::user() ?? null;
    }

    public function smsNotificationCount(Request $request)
    {
        try {
            $user = $this->login_user;
            $roleSlugs = $user->roles()->pluck('slug')->toArray();

            # Base query for email notifications
            $query = NotificationLog::query()
                ->where('section_type', CommonConst::SMS)
                ->where('is_delete', 0)
                ->with(['sender', 'notification_type']);

            # If not admin/super admin, filter by receiver
            if (!array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN])) {
                $query->where('receiver_id', $user->uuid);
            }

            # Unread notifications: where user's UUID is NOT in showing_user_ids
            $unreadCount = $query->where(function ($q) use ($user) {
                $q->whereNull('showing_user_ids')
                    ->orWhereRaw("NOT showing_user_ids::jsonb @> '\"{$user->uuid}\"'");
            })->count();
            if ($request->type == 'count') return $unreadCount;
            return $this->actionSuccess("Unread email notification count retrieved successfully.", [
                'un_read' => $unreadCount
            ]);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function smsLatestFiveNotificationList(Request $request)
    {
        try {
            $user = $this->login_user;
            $isAdmin = collect($user->roles()->pluck('slug'))->intersect([RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN,])->isNotEmpty();

            $query = NotificationLog::query()
                ->where('section_type', CommonConst::SMS)
                ->with(['sender', 'notification_type'])
                ->latest()
                ->limit(5);

            if (!$isAdmin) {
                $query->where('receiver_id', $user->uuid)
                    ->where('is_delete', 0);
            }
            # Get the relationships using the helper
            $with = (new NotificationHelper())->onlyUseNotificationLogGet();
            $query->with($with);
            $list = $query->get();
            if ($request->type == 'list') return $list;
            return $this->actionSuccess('sms latest five notifications retrieved successfully.', $list);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function smsMarkAllReadOrUnRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_read' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }

        try {
            $user = $this->login_user;
            $isRead = $request->is_read;

            $isAdmin = collect($user->roles()->pluck('slug'))->intersect([RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN,])->isNotEmpty();

            $query = NotificationLog::query()->where('section_type', CommonConst::SMS);

            if (!$isAdmin) {
                $query->where('receiver_id', $user->uuid)->where('is_delete', 0);
            }
            $notifications = $query->where('is_delete', 0)->get();

            foreach ($notifications as $log) {
                $ids = $log->showing_user_ids ?? [];

                if ($isRead) {
                    # Mark as read: Add user ID if not already present
                    if (!in_array($user->uuid, $ids)) {
                        $ids[] = $user->uuid;
                        $log->showing_user_ids = array_unique($ids);
                        $log->save();
                    }
                } else {
                    # Mark as unread: Remove user ID if present
                    if (in_array($user->uuid, $ids)) {
                        $updatedIds = array_values(array_diff($ids, [$user->uuid]));
                        $log->showing_user_ids = $updatedIds;
                        $log->save();
                    }
                }
            }

            $message = $isRead ? "All sms notifications marked as read." : "All sms notifications marked as unread.";
            return $this->actionSuccess($message);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function smsIsReadNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|uuid',
            'is_read' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }

        try {
            $userId = $this->login_user->uuid;

            $log = NotificationLog::where('id', $request->notification_id)
                ->where('section_type', CommonConst::SMS)
                ->first();

            if (!$log) {
                return $this->actionFailure("Notification not found or access denied.");
            }

            # Only update if user hasn't already marked it as read
            $showingUserIds = $log->showing_user_ids ?? [];
            if (!in_array($userId, $showingUserIds)) {
                $log->showing_user_ids = array_unique([...$showingUserIds, $userId]);
                $log->save();
            }

            return $this->actionSuccess("Status updated successfully.", $log);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    # Notification Log Function Api Start -->
    public function smsLogList(Request $request)
    {
        try {
            $params = [
                'per_page'     => $request->input('per_page', 100),
                'select_email' => $request->input('select_email'),
                'sort_key'     => $request->input('sortBy'),
                'sort_order'   => $request->input('orderBy'),
                'search'       => $request->input('search'),
                'status'       => $request->input('status'),
                'module_id'    => $request->input('module_id') ?? null,
                'module_log_type' => $request->input('module_log_type') ?? null,
            ];

            $items_list = $this->_smsLogList(...$params);
            return  $this->actionSuccess('Get sms Log List Successfully', customizingResponseData($items_list));
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    private function _smsLogList(int $per_page, ?string $select_email = null, ?string $sort_key = null, ?string $sort_order = null, ?string $search = null, ?string $status = null, ?string $module_id = null, ?string $module_log_type = null)
    {
        $user = $this->login_user;
        $roleSlugs = $user->roles()->pluck('slug')->toArray();

        $query = NotificationLog::query()
            ->where('section_type', CommonConst::SMS)
            ->search($search)
            ->with(['sender', 'notification_type']);
        if ($status) $query->where('status', $status);

        if ($module_id && $module_log_type) {
            $query = filterNotificationQuery($query, $module_id, $module_log_type);
        } else {
            if (!array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN])) {
                $query->where('receiver_id', $user->uuid)
                    ->where('is_delete', 0);
            } elseif ($select_email) {
                $query->where('receiver_id', $select_email);
            }
        }

        if ($sort_key && $sort_order) {
            $query->orderBy($sort_key, $sort_order);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        # Get the relationships using the helper
        $with = (new NotificationHelper())->onlyUseNotificationLogGet();
        $query->with($with);
        return $query->paginate($per_page);
    }

    public function smsUpdateReadStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator->fails(), $validator->errors());
        }
        try {
            $userId = $this->login_user->uuid;
            $log = NotificationLog::where('id', $request->id)
                ->where('section_type', CommonConst::SMS)
                ->first();

            if (!$log) {
                return $this->actionFailure("Notification not found or access denied.");
            }

            # Only update if user hasn't already marked it as read
            $showingUserIds = $log->showing_user_ids ?? [];
            if (!in_array($userId, $showingUserIds)) {
                $log->showing_user_ids = array_unique([...$showingUserIds, $userId]);
                $log->save();
            }
            try {
                event(new NotificationMessage("", $userId, CommonConst::SMS));
            } catch (\Exception $e) {
                createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            }
            return $this->actionSuccess("Status updated successfully.", $log);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function smsStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_log_id' => 'required|exists:notification_logs,id',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $log = NotificationLog::where('id', $request->notification_log_id)->first();
            if (!$log) return $this->actionFailure('Notification Log not Found!');
            $log->status = $request->status;
            $log->save();
            DB::commit();
            return $this->actionSuccess('Notification Log Status updated successfully.', $log);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function smsDeleteNotification(Request $request)
    {
        try {
            # $this->login_user
            $emailLog = NotificationLog::where('id', $request->notification_type_id)->first();
            if (!$emailLog) return $this->actionFailure("Email log not found!");
            $emailLog->is_delete = true;
            $emailLog->save();
            return $this->actionFailure('Email Notification Soft Delete Successfully', $emailLog);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    # Notification Utilities Function Api Start -->
    /**
     * It gets all the email categories and their types.
     * @group Mail Api
     * @param Request request The request object.
     * @authenticated
     * @return JSON response
     */
    public function smsCategoryList()
    {
        try {
            $list = NotificationCategory::with('notification_types')->get();
            return $this->actionSuccess("Whats App Category list get Successfully", $list);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    /**
     * Create or update an email template.
     * 
     * @group Mail Api
     * @param Request $request The request object.
     * @authenticated
     * @return JSON response
     */
    public function smsCreateUpdateTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:notification_template_sections,id',
            'email_subject' => 'required|string',
            'sms_message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }

        try {
            $data = $this->_smsCreateUpdateTemplate($request->only(['id',  'email_subject', 'sms_message']));

            return $this->actionSuccess("Record saved successfully", $data);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure("Creation failed");
        }
    }


    /**
     * Update or create a new sms email template.
     *
     * @param array $data The template data.
     * @return \App\Models\NotificationTemplateSection
     */
    private function _smsCreateUpdateTemplate(array $data)
    {
        $sms_message = str_replace(['amp;', 'amp'], '', nl2br(e($data['sms_message'])));
        return NotificationTemplateSection::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'email_subject' => $data['email_subject'],
                'sms_message' => $sms_message ?? $data['sms_message'],
            ]
        );
    }

    /**
     * It takes a request, gets the user, and then passes the user's name and email to the
     * `makeMessageContent` function
     * @group Mail Api
     * @param Request request The request object
     * @authenticated
     */
    public function smsPreview(Request $request)
    {
        try {
            $category_name = NotificationCategory::whereHas('notification_types', function ($qu) use ($request) {
                $qu->where('id', $request->notification_type_id);
            })->pluck('category')->first();

            $body_data = getSendDataList($category_name);
            $content = makeMessageContent($body_data,  $request->notification_type_id, CommonConst::SMS);

            if ($content->status) {
                return $this->actionSuccess("Whats App data get Successfully", $content);
            }
            return $this->actionFailure($content->message);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    /**
     * It takes the email template type id, creates the email content, and sends it to the test
     * receiver
     * @group Mail Api
     * @param Request request The request object
     * @authenticated
     */
    public function smsSendNotification(Request $request)
    {
        try {
            $user = Auth::user();

            if (empty($user->phone)) {
                return $this->actionFailure("User does not have a valid phone number.");
            }

            $category_name = NotificationCategory::whereHas('notification_types', function ($qu) use ($request) {
                $qu->where('id', $request->notification_type_id);
            })->pluck('category')->first();

            $body_data = getSendDataList($category_name);
            $content = makeMessageContent($body_data,  $request->notification_type_id, CommonConst::SMS);

            if (!$content->status) {
                return $this->actionFailure($content->message);
            }

            $receiver_contact = $user->country_code . $user->phone;
            $receiver_id = $user->uuid;
            # Create log
            $emailLog = NotificationLog::create([
                'receiver_contact' => $receiver_contact,
                'subject' => $content->subject,
                'content' => $content->sms_message,
                'priority' => $content->priority,
                'status' => CommonConst::PENDING,
                'notification_type_id' => $request->notification_type_id,
                'receiver_id' => $user->uuid,
                'sender_id' => $receiver_id,
                'section_type' => CommonConst::SMS,
                'is_notification' => false,
                'email_body' => json_encode($user),
                "module_id" => isset($body_data['id']) && $body_data['id'] ? $body_data['id'] : null,
            ]);

            # Generate and save PDF
            // $filename = 'sms_attachment_' . time() . '.pdf';
            // $path = 'sms_attachments/' . $filename;

            // $pdf = Pdf::loadView('pdf.test_pdf', ['data' => []]);
            // Storage::disk('public')->put($path, $pdf->output());

            // $fileUrl = asset('storage/' . $path);
            // $smsMessage = strip_tags($mailContent->sms_message) . "\n\nAttachment: " . $fileUrl;

            $smsMessage = strip_tags($content->sms_message);

            # Send SMS
            try {
                $twilioResponse = (new TwilioService())->sendTestSmsMediaMessage($receiver_contact, $smsMessage);
                $emailLog->status = $twilioResponse->status ? CommonConst::SUCCESS : CommonConst::FAILED;
                $emailLog->message = $twilioResponse->message ?? null;
                $emailLog->save();
                return $this->actionSuccess('SMS sent successfully.', $emailLog);
            } catch (\Exception $e) {
                $emailLog->status = CommonConst::FAILED;
                $emailLog->message = $e->getMessage();
                $emailLog->save();
            }

            try {
                event(new NotificationMessage($emailLog->subject, $receiver_id, CommonConst::EMAIL));
            } catch (\Exception $e) {
                createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            }
            return  $emailLog->status == CommonConst::SUCCESS ? $this->actionSuccess('SMS sent successfully.', $emailLog) :  $this->actionFailure($emailLog->message, $emailLog);;
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }
}
