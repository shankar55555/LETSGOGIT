<?php

namespace App\Http\Controllers;

use App\Constants\CommonConst;
use Illuminate\Http\Request;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Jobs\EmailJob;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Models\NotificationType;
use Modules\Leads\Models\Lead;
use App\Mail\MailSend;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\Clients\Constants\ClientConst;
use Modules\Clients\Models\Client;
use Modules\Contracts\Constants\ContractConst;
use Modules\Contracts\Models\Contract;
use Modules\FollowUp\Constants\FollowUpConst;
use Modules\FollowUp\Models\FollowUp;
use Modules\Invoices\Constants\InvoiceConst;
use Modules\Invoices\Models\Invoice;
use Modules\Leads\Constants\LeadConst;
use Modules\Quotations\Constants\QuotationConst;
use Modules\Quotations\Models\Quotation;
use Modules\SiteVisit\Constants\SiteVisitConst;
use Modules\SiteVisit\Models\SiteVisit;
use Nwidart\Modules\Facades\Module;
use Illuminate\Validation\Rule;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\Clients\Jobs\ClientInActiveJob;
use Modules\FollowUp\Jobs\FollowUpDueJob;
use Modules\FollowUp\Jobs\FollowUpOverDueJob;
use Modules\Invoices\Jobs\InvoiceAfterDueJob;
use Modules\Invoices\Jobs\InvoiceBeforeDueJob;
use Modules\Leads\Jobs\LeadNoActionJob;
use Modules\Quotations\Jobs\QuotationExpiredJob;
use Modules\SiteVisit\Jobs\SiteVisitDueJob;

class TestController extends Controller
{
    public function test()
    {

        // $lead = Lead::first();
        # Lead created
        // NotificationJob::dispatch(LeadConst::RULE_LEAD_CREATED, leadRuleNotification($lead->id),null, loginUserId());
        // NotificationJob::dispatch(LeadConst::RULE_LEAD_CREATED, leadRuleNotification($lead->id),null, loginUserId());
        // LeadNoActionJob::dispatch()->onConnection('sync');
        // ClientInActiveJob::dispatch()->onConnection('sync');
        // QuotationExpiredJob::dispatch()->onConnection('sync');
        // InvoiceAfterDueJob::dispatch()->onConnection('sync');
        // InvoiceBeforeDueJob::dispatch()->onConnection('sync');
        // SiteVisitDueJob::dispatch()->onConnection('sync');
        // FollowUpDueJob::dispatch()->onConnection('sync');
        // FollowUpOverDueJob::dispatch()->onConnection('sync');
        return;
    }

