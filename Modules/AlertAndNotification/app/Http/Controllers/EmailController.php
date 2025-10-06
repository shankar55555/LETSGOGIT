<?php

namespace Modules\AlertAndNotification\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Mail\MailSend;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AlertAndNotification\Models\NotificationCategory;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\AlertAndNotification\Models\NotificationType;
use Modules\RolePermission\Constants\RolePermissionConst;
use Illuminate\Support\Facades\Mail;
use Modules\AlertAndNotification\Constants\EmailConst;
use Illuminate\Support\Facades\Storage;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Helpers\NotificationHelper;

class EmailController extends Controller
{
    const CONTROLLER_NAME = "Email Controller";

    protected $referer;
    protected $login_user;

    public function __construct()
    {
        $this->login_user = request()->user() ?? Auth::user() ?? null;
    }

    public function emailNotificationCount(Request $request)
    {
        try {
            $user = $this->login_user;
            $roleSlugs = $user->roles()->pluck('slug')->toArray();

            # Base query for email notifications
            $query = NotificationLog::query()
                ->where('section_type', CommonConst::EMAIL)
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

    public function emailLatestFiveNotificationList(Request $request)
    {
        try {
            $user = $this->login_user;
            $isAdmin = collect($user->roles()->pluck('slug'))->intersect([RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN,])->isNotEmpty();

            $query = NotificationLog::query()
                ->where('section_type', CommonConst::EMAIL)
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
            return $this->actionSuccess('email latest five notifications retrieved successfully.', $list);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function emailMarkAllReadOrUnRead(Request $request)
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

            $query = NotificationLog::query()->where('section_type', CommonConst::EMAIL);

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

            $message = $isRead ? "All email notifications marked as read." : "All email notifications marked as unread.";
            return $this->actionSuccess($message);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function emailIsReadNotification(Request $request)
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
                ->where('section_type', CommonConst::EMAIL)
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
    /**
     * @group Notification Logs
     *
     * Get Email Log List
     *
     * This API retrieves a paginated list of email notification logs.  
     * You can filter by email, search text, status, module, and also sort the result.
     *
     * @authenticated
     *
     * @queryParam per_page int Optional. Number of items per page. Default: 100. Example: 50
     * @queryParam select_email string Optional. Filter by specific receiver email (UUID). Example: user-uuid-123
     * @queryParam sortBy string Optional. Sort by column key. Example: created_at
     * @queryParam orderBy string Optional. Sort order (`asc` or `desc`). Example: desc
     * @queryParam search string Optional. Search keyword for notification logs. Example: payment
     * @queryParam status string Optional. Filter by status. Example: sent
     * @queryParam module_id string Optional. Module ID for filtering related logs. Example: 123
     * @queryParam module_log_type string Optional. Type of module (lead, client, quotation, invoice). Example: lead
     */
    public function emailLogList(Request $request)
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

            $items_list = $this->_emailLogList(...$params);
            return  $this->actionSuccess('Get email Log List Successfully', customizingResponseData($items_list));
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    private function _emailLogList(int $per_page, ?string $select_email = null, ?string $sort_key = null, ?string $sort_order = null, ?string $search = null, ?string $status = null, ?string $module_id = null, ?string $module_log_type = null)
    {
        $user = $this->login_user;
        $roleSlugs = $user->roles()->pluck('slug')->toArray();

        $query = NotificationLog::query()
            ->where('section_type', CommonConst::EMAIL)
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

    public function emailUpdateReadStatusUpdate(Request $request)
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
                ->where('section_type', CommonConst::EMAIL)
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
                event(new NotificationMessage("", $userId, CommonConst::EMAIL));
            } catch (\Exception $e) {
                createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            }
            return $this->actionSuccess("Status updated successfully.", $log);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function emailStatusUpdate(Request $request)
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

    public function emailDeleteNotification(Request $request)
    {
        try {
            # $this->login_user
            $emailLog = NotificationLog::where('id', $request->notification_type_id)->where('section_type', CommonConst::EMAIL)->first();
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
    public function emailCategoryList()
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
    public function emailCreateUpdateTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:notification_template_sections,id',
            'title' => 'required|string',
            'email_body' => 'required|string',
            'email_subject' => 'required|string',
            'hidden_pre_header' => 'nullable|string',
            'is_enable' => 'required|in:Enable,Disable',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors()->first());
        }
        DB::beginTransaction();
        try {

            /** Handle Notification Type */
            $notification_type_id = $request->notification_type_id ?? null;
            if ($notification_type_id) {
                if (NotificationType::where('id', '!=', $notification_type_id)->where('title', $request->type_title)->exists()) {
                    return $this->actionFailure("Type title already exists!");
                }
                $type = NotificationType::where('id', $notification_type_id)->first();
                if ($type) {
                    $type->title = $request->type_title;
                    $type->description = $request->description ?? '';
                    $type->save();
                }
            }

            $data = $this->_emailCreateUpdateTemplate($request->only(['id', 'email_body', 'email_subject', 'hidden_pre_header', 'is_enable', 'title']));
            DB::commit();
            return $this->actionSuccess("Record saved successfully", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure("Creation failed");
        }
    }

    /**
     * Update or create a new email email template.
     *
     * @param array $data The template data.
     * @return \App\Models\NotificationTemplateSection
     */
    private function _emailCreateUpdateTemplate(array $data)
    {
        return NotificationTemplateSection::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'email_body' => $data['email_body'],
                'email_subject' => $data['email_subject'],
                'hidden_pre_header' => $data['hidden_pre_header'] ?? null,
                'title' => $data['title'],
                'is_enable' => $data['is_enable'],
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
    public function emailPreview(Request $request)
    {
        try {
            $category_name = NotificationCategory::whereHas('notification_types', function ($qu) use ($request) {
                $qu->where('id', $request->notification_type_id);
            })->pluck('category')->first();

            $body_data = getSendDataList($category_name, CommonConst::EMAIL);
            $mailContent = makeMessageContent($body_data, $request->notification_type_id, CommonConst::EMAIL);

            if ($mailContent->status) {
                return $this->actionSuccess("Get email data", $mailContent);
            }
            return $this->actionFailure($mailContent->message);
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
    public function emailSendNotification(Request $request)
    {
        try {
            $user = Auth::user();
            $receiver_contact = $user->email;
            $category_name = NotificationCategory::whereHas('notification_types', function ($qu) use ($request) {
                $qu->where('id', $request->notification_type_id);
            })->pluck('category')->first();

            $email_body = getSendDataList($category_name, CommonConst::EMAIL);
            $mailContent = makeMessageContent($email_body, $request->notification_type_id, CommonConst::EMAIL);

            if ($mailContent->status) {
                $receiver_id = $user->uuid;
                $sender_id = $user->uuid;
                $logData = [
                    'receiver_contact' => $receiver_contact,
                    'subject' => $mailContent->subject,
                    'content' => $mailContent->content,
                    'priority' => $mailContent->priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $request->notification_type_id,
                    'receiver_id' => $receiver_id,
                    'section_type' => CommonConst::EMAIL,
                    "is_notification" => false,
                    'email_body' => json_encode($email_body),
                    'sender_id' => $sender_id ?? null,
                    "module_id" => isset($body_data['id']) && $body_data['id'] ? $body_data['id'] : null,
                ];

                $emailLog = NotificationLog::create($logData);

                # 1. Clear all files in the directory
                Storage::disk('public')->deleteDirectory('email_attachments');
                Storage::disk('public')->makeDirectory('email_attachments');

                # 2. Generate PDF and save to public storage
                $pdf = Pdf::loadView('pdf.test_pdf', ['list' => []]);
                $filename = 'attachment_' . formattedDateTime() . '.pdf';
                $path = 'email_attachments/' . $filename;
                Storage::disk('public')->put($path, $pdf->output());

                # 3. Prepare email data
                $email_info = [
                    'subject' => $mailContent->subject,
                    'receiver_contact' => $receiver_contact,
                    'mail_log_id' => $emailLog->id,
                    'attachment_path' => 'public/' . $path,
                    'attachment_original_name' => $filename,
                    'hidden_pre_header' => $mailContent->hidden_pre_header,
                    'content' => $mailContent->simple_content,
                ];

                try {
                    Mail::to($receiver_contact)->send(new MailSend($email_info));
                    $emailLog->status = CommonConst::SUCCESS;
                    $emailLog->save();
                    $message = 'Mail Send Successfully';
                } catch (\Exception $e) {
                    createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
                    $message = $e->getMessage();
                    $emailLog->status = CommonConst::FAILED;
                    $emailLog->message = $e->getMessage();
                    $emailLog->save();
                }

                try {
                    event(new NotificationMessage($emailLog->subject, $receiver_id, CommonConst::EMAIL));
                } catch (\Exception $e) {
                    createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
                }
                return  $emailLog->status = CommonConst::SUCCESS ? $this->actionSuccess($message, $emailLog) : $this->actionFailure($message, $emailLog);
            }
            return $this->actionFailure($mailContent->message);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }
}
