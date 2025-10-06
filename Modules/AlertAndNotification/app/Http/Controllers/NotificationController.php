<?php

namespace Modules\AlertAndNotification\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AlertAndNotification\Models\NotificationCategory;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\AlertAndNotification\Models\NotificationType;
use Modules\AlertAndNotification\Models\NotificationVariable;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    const CONTROLLER_NAME = "Notification Controller";

    protected $login_user;

    public function __construct()
    {
        $this->login_user = request()->user() ?? Auth::user() ?? null;
    }

    /**
     *
     * Get Dropdown Notification Data
     *
     * This endpoint retrieves data for dropdowns used in the notification module.
     * If a `notification_category_id` is provided, it fetches related notification types,
     * template sections, and variables. If not provided, it returns all categories.
     * 
     * @group Notifications
     * @authenticated
     * @bodyParam notification_category_id int optional The ID of the notification category to filter results.
     *
     * @param  \Illuminate\Http\Request  $request  The request instance containing an optional notification_category_id.
     * @return \Illuminate\Http\JsonResponse JSON response with dropdown data.
     */
    public function dropdownNoficationList(Request $request)
    {
        $notification_category_id = $request->notification_category_id;
        $list = [];

        # Always return category list (filtered if ID provided)
        # $list['categoryList'] = NotificationCategory::when($notification_category_id, function ($query) use ($notification_category_id) {
        #     $query->where('id', $notification_category_id);
        # })->select('id', 'category')->get();

        if ($notification_category_id) {
            # Notification Types
            $list['notification_types'] = NotificationType::where('category_id', $notification_category_id)
                ->select('id', 'title')->get();

            # Templates related to category
            $list['templates'] = NotificationTemplateSection::whereHas('notification_type', function ($query) use ($notification_category_id) {
                $query->where('category_id', $notification_category_id);
            })->get();

            $list['variables'] = $this->getVariables($notification_category_id);
        } else {
            # If no category selected, return all categories
            $list['categories'] = NotificationCategory::select('id', 'category')->get();
        }

        return $this->actionSuccess('Notification Options retrieved successfully.', (object) $list);
    }

    public function getVariables($notification_category_id)
    {
        return NotificationVariable::whereHas('notification_type', function ($query) use ($notification_category_id) {
            $query->where('category_id', $notification_category_id);
        })
            ->distinct('variables')
            ->get();
    }

    /**
     * Create or update a notification template
     *
     * This endpoint allows creating or updating a notification template, along with its category,
     * type, template section (email, SMS, WhatsApp, app), and associated variables.
     *
     * This method handles:
     * - Validation of request data
     * - Creation or reuse of notification category and type
     * - Creation or update of notification template section
     * - Setup of associated notification variables
     *
     * @group Notifications
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request HTTP request containing the notification data.
     * @return \Illuminate\Http\JsonResponse JSON success or failure response.
     *
     * @bodyParam id integer Optional. If provided, updates the existing notification.
     * @bodyParam title string required The notification template title. Example: "Welcome Notification"
     * @bodyParam email_subject string required The subject line for the email. Example: "You're Invited!"
     * @bodyParam is_enable boolean required Whether the notification is enabled. Example: true
     * @bodyParam hidden_pre_header string required The pre-header text for the email. Example: "Read this message now!"
     * @bodyParam priority integer required Priority of the notification (1 = High, 2 = Medium, etc.). Example: 1
     * @bodyParam email_body string required The HTML or plain body content of the email. Example: "<p>Hello User</p>"
     * @bodyParam whats_app_message string required WhatsApp message content. Example: "Hi {{name}}, welcome aboard!"
     * @bodyParam bell_notification_message string required Message to be shown in bell/alert notifications. Example: "New message received"
     * @bodyParam sms_message string required SMS content. Example: "Hello from XYZ company"
     * @bodyParam app_message string required App notification message. Example: "You have a new task assigned"
     * @bodyParam variables array Optional. Notification variables to bind in the message content. Example: ["name", "email"]
     *
     * @bodyParam notification_category_id integer Optional. Existing notification category ID.
     * @bodyParam category string required if notification_category_id is not provided. New category name to be created. Example: "User Engagement"
     *
     * @bodyParam notification_type_id integer Optional. Existing notification type ID.
     * @bodyParam type_title string required if notification_type_id is not provided. New type title. Example: "User Registration"
     * @bodyParam description string required if notification_type_id is not provided. Description of the notification type. Example: "Notification sent after user signs up."
     */
    public function createNotification(Request $request)
    {
        $rules = [
            'id' => 'nullable',
            'title' => 'required|string',
            'email_subject' => 'required|string',
            'is_enable' => 'required|boolean',
            'hidden_pre_header' => 'required|string',
            'priority' => 'required',
            'email_body' => 'required|string',
            'whats_app_message' => 'required|string',
            'bell_notification_message' => 'required|string',
            'sms_message' => 'nullable|string',
            'app_message' => 'nullable|string',
            'variables' => 'nullable|array',
        ];

        # Conditional rule for notification category
        if ($request->notification_category_id) {
            $rules['notification_category_id'] = 'required|exists:notification_category,id';
        } else {
            $rules['category'] = 'required|string';
        }

        # Conditional rule for notification type
        if ($request->notification_type_id) {
            $rules['notification_type_id'] = 'required|exists:notification_types,id';
        } else {
            $rules['type_title'] = 'required|string';
            $rules['description'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->actionFailure($validator->errors());
        }

        DB::beginTransaction();
        try {
            /**  Handle Notification Category  */
            $notification_category_id = $request->notification_category_id;

            if (!$notification_category_id && $request->category) {
                if (NotificationCategory::where('category', $request->category)->exists()) {
                    return $this->actionFailure("Category already exists!");
                }

                $category = NotificationCategory::updateOrCreate(['category' => $request->category]);
                if (!$category) {
                    return $this->actionFailure("Category could not be created.");
                }

                $notification_category_id = $category->id;
            }

            /** Handle Notification Type */
            $notification_type_id = $request->notification_type_id;

            if (!$notification_type_id && $request->type_title) {
                if (NotificationType::where('title', $request->type_title)->exists()) {
                    return $this->actionFailure("Type title already exists!");
                }

                $type_title = $request->type_title;
                $type_key = Str::slug($type_title);
                $originalSlug = $type_key;
                $counter = 1;

                # Ensure uniqueness of slug
                while (NotificationType::where('type_key', $type_key)->exists()) {
                    $type_key = $originalSlug . '-' . $counter++;
                }

                $type = NotificationType::updateOrCreate([
                    'category_id' => $notification_category_id,
                    'title' => $type_title,
                    'type_key' => $type_key,
                    'description' => $request->description ?? '',
                ]);

                if (!$type) {
                    return $this->actionFailure("Notification type could not be created.");
                }

                $notification_type_id = $type->id;
            }

            /** Create/Update Notification Template Section */
            $template = NotificationTemplateSection::updateOrCreate(
                ['notification_type_id' => $notification_type_id],
                [
                    'title' => $request->title,
                    'email_body' => $request->email_body,
                    'email_subject' => $request->email_subject,
                    'whats_app_message' => $request->whats_app_message,
                    'sms_message' => $request->sms_message,
                    'bell_notification_message' => $request->bell_notification_message,
                    'app_message' => $request->app_message,
                    'priority' => $request->priority,
                    'hidden_pre_header' => $request->hidden_pre_header,
                    'is_enable' => $request->is_enable,
                ]
            );

            /** Attach Notification Variables */
            $variables = $this->getVariables($notification_category_id);
            foreach ($variables as $info) {
                NotificationVariable::updateOrCreate([
                    'notification_type_id' => $notification_type_id,
                    'variables' => $info->variables,
                ]);
            }
            DB::commit();
            return $this->actionSuccess('Notification created successfully.', $template);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->actionFailure($e->getMessage());
        }
    }

    public function deleteNotificationCategory(Request $request)
    {
        $validator = Validator::make(['notification_category_id' => $request->notification_category_id], [
            'notification_category_id' => 'required|exists:notification_category,id',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }
        DB::beginTransaction();
        try {
            $notification_category = NotificationCategory::findOrFail($request->notification_category_id);
            $notification_category->delete();
            DB::commit();
            return $this->actionSuccess('Notification Category deleted successfully.', []);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function deleteNotificationType(Request $request)
    {
        $validator = Validator::make(['notification_type_id' => $request->notification_type_id], [
            'notification_type_id' => 'required|exists:notification_types,id',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }
        DB::beginTransaction();
        try {
            $notification_type = NotificationType::findOrFail($request->notification_type_id);
            $notification_type->delete();
            DB::commit();
            return $this->actionSuccess('Notification Type deleted successfully.', []);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }
}