    public function testNotification($module, $rule_slug)
    {
        $module_list = [
            CommonConst::MODULE_ALERT_AND_NOTIFICATION,
            CommonConst::MODULE_ATTENDANCE,
            CommonConst::MODULE_CLIENT,
            CommonConst::MODULE_USER,
            CommonConst::MODULE_CONTRACT,
            CommonConst::MODULE_DASHBOARD,
            CommonConst::MODULE_FOLLOW_UP,
            CommonConst::MODULE_INVOICE,
            CommonConst::MODULE_LEAD,
            CommonConst::MODULE_PAYMENT,
            CommonConst::MODULE_PRODUCT_SERVICE,
            CommonConst::MODULE_QUOTATION,
            CommonConst::MODULE_ROLE_PERMISSION,
            CommonConst::MODULE_SCHEDULING,
            CommonConst::MODULE_SITE_VISIT,
            CommonConst::MODULE_TARGETS
        ];

        $type_list = [];
        if (CommonConst::MODULE_ALERT_AND_NOTIFICATION) {
        } else if (CommonConst::MODULE_ATTENDANCE) {
        } else if (CommonConst::MODULE_CLIENT) {
            $type_list[] = ClientConst::CLIENT_EMAIL_TYPE_LIST;
        } else if (CommonConst::MODULE_USER) {
        } else if (CommonConst::MODULE_CONTRACT) {
            $type_list[] = ContractConst::CONTRACT_EMAIL_TYPE_LIST;
        } else if (CommonConst::MODULE_DASHBOARD) {
        } else if (CommonConst::MODULE_FOLLOW_UP) {
            $type_list[] = FollowUpConst::FOLLOW_UP_EMAIL_TYPE_LIST;
        } else if (CommonConst::MODULE_INVOICE) {
            $type_list[] = InvoiceConst::INVOICE_EMAIL_TYPE_LIST;
        } else if (CommonConst::MODULE_LEAD) {
            $type_list[] = LeadConst::LEAD_EMAIL_TYPE_LIST;
        } else if (CommonConst::MODULE_PAYMENT) {
        } else if (CommonConst::MODULE_PRODUCT_SERVICE) {
        } else if (CommonConst::MODULE_QUOTATION) {
            $type_list[] = QuotationConst::QUOTATION_EMAIL_TYPE_LIST;
        } else if (CommonConst::MODULE_ROLE_PERMISSION) {
        } else if (CommonConst::MODULE_SCHEDULING) {
        } else if (CommonConst::MODULE_SITE_VISIT) {
            $type_list[] = SiteVisitConst::SITE_VISIT_EMAIL_TYPE_LIST;
        } else if (CommonConst::MODULE_TARGETS) {
        }

        $validator = Validator::make(['module_name' => $module, 'rule_slug' => $rule_slug,], [
            'module_name'         => "required|in:" . implode(',', $module_list),
            'rule_slug'           => "required|in:" . implode(',', $type_list)
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }

        echo "this test function to get all module first item info get hit trigger";

        $data = [];
        $id = null;
        if (Module::has(CommonConst::MODULE_LEAD) && $module == CommonConst::MODULE_LEAD) {
            $id = Lead::pluck('id')->first();
            $data = $id ? leadRuleNotification($id) : [];
        } else if (Module::has(CommonConst::MODULE_CLIENT) && $module == CommonConst::MODULE_CLIENT) {
            $id = Client::pluck('id')->first();
            $data = $id ? clientRuleNotification($id) : [];
        } else if (Module::has(CommonConst::MODULE_QUOTATION) && $module == CommonConst::MODULE_QUOTATION) {
            $id = Quotation::pluck('id')->first();
            $data = $id ? quotationRuleNotification($id) : [];
        } else if (Module::has(CommonConst::MODULE_INVOICE) && $module == CommonConst::MODULE_INVOICE) {
            $id = Invoice::pluck('id')->first();
            $data = $id ? invoiceRuleNotification($id) : [];
        } else if (Module::has(CommonConst::MODULE_FOLLOW_UP) && $module == CommonConst::MODULE_FOLLOW_UP) {
            $id = FollowUp::pluck('id')->first();
            $data = $id ? followUpRuleNotification($id) : [];
        } else if (Module::has(CommonConst::MODULE_SITE_VISIT) && $module == CommonConst::MODULE_SITE_VISIT) {
            $id = SiteVisit::pluck('id')->first();
            $data = $id ? siteVisitRuleNotification($id) : [];
        } else if (Module::has(CommonConst::MODULE_CONTRACT) && $module == CommonConst::MODULE_CONTRACT) {
            $id = Contract::pluck('id')->first();
            $data = $id ? invoiceRuleNotification($id) : [];
        } else {
            echo "Rule Notification module not found";
            return;
        }

        if (!$id) {
            echo "Rule Notification module Id not Found!";
            return;
        }

        # You can add more pending fields similarly from other models if needed
        $this->callHelperFunction($rule_slug, $data);

        echo "Rule Notification send Successfully";
        return;
    }

    public function callHelperFunction($rule_slug, $data)
    {
        $notificationHelper = new NotificationHelper();
        $notificationHelper->handle($rule_slug, $data, null, loginUserId());
    }

    # Dispatch the job Lead No Action
    public function testDispatchJob($rule_slug)
    {
        $type_list = [
            LeadConst::RULE_NO_ACTION,
            ClientConst::RULE_CLIENT_INACTIVE,
            FollowUpConst::RULE_FOLLOW_UP_DUE,
            FollowUpConst::RULE_FOLLOW_UP_OVERDUE,
            QuotationConst::RULE_QUOTATION_EXPIRED,
            SiteVisitConst::RULE_SITE_VISIT_DUE,
            InvoiceConst::RULE_DAYS_BEFORE_DUE,
            InvoiceConst::RULE_AFTER_DUE_DATE,
        ];

        $validator = Validator::make(['rule_slug' => $rule_slug], [
            'rule_slug' => "required|in:" . implode(',', $type_list),
        ]);

        if ($validator->fails()) {
            return $this->validationFailed($validator->errors(), $type_list);
        }

        echo "this test function to get all module first item info get hit trigger";

        if ($rule_slug == LeadConst::RULE_NO_ACTION) {
            LeadNoActionJob::dispatch()->onConnection('sync');
        } else if ($rule_slug == ClientConst::RULE_CLIENT_INACTIVE) {
            ClientInActiveJob::dispatch()->onConnection('sync');
        } else if ($rule_slug == FollowUpConst::RULE_FOLLOW_UP_DUE) {
            FollowUpDueJob::dispatch()->onConnection('sync');
        } else if ($rule_slug == FollowUpConst::RULE_FOLLOW_UP_OVERDUE) {
            FollowUpOverDueJob::dispatch()->onConnection('sync');
        } else if ($rule_slug == QuotationConst::RULE_QUOTATION_EXPIRED) {
            QuotationExpiredJob::dispatch()->onConnection('sync');
        } else if ($rule_slug == SiteVisitConst::RULE_SITE_VISIT_DUE) {
            SiteVisitDueJob::dispatch()->onConnection('sync');
        }
        return $this->actionSuccess('Job dispatched successfully.');
    }

    # Test Mail end Job
    public function testMailSend(Request $request)
    {
        $notification_type = NotificationType::where('type_key', CommonConst::ACCOUNT_LOGIN)->select('id', 'title')->first();
        $request_device_info = addEmailDeviceInfo($request);
        $adminList = adminAndSuperAdminUserList();
        $email_content_info = [
            'name' => "Resham Singh",
            'request_device_info' => $request_device_info,
        ];

        $additional_info = [
            'notification_type_id' => $notification_type->id,
            'sender_id' => Auth::User()->uuid ?? adminUserId()[0],
            'attachment_path' => null,
            'attachment_original_name' => null,
            "is_notification" => false, # Gmail account Send Mail
            "module_id" => null
        ];
        $log_id = null;
        # $this->createAndSendNewEmail($adminList, $email_content_info, $additional_info);
        EmailJob::dispatch($log_id, $adminList, $email_content_info, $additional_info)->onConnection('sync');
        return;
    }

    public function createAndSendNewEmail(array $userList = [], array $emailContentInfo = [], array $additionalInfo = [])
    {
        foreach ($userList as $key => $user) {
            $additionalInfo['receiver_id'] = $user['uuid'];
            $additionalInfo['receiver_contact'] = $user['email'];

            $notificationTypeId = $additionalInfo['notification_type_id'];
            $receiverContact = $additionalInfo['receiver_contact'];
            $isNotification = $additionalInfo['is_notification'] ?? true;

            $content = makeMessageContent($emailContentInfo, $notificationTypeId, CommonConst::EMAIL);

            if ($content->status) {
                $additionalInfo['hidden_pre_header'] = $content->hidden_pre_header;
                $logData = [
                    'receiver_contact' => $receiverContact,
                    'subject' => $content->subject,
                    'content' => $content->content,
                    'priority' => $content->priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $notificationTypeId,
                    'receiver_id' => $additionalInfo['receiver_id'] ?? null,
                    'section_type' => CommonConst::EMAIL,
                    'is_notification' => $isNotification,
                    'email_body' => json_encode($emailContentInfo),
                    'additional_info' => json_encode($additionalInfo),
                    'sender_id' => $additionalInfo['sender_id'] ?? null,
                    'module_id' => $additionalInfo['module_id'] ?? null,
                ];

                $log = NotificationLog::create($logData);
                if ($log && !$isNotification) {
                    $this->sendEmail($receiverContact, $content, $additionalInfo, $log);
                } else {
                    $log->status = CommonConst::SUCCESS;
                    $log->save();
                }
            }
        }
    }

    protected function sendEmail(string $to, object $content, array $info, NotificationLog $log): void
    {
        try {
            $emailData = [
                'subject' => $content->subject,
                'attachment_path' => $info['attachment_path'] ?? null,
                'attachment_original_name' => $info['attachment_original_name'] ?? null,
                'hidden_pre_header' => $content->hidden_pre_header,
                'content' => $content->simple_content,
            ];

            Mail::to($to)->send(new MailSend($emailData));
            $log->status = CommonConst::SUCCESS;
        } catch (\Exception $e) {
            createExceptionError($e, 'EmailJob', __FUNCTION__);
            $log->status = CommonConst::FAILED;
            $log->message = $e->getMessage();
        }

        $log->save();
    }
}
