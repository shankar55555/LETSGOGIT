<?php

namespace Modules\AlertAndNotification\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Models\AdminControlConfig;
use Modules\AlertAndNotification\Http\Requests\RuleStoreRequest;
use Modules\AlertAndNotification\Http\Requests\RuleUpdateRequest;
use Modules\AlertAndNotification\Models\Rule;
use Modules\AlertAndNotification\Transformers\RuleResource;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Modules\AlertAndNotification\Services\RuleService;
use Modules\AlertAndNotification\Http\Controllers\EmailController;
use Modules\AlertAndNotification\Http\Controllers\WhatsAppController;
use Modules\AlertAndNotification\Http\Controllers\SmsController;
use Modules\AlertAndNotification\Http\Controllers\BellNotificationController;
use Modules\AlertAndNotification\Http\Controllers\AppController;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;

class RuleController extends Controller
{
    const CONTROLLER_NAME = "Rule Controller";
    protected $ruleService;

    public function __construct(RuleService $ruleService)
    {
        $this->ruleService = $ruleService;
    }

    public function allNotificationCount(Request $request)
    {
        $response = [
            'un_read_email_count' => (new EmailController)->emailNotificationCount($request),
            'un_read_whatsapp_count' => (new WhatsAppController)->whatsAppNotificationCount($request),
            'un_read_sms_count' => (new SmsController)->smsNotificationCount($request),
            'un_read_bell_count' => (new BellNotificationController)->bellNotificationCount($request),
            'un_read_app_count' => (new AppController)->appNotificationCount($request),
        ];

        return $this->actionSuccess("Unread all notification count retrieved successfully.", $response);
    }

    public function allNotificationLatestFiveList(Request $request)
    {
        $response = [
            'email_list' => (new EmailController)->emailLatestFiveNotificationList($request),
            'whatsapp_list' => (new WhatsAppController)->whatsAppLatestFiveNotificationList($request),
            'sms_list' => (new SmsController)->smsLatestFiveNotificationList($request),
            'bell_list' => (new BellNotificationController)->bellLatestFiveNotificationList($request),
            'app_list' => (new AppController)->appLatestFiveNotificationList($request),
        ];
        return $this->actionSuccess("Unread all notification Latest Five retrieved successfully.", $response);
    }

    public function index(Request $request)
    {
        $paginated = $this->ruleService->getPaginatedRule(
            $request->integer('per_page', 15),
            $request->input('search'),
            $request->input('rule'),
            $request->input('status'),
            $request->input('created_by'),
            $request->input('last_updated_by')
        );

        return $this->actionSuccess('Rule retrieved successfully', customizingResponseData($paginated));
    }

    public function store(RuleStoreRequest $request)
    {
        $rule = Rule::createWithAttributes([
            ...$request->validated(),
            'created_by' => Auth::user()->uuid
        ]);
        return $this->actionSuccess('Rule created successfully', new RuleResource($rule));
    }

    public function show(string $id): JsonResponse
    {
        $lead = $this->ruleService->getRuleById($id);
        return response()->json([
            'data' => new RuleResource($lead),
            'message' => 'Rule retrieved successfully'
        ]);
    }

    public function update(RuleUpdateRequest $request, string $id)
    {
        # Check if another record has the same 'rule' value
        if (Rule::where('id', '!=', $id)->where('rule', $request->rule)->exists()) {
            return $this->actionFailure('The rule name has already been taken.');
        }

        $lead = $this->ruleService->updateRule(
            $id,
            array_merge($request->validated(), ['last_updated_by' => Auth::user()->uuid])
        );

        return response()->json([
            'data' => new RuleResource($lead),
            'message' => 'Rule updated successfully'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->ruleService->deleteRule($id);
        return response()->json([
            'message' => 'Rule deleted successfully'
        ]);
    }


    public function ruleStatusUpdate(Request $request)
    {
        $rule = Rule::where('id', $request->rule_id)->first();
        if (!$rule) return $this->actionFailure('Rule Not Found!');
        $rule->status = $request->status;
        $rule->save();

        return $this->actionSuccess('Rule Status Update Successfully', $rule);
    }

    /**
     * public function to handle rule-based queries
     *
     * @param string $module
     * @param string $slug
     * @param array $condition (contains 'control', 'value', 'datatype')
     * @return \Illuminate\Support\Collection
     */
    public function executeRuleQuery($condition, $data)
    {
        $module = $condition['module'] ?? null;
        $slug = $condition['trigger_event'] ?? null;
        $control = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;
        $datatype = $condition['datatype'] ?? null;

        switch ($module) {
            case CommonConst::MODULE_LEAD:
                return $this->ruleService->handleLeadRules($slug, $control, $value, $datatype, $data);
            case CommonConst::MODULE_CLIENT:
                return $this->ruleService->handleClientRules($slug, $control, $value, $datatype, $data);
            case CommonConst::MODULE_QUOTATION:
                return $this->ruleService->handleQuotationRules($slug, $control, $value, $datatype, $data);
            case CommonConst::MODULE_CONTRACT:
                return $this->ruleService->handleContractRules($slug, $control, $value, $datatype, $data);
            case CommonConst::MODULE_INVOICE:
                return $this->ruleService->handleInvoiceRules($slug, $control, $value, $datatype, $data);
            case CommonConst::MODULE_SITE_VISIT:
                return $this->ruleService->handleSiteVisitRules($slug, $control, $value, $datatype, $data);
            case CommonConst::MODULE_FOLLOW_UP:
                return $this->ruleService->handleFollowupRules($slug, $control, $value, $datatype, $data);
            default:
                return false;
        }
    }

    public function getTriggerEvents(Request $request)
    {
        $ruleList = readConstFileList('RULE', [], false);

        foreach ($ruleList as &$rule) {
            foreach ($rule['trigger_event'] as &$event) {
                if ($event['slug'] === CommonConst::RULE_STATUS_TRIGGER) {
                    $statusList = AdminControlConfig::query()->where('status_for', $rule['module'])->select(['id', 'status_text'])->get()->toArray();

                    $event['status_list'] = $statusList;

                    foreach ($event['actionList'] as &$action) {

                        if ($action['slug'] === CommonConst::ACTION_CHANGE_STATUS) {
                            $action['status_list'] = $statusList;
                        }

                        if (isset($action['templates'])) {
                            $action['templates'] = NotificationTemplateSection::whereHas('notification_type', function ($que) use ($rule) {
                                $que->whereHas('notification_category', function ($qu) use ($rule) {
                                    $qu->where('category', $rule['module']);
                                });
                            })->select(['id', 'title'])->get()->toArray();
                        }
                    }
                } else {
                    foreach ($event['actionList'] as &$action) {
                        if (isset($action['status_list'])) {
                            $statusList = AdminControlConfig::query()->where('status_for', $rule['module'])->select(['id', 'status_text'])->get()->toArray();
                            $action['status_list'] = $statusList;
                        }

                        if (isset($action['templates'])) {
                            $action['templates'] = NotificationTemplateSection::query()->whereHas('notification_type', function ($que) use ($rule) {
                                $que->whereHas('notification_category', function ($qu) use ($rule) {
                                    $qu->where('category', $rule['module']);
                                });
                            })->select(['id', 'title'])->get()->toArray();
                        }
                    }
                }
            }
        }

        return $this->actionSuccess('Rule Status Updated Successfully', ['rule_list' => $ruleList]);
    }
}
