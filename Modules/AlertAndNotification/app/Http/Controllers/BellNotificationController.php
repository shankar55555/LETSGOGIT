<?php

namespace Modules\AlertAndNotification\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AlertAndNotification\Models\NotificationCategory;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\RolePermission\Constants\RolePermissionConst;
use Modules\AlertAndNotification\Constants\EmailConst;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Helpers\NotificationHelper;

class BellNotificationController extends Controller
{
    const CONTROLLER_NAME = "Bell Notification Controller";

    protected $referer;
    protected $login_user;

    public function __construct()
    {
        $this->login_user = request()->user() ?? Auth::user() ?? null;
    }

    public function bellNotificationCount(Request $request)
    {
        try {
            $user = $this->login_user;
            $roleSlugs = $user->roles()->pluck('slug')->toArray();

            # Base query for email notifications
            $query = NotificationLog::query()
                ->where('section_type', CommonConst::BELL_NOTIFICATION)
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
            return $this->actionSuccess("Unread notification count retrieved successfully.", [
                'un_read' => $unreadCount
            ]);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function bellLatestFiveNotificationList(Request $request)
    {
        try {
            $user = $this->login_user;
            $isAdmin = collect($user->roles()->pluck('slug'))->intersect([RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN,])->isNotEmpty();

            $query = NotificationLog::query()
                ->where('section_type', CommonConst::BELL_NOTIFICATION)
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
            return $this->actionSuccess('bell latest five notifications retrieved successfully.', $list);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function bellMarkAllReadOrUnRead(Request $request)
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

            $query = NotificationLog::query()->where('section_type', CommonConst::BELL_NOTIFICATION);

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

            $message = $isRead ? "All bell notifications marked as read." : "All bell notifications marked as unread.";
            return $this->actionSuccess($message);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function bellIsReadNotification(Request $request)
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
                ->where('section_type', CommonConst::BELL_NOTIFICATION)
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
    public function bellLogList(Request $request)
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

            $items_list = $this->_bellLogList(...$params);
            return  $this->actionSuccess('Get bell Log List Successfully', customizingResponseData($items_list));
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    private function _bellLogList(int $per_page, ?string $select_email = null, ?string $sort_key = null, ?string $sort_order = null, ?string $search = null, ?string $status = null, ?string $module_id = null, ?string $module_log_type = null)
    {
        $user = $this->login_user;
        $roleSlugs = $user->roles()->pluck('slug')->toArray();

        $query = NotificationLog::query()
            ->where('section_type', CommonConst::BELL_NOTIFICATION)
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

    public function bellUpdateReadStatusUpdate(Request $request)
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
                ->where('section_type', CommonConst::BELL_NOTIFICATION)
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
                event(new NotificationMessage("", $userId, CommonConst::BELL_NOTIFICATION));
            } catch (\Exception $e) {
                createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            }
            return $this->actionSuccess("Status updated successfully.", $log);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function bellStatusUpdate(Request $request)
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

    public function bellDeleteNotification(Request $request)
    {
        try {
            # $this->login_user
            $emailLog = NotificationLog::where('id', $request->notification_type_id)->first();
            if (!$emailLog) return $this->actionFailure("Log not found!");
            $emailLog->is_delete = true;
            $emailLog->save();
            return $this->actionFailure('Notification Soft Delete Successfully', $emailLog);
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
    public function bellCategoryList()
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
    public function bellCreateUpdateTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:notification_template_sections,id',
            // 'email_subject' => 'required|string',
            'bell_notification_message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }

        try {
            $data = $this->_bellCreateUpdateTemplate($request->only(['id', 'email_subject', 'bell_notification_message']));

            return $this->actionSuccess("Record saved successfully", $data);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure("Creation failed");
        }
    }

    /**
     * Update or create a new bell email template.
     *
     * @param array $data The template data.
     * @return \App\Models\NotificationTemplateSection
     */
    private function _bellCreateUpdateTemplate(array $data)
    {
        $bell_notification_message = str_replace(['amp;', 'amp'], '', nl2br(e($data['bell_notification_message'])));
        return NotificationTemplateSection::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'email_subject' => $data['email_subject'] ?? "Subject",
                'bell_notification_message' => $bell_notification_message ?? $data['bell_notification_message'],
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
    public function bellPreview(Request $request)
    {
        try {
            $category_name = NotificationCategory::whereHas('notification_types', function ($qu) use ($request) {
                $qu->where('id', $request->notification_type_id);
            })->pluck('category')->first();

            $body_data = getSendDataList($category_name);
            $content = makeMessageContent($body_data, $request->notification_type_id, CommonConst::BELL_NOTIFICATION);

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
    public function bellSendNotification(Request $request)
    {
        try {
            $user = Auth::user();
            $receiver_contact = $user->name ?? $user->user_name;
            $category_name = NotificationCategory::whereHas('notification_types', function ($qu) use ($request) {
                $qu->where('id', $request->notification_type_id);
            })->pluck('category')->first();

            $body_data = getSendDataList($category_name);
            $content = makeMessageContent($body_data, $request->notification_type_id, CommonConst::BELL_NOTIFICATION);

            if ($content->status) {
                $receiver_id = $user->uuid;
                $sender_id = $user->uuid;
                $logData = [
                    'receiver_contact' => $receiver_contact,
                    'subject' => $content->subject,
                    'content' => $content->bell_notification_message,
                    'priority' => $content->priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $request->notification_type_id,
                    'receiver_id' => $receiver_id,
                    'section_type' => CommonConst::BELL_NOTIFICATION,
                    "is_notification" => false,
                    'email_body' => json_encode($user),
                    'sender_id' => $sender_id ?? null,
                    "module_id" => isset($body_data['id']) && $body_data['id'] ? $body_data['id'] : null,
                ];

                $emailLog = NotificationLog::create($logData);
                try {
                    $bell_notification_message = str_replace(['<br>', '<br/>', '<br />'], "\n", $content->bell_notification_message);
                    event(new NotificationMessage($bell_notification_message, $receiver_id, CommonConst::BELL_NOTIFICATION));
                    $emailLog->status = CommonConst::SUCCESS;
                    $emailLog->save();
                } catch (\Exception $e) {
                    createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
                    $emailLog->status = CommonConst::FAILED;
                    $emailLog->message = $e->getMessage();
                    $emailLog->save();
                    return $this->actionFailure($e->getMessage(),   $emailLog);
                }
                return $this->actionSuccess('Notification Send Successfully', $emailLog);
            }
            return $this->actionFailure($content->bell_notification_message);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }
}
