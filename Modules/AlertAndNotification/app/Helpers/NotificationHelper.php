<?php

namespace Modules\AlertAndNotification\Helpers;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use App\Models\ExportLog;
use App\Models\Setting;
use Modules\AlertAndNotification\Models\Rule;
use Illuminate\Support\Facades\Auth;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\AlertAndNotification\Models\NotificationType;
use Modules\AlertAndNotification\Services\TwilioService;
use Modules\AlertAndNotification\Services\WhatsAppService;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Services\LeadService;
use Illuminate\Database\Eloquent\Builder;
use Modules\Clients\Constants\ClientConst;
use Modules\Clients\Services\ClientService;
use Modules\Quotations\Constants\QuotationConst;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Modules\Clients\Models\Client;
use Modules\Invoices\Constants\InvoiceConst;
use Modules\Invoices\Models\Invoice;
use Modules\Leads\Models\Lead;
use Modules\Product\Models\Product;
use Modules\Quotations\Models\Quotation;
use Modules\SiteVisit\Constants\SiteVisitConst;
use Modules\SiteVisit\Models\SiteVisit;

class NotificationHelper
{
    protected array $data = [];
    protected ?string $rule_slug = null;
    protected ?string $rule_id = null;
    protected ?string $user_id = null;

    /**
     * Handles rule execution based on provided parameters
     *
     * @param string|null $ruleSlug Rule slug identifier
     * @param array $data Additional data for rule execution
     * @param string|null $ruleId Specific rule ID
     * @return void
     */
    public function handle(?string $ruleSlug = null, array $data = [], ?string $ruleId = null, ?string $userId = null): void
    {
        $this->data = $data;
        $this->rule_slug = $ruleSlug;
        $this->rule_id = $ruleId;
        $this->user_id = $userId ?? loginUserId() ?? $this->determineUserId();

        i("Rule Trigger: Starting handle() with rule_slug: {$ruleSlug}, rule_id: {$ruleId}, data keys: [" . implode(', ', array_keys($data)) . "]");

        $rule = $this->getActiveRule();
        if (!$rule) {
            i("Rule Trigger: No active rule found for slug: {$ruleSlug} or ID: {$ruleId}. Exiting.");
            return;
        }

        i("Rule Trigger: Fetched active rule — ID: {$rule->id}, Slug: {$rule->slug}, Condition Type: {$rule->condition_type}");

        $actions = json_decode($rule->actions ?? '[]', true) ?: [];

        if (empty($actions)) {
            i("Rule Trigger: No actions defined for Rule ID: {$rule->id}. Nothing to execute.");
        }

        foreach ($actions as $index => $action) {
            if (!is_array($action)) {
                i("Rule Trigger: Skipping invalid action at index {$index} — not an array.");
                continue;
            }

            $actionType = $action['action_type'] ?? 'undefined';
            i("Rule Trigger: Executing action index {$index} with type: {$actionType}");

            $this->executeAction($action, $rule);
        }

        i("Rule Trigger: Completed handle() for Rule ID: {$rule->id}");
    }


    /**
     * Determines the user ID for the current context
     *
     * @return string|null
     */
    private function determineUserId(): ?string
    {
        if (Auth::check()) {
            $uuid = Auth::user()->uuid;
            i("Rule Trigger: User is authenticated. Using Auth user UUID: {$uuid}");
            return $uuid;
        }

        $fromData = $this->data['created_by_uuid'] ?? null;
        if ($fromData) {
            i("Rule Trigger: User not authenticated. Using 'created_by_uuid' from data: {$fromData}");
            return $fromData;
        }

        $admin = adminUserId()[0] ?? null;
        i("Rule Trigger: Using fallback admin user UUID: {$admin}");
        return $admin;
    }

    /**
     * Retrieves active rule based on provided identifiers
     *
     * @return object|null
     */
    private function getActiveRule(): ?object
    {
        $query = Rule::whereNot('status', 'in-active');

        if ($this->rule_id) {
            i("Rule Trigger: Fetching rule using ID: {$this->rule_id}");
            $rule = $query->where('id', $this->rule_id)->first();
        } else {
            i("Rule Trigger: Fetching rule using slug: {$this->rule_slug}");
            $rule = $query->where('rule_slug', $this->rule_slug)->first();
        }

        if ($rule) {
            i("Rule Trigger: Found active rule — ID: {$rule->id}, Slug: {$rule->slug}, Status: {$rule->status}");
        } else {
            i("Rule Trigger: No active rule found for given identifier.");
        }

        return $rule;
    }

    /**
     * Executes specified action for the given rule
     *
     * @param array $action Action configuration
     * @param object $rule Rule object
     * @return void
     */
    protected function executeAction(array $action, object $rule): void
    {
        if (empty($action['action_type'])) {
            i("Rule Trigger: Skipping action — missing 'action_type'.");
            return;
        }

        $actionType = $action['action_type'];
        $ruleId = $rule->id ?? 'unknown';

        i("Rule Trigger: Executing action of type '{$actionType}' for Rule ID: {$ruleId}");
        match ($actionType) {
            CommonConst::ACTION_SEND_NOTIFICATION => $this->sendNotification($action, $rule),
            CommonConst::ACTION_CHANGE_STATUS     => $this->actionChangeStatus($action),
            CommonConst::ACTION_APPEND_NOTE       => $this->actionAppendNote($action),
            LeadConst::CONVERT_TO_CLIENT          => $this->convertToClient($action),
            ClientConst::CONVERT_TO_LEAD          => $this->convertToLead($action),
            QuotationConst::TRIGGER_SEND_QUOTATION => $this->sendPdfEmailOrWhatsApp($action, $rule),
            InvoiceConst::TRIGGER_SEND_INVOICE    => $this->sendPdfEmailOrWhatsApp($action, $rule),
            SiteVisitConst::TRIGGER_GENERATE_CHECKLIST_CHALLAN => $this->sendPdfEmailOrWhatsApp($action, $rule),
            default => i("Rule Trigger: Unknown action type '{$actionType}' — no execution performed."),
        };
    }

    /**
     * Sends notifications based on action and rule parameters
     *
     * @param array $action Notification action configuration
     * @param object $rule Rule object containing notification rules
     * @throws InvalidArgumentException If required parameters are missing or invalid
     */
    protected function sendNotification(array $action, object $rule): void
    {
        $ruleId = $rule->id ?? 'unknown';
        $emailType = $this->getNotificationType($action, $rule);
        $methods = $action['notification_methods'] ?? [];

        i("Rule Trigger: Sending notification for Rule ID: {$ruleId} using methods: [" . implode(', ', $methods) . "]");

        foreach ($methods as $method) {
            i("Rule Trigger: Processing method '{$method}' for notification (Type: {$emailType})");

            match ($method) {
                'Bell Notification' => $this->sendBellNotification($action, $emailType),
                'Email'             => $this->sendEmailNotification($action, $emailType),
                'Sms'               => $this->sendSmsNotification($action, $emailType),
                'WhatsApp'          => $this->sendWhatsappNotification($action, $emailType),
                'App'               => $this->sendAppNotification($action, $emailType),
                default             => i("Rule Trigger: Unsupported notification method '{$method}' — skipped."),
            };
        }
    }

    protected function sendPdfEmailOrWhatsApp(array $action, object $rule): void
    {
        $ruleId = $rule->id ?? 'unknown';
        $emailType = $this->getNotificationType($action, $rule);
        $methods = $action['notification_methods'] ?? [];

        i("Rule Trigger: Sending PDF via Email/WhatsApp for Rule ID: {$ruleId} using methods: [" . implode(', ', $methods) . "]");

        foreach ($methods as $method) {
            i("Rule Trigger: Processing method '{$method}' for PDF send (Type: {$emailType})");

            match ($method) {
                'Email'    => $this->sendEmailNotification($action, $emailType),
                'WhatsApp' => $this->sendWhatsappNotification($action, $emailType),
                default    => i("Rule Trigger: Unsupported method '{$method}' for PDF — skipped."),
            };
        }
    }

    /**
     * Retrieves notification type based on action and rule
     *
     * @param array $action Notification action configuration
     * @param object $rule Rule object
     * @return object|null Notification type object
     */
    private function getNotificationType(array $action, object $rule): ?object
    {
        if (!empty($action['template_id'])) {
            $notificationTypeId = NotificationTemplateSection::where('id', $action['template_id'])
                ->pluck('notification_type_id')
                ->first();

            $type = NotificationType::find($notificationTypeId);

            $typeKey = $type ? $type->type_key : 'N/A';

            i("Rule Trigger: Fetched NotificationType by template_id: {$action['template_id']} → Type ID: {$notificationTypeId}, Type Key: {$typeKey}");

            return $type;
        }

        $type = NotificationType::where('type_key', $rule->rule_slug)->first();

        $typeId = $type ? $type->id : 'N/A';
        $typeKey = $type ? $type->type_key : 'N/A';

        i("Rule Trigger: Fetched NotificationType by rule_slug: {$rule->rule_slug} → Type ID: {$typeId}, Type Key: {$typeKey}");

        return $type;
    }

    /**
     * Gets recipient list based on action configuration
     *
     * @param array $action Notification action configuration
     * @return array List of recipients
     */
    private function getRecipients(array $action): array
    {
        $sendEmailList = [];
        $recipients = $action['recipients'] ?? [];

        i("Rule Trigger: Resolving recipients: [" . implode(', ', $recipients) . "]");

        foreach ($recipients as $recipient) {
            $resolved = match ($recipient) {
                'Admins' => adminAndSuperAdminUserList(),
                'Created By' => emailUserInfo($this->data['created_by_uuid'] ?? null),
                'Last Updated By' => emailUserInfo($this->data['last_updated_by_uuid'] ?? null),
                'Assigned User' => emailUserInfo($this->data['assigned_user_uuid'] ?? null),
                'Lead User' => emailUserInfo($this->data['lead_uuid'] ?? null, 'Lead'),
                'Client User' => emailUserInfo($this->data['client_uuid'] ?? null, 'Client'),
                default => [],
            };

            $resolvedCount = is_array($resolved) ? count($resolved) : 0;
            i("Rule Trigger: Resolved recipient '{$recipient}' → {$resolvedCount} emails");

            $sendEmailList = array_merge($sendEmailList, $resolved);
        }

        i("Rule Trigger: Final recipient list count: " . count($sendEmailList));

        return $sendEmailList;
    }

    /**
     * Generate and save a PDF file based on the action type (Quotation or Invoice).
     * Logs the PDF creation and saves an entry in the `export_logs` table.
     *
     * @param array $action Action configuration containing the `action_type`
     * @return object An object with file details: path, filename, fileUrl, fileCaption, extension
     */
    protected function makeSendPdfFileInfo(array $action)
    {
        $fileUrl = $extension = $fileCaption = $filename = $table_name = $path = "";

        try {
            if ($action['action_type'] == QuotationConst::TRIGGER_SEND_QUOTATION) {
                $table_name = 'quotations';
                $directory = "email_attachments";

                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0755, true);
                    i("Rule Trigger : Created directory: $directory");
                }

                $quotation = Quotation::with(onlyQuotationUserRelation())->find($this->data['id']);
                $settings = Setting::pluck('value', 'key') ?? [];

                $pdf = Pdf::loadView('pdf.quotationPdf', ['quotation' => $quotation, 'settings' => $settings]);
                $filename = 'quotation_' . formattedDateTime() . '.pdf';
                $path = $directory . '/' . $filename;

                Storage::disk('public')->put($path, $pdf->output());
                i("Rule Trigger : Quotation PDF created at: $path");

                $fileUrl = url('storage/' . $path);
                $fileCaption = $quotation && $quotation->quotation_number
                    ? 'Quotation #' . $quotation->quotation_number
                    : 'Quotation Attachment';
                $extension = 'Document';
            } elseif ($action['action_type'] == InvoiceConst::TRIGGER_SEND_INVOICE) {
                $directory = 'invoices';
                $table_name = 'invoices';

                Storage::disk('public')->makeDirectory($directory, 0755, true);
                i("Rule Trigger : Created directory: $directory");

                $invoice = Invoice::with(onlyInvoiceUserRelation())->findOrFail($this->data['id']);
                $settings = Setting::pluck('value', 'key') ?? [];

                $pdf = Pdf::loadView('pdf.invoicePdf', ['invoice' => $invoice, 'settings' => $settings]);
                $filename = 'invoice_' . formattedDateTime() . '.pdf';
                $path = "$directory/$filename";

                Storage::disk('public')->put($path, $pdf->output());
                i("Rule Trigger : Invoice PDF created at: $path");

                $fileUrl = url("storage/$path");
                $fileCaption = 'Send Invoice Pdf';
                $extension = 'Document';
            } elseif ($action['action_type'] == SiteVisitConst::TRIGGER_GENERATE_CHECKLIST_CHALLAN) {
                $siteVisit = SiteVisit::findOrFail($this->data['id']);

                $directory = 'SiteVisit';
                $table_name = 'SiteVisit';
                Storage::disk('public')->makeDirectory($directory, 0755, true);
                $fileUrl = $fileCaption = $extension = $filename = $path = '';
                i("Rule Trigger : Checklist challan directory created: $directory");

                # Determine parent record
                $parentRecord = $siteVisit->lead_id ? Lead::find($siteVisit->lead_id) : ($siteVisit->client_id ? Client::find($siteVisit->client_id) : (object)[]);

                # Set fallback contact values
                $siteVisit->contact_person = $parentRecord->contact_person ?? '-';
                $siteVisit->phone = $parentRecord->phone ?? '-';
                $siteVisit->address = $parentRecord->address ?? '-';

                # Fetch products and their checklists
                $products = Product::whereIn('id', $siteVisit->products)->get();
                $visitType = $siteVisit->visit_type;

                $company = Setting::where('key', 'company_name')->value('value') ?? '-';

                $pdf = Pdf::loadView('pdf.challan', compact('products', 'siteVisit', 'company'));
                $filename = 'challan_' . formattedDateTime() . '.pdf';
                $path = "$directory/$filename";
                Storage::disk('public')->put($path, $pdf->output());

                $fileUrl = url("storage/$path");
                $fileCaption = 'Send Challan List';
                $extension = 'Document';
            }

            ExportLog::create([
                'name'       => $filename,
                'table_name' => $table_name,
                'extension'  => pathinfo($filename, PATHINFO_EXTENSION),
                'file_path'  => $fileUrl,
                'status'     => CommonConst::SUCCESS,
                'created_by' => $this->user_id,
            ]);

            i("Rule Trigger : ExportLog saved for file: $filename");

            return (object)[
                "path" => $path,
                "filename" => $filename,
                "fileUrl" => $fileUrl,
                "fileCaption" => $fileCaption,
                "extension" => $extension,
            ];
        } catch (\Exception $e) {
            er("Rule Trigger : Error generating PDF: " . $e->getMessage(), ['action' => $action]);
            throw $e;
        }
    }

    /**
     * Sends bell notification to recipients
     *
     * @param array $action Notification action configuration
     * @param object|null $emailType Notification type
     */
    protected function sendBellNotification(array $action, object $emailType): void
    {
        $priority = $action['priority'] ?? 'Medium';
        foreach ($this->getRecipients($action) as $key => $user) {
            $receiver_contact = $user['name'] ?? $user['user_name'];
            $receiver_id = $user['uuid'];

            $this->data['request_device_info'] = addMessageDeviceInfo(request());
            $content = makeMessageContent($this->data, $emailType->id, CommonConst::BELL_NOTIFICATION);

            i("Rule Trigger : Sending Bell Notification to [{$receiver_contact}] (ID: {$receiver_id}) with Priority: {$priority}");

            if ($content->status) {
                $logData = [
                    'receiver_contact' => $receiver_contact,
                    'subject' => $content->subject,
                    'content' => $content->bell_notification_message,
                    'priority' => $priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $emailType->id,
                    'receiver_id' => $receiver_id,
                    'section_type' => CommonConst::BELL_NOTIFICATION,
                    "is_notification" => false,
                    'email_body' => json_encode($this->data),
                    'sender_id' => $this->user_id,
                    'module_id' => $user['uuid'] == $this->data['lead_uuid'] ? $this->data['lead_uuid'] : (isset($this->data['client_uuid']) && $user['uuid'] == $this->data['client_uuid'] ? $this->data['client_uuid'] : ($this->data['id'] ?? null)),
                ];

                $emailLog = NotificationLog::create($logData);

                try {
                    $message = str_replace(['<br>', '<br/>', '<br />'], "\n", $content->bell_notification_message);
                    event(new NotificationMessage($message, $receiver_id, CommonConst::BELL_NOTIFICATION));

                    $emailLog->status = CommonConst::SUCCESS;
                    $emailLog->save();
                    i("Rule Trigger : Bell Notification sent successfully to {$receiver_contact}");
                } catch (\Exception $e) {
                    createExceptionError($e, "Notification Helper", __FUNCTION__);
                    $emailLog->status = CommonConst::FAILED;
                    $emailLog->message = $e->getMessage();
                    $emailLog->save();
                    er("Rule Trigger : Failed to send Bell Notification to {$receiver_contact}: " . $e->getMessage());
                }
            } else {
                i("Rule Trigger : Bell Notification content generation failed for {$receiver_contact}");
            }
        }
    }

    /**
     * Sends email notification to recipients
     *
     * @param array $action Notification action configuration
     * @param object|null $emailType Notification type
     */
    protected function sendEmailNotification(array $action, object $emailType): void
    {
        $not_send_mail = false;
        $notSendMailList = [];

        if ($emailType) {
            $sendPdfTriggers = [
                QuotationConst::TRIGGER_SEND_QUOTATION,
                InvoiceConst::TRIGGER_SEND_INVOICE,
                SiteVisitConst::TRIGGER_GENERATE_CHECKLIST_CHALLAN
            ];

            $info = in_array($action['action_type'], $sendPdfTriggers) ? $this->makeSendPdfFileInfo($action) : null;

            foreach ($this->getRecipients($action) as $key => $user) {
                $receiver_id = $user['uuid'];
                $receiver_contact = $user['email'];

                i("Rule Trigger : Sending Email Notification to {$receiver_contact} (ID: {$receiver_id})");

                $additional_info = [
                    'receiver_id' => $receiver_id,
                    'receiver_contact' => $receiver_contact,
                    'notification_type_id' => $emailType->id,
                    'sender_id' => $this->user_id,
                    'attachment_path' => $info?->fileUrl ? 'public/' . $info->path : null,
                    'attachment_original_name' => $info?->filename ?? null,
                    "is_notification" => false,
                    'module_id' => $user['uuid'] == $this->data['lead_uuid'] ? $this->data['lead_uuid'] : (isset($this->data['client_uuid']) && $user['uuid'] == $this->data['client_uuid'] ? $this->data['client_uuid'] : ($this->data['id'] ?? null)),
                ];

                if ($info) {
                    $additional_info += [
                        "path" => $info->path,
                        "filename" => $info->filename,
                        "fileUrl" => $info->fileUrl,
                        "fileCaption" => $info->fileCaption,
                        "extension" => $info->extension,
                    ];
                }

                $info = commonSendEmailFun($this->data, $additional_info);

                if ($info->status === false) {
                    $not_send_mail = true;
                    $notSendMailList[] = $info->message;
                    er("Rule Trigger : Email sending failed for {$receiver_contact}: " . $info->message);
                } else {
                    i("Rule Trigger : Email sent successfully to {$receiver_contact}");
                }
            }
        }
    }

    /**
     * Sends SMS notification to recipients
     *
     * @param array $action Notification action configuration
     * @param object $rule Rule object
     * @param object|null $emailType Notification type
     */
    protected function sendSmsNotification(array $action, object $emailType): void
    {
        foreach ($this->getRecipients($action) as $key => $user) {
            $receiver_contact = $user['country_code'] . $user['phone'];
            $this->data['request_device_info'] = addMessageDeviceInfo(request());
            $content = makeMessageContent($this->data, $emailType->id, CommonConst::SMS);

            if ($content->status) {
                $receiver_id = $user['uuid'];

                $logData = [
                    'receiver_contact' => $receiver_contact,
                    'subject' => $content->subject,
                    'content' => $content->sms_message,
                    'priority' => $content->priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $emailType->id,
                    'receiver_id' => $receiver_id,
                    'section_type' => CommonConst::SMS,
                    "is_notification" => false,
                    'email_body' => json_encode($this->data),
                    'sender_id' => $this->user_id,
                    'module_id' => $user['uuid'] == $this->data['lead_uuid'] ? $this->data['lead_uuid'] : (isset($this->data['client_uuid']) && $user['uuid'] == $this->data['client_uuid'] ? $this->data['client_uuid'] : ($this->data['id'] ?? null)),
                ];

                $emailLog = NotificationLog::create($logData);

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
                } catch (\Exception $e) {
                    $emailLog->status = CommonConst::FAILED;
                    $emailLog->message = $e->getMessage();
                    $emailLog->save();
                }
                try {
                    event(new NotificationMessage($emailLog->subject, $receiver_id, CommonConst::EMAIL));
                } catch (\Exception $e) {
                    createExceptionError($e, "Notification Helper", __FUNCTION__);
                }
            }
        }
    }

    /**
     * Sends WhatsApp notification to recipients
     *
     * @param array $action Notification action configuration
     * @param object $rule Rule object
     * @param object|null $emailType Notification type
     */
    protected function sendWhatsappNotification(array $action, object $emailType): void
    {
        $this->data['request_device_info'] = addMessageDeviceInfo(request());
        $priority = $action['priority'] ?? 'Medium';

        $sendPdfTriggers = [
            QuotationConst::TRIGGER_SEND_QUOTATION,
            InvoiceConst::TRIGGER_SEND_INVOICE,
            SiteVisitConst::TRIGGER_GENERATE_CHECKLIST_CHALLAN
        ];

        $info = in_array($action['action_type'], $sendPdfTriggers)
            ? $this->makeSendPdfFileInfo($action)
            : null;

        foreach ($this->getRecipients($action) as $key => $user) {
            $receiver_id = $user['uuid'];
            $receiver_contact = $user['phone'];
            $userName = trim($user['name']);

            i("Rule Trigger : Sending WhatsApp Notification to {$receiver_contact} (User: {$userName}, ID: {$receiver_id})");

            $content = makeMessageContent($this->data, $emailType->id, CommonConst::WHATSAPP);

            if ($content->status) {
                $fileUrl = $fileCaption = $extension = "";
                $additional_info = [
                    'attachment_path' => $info?->path ? asset('storage/' . $info->path) : null,
                    "is_notification" => false,
                    'module_id' => $user['uuid'] == $this->data['lead_uuid'] ? $this->data['lead_uuid'] : (isset($this->data['client_uuid']) && $user['uuid'] == $this->data['client_uuid'] ? $this->data['client_uuid'] : ($this->data['id'] ?? null)),
                ];

                if ($info) {
                    $additional_info += [
                        "path" => $info->path,
                        "filename" => $info->filename,
                        "fileUrl" => $info->fileUrl,
                        "fileCaption" => $info->fileCaption,
                        "extension" => $info->extension,
                    ];

                    $fileUrl = asset('storage/' . $info->path);
                    $fileCaption = $info->fileCaption;
                    $extension = "Document";
                }

                $logData = [
                    'receiver_contact' => $receiver_contact,
                    'subject' => $content->subject,
                    'content' => $content->whats_app_message,
                    'priority' => $priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $emailType->id,
                    'receiver_id' => $receiver_id,
                    'section_type' => CommonConst::WHATSAPP,
                    "is_notification" => false,
                    'email_body' => json_encode($this->data),
                    'additional_info' => json_encode($additional_info),
                    'sender_id' => $this->user_id,
                    'module_id' => $additional_info['module_id'],
                ];

                $emailLog = NotificationLog::create($logData);

                $message = str_replace(['<br>', '<br/>', '<br />'], "\n", $content->whats_app_message);
                $response = (new WhatsAppService())->sendMediaMessage($userName, $receiver_contact, $message, $fileUrl, $fileCaption, $extension);

                if ($response->status) {
                    $emailLog->status = CommonConst::SUCCESS;
                    $emailLog->save();
                    i("Rule Trigger : WhatsApp message sent to {$receiver_contact}");
                } else {
                    $emailLog->status = CommonConst::FAILED;
                    $emailLog->message = $response->message;
                    $emailLog->save();
                    er("Rule Trigger : WhatsApp sending failed for {$receiver_contact}: " . $response->message);
                }

                try {
                    event(new NotificationMessage($emailLog->subject, $receiver_id, CommonConst::WHATSAPP));
                } catch (\Exception $e) {
                    createExceptionError($e, "Notification Helper", __FUNCTION__);
                }
            } else {
                i("Rule Trigger : WhatsApp message content not generated for {$receiver_contact}");
            }
        }
    }

    /**
     * Sends app notification to recipients
     *
     * @param array $action Notification action configuration
     * @param object $rule Rule object
     * @param object|null $emailType Notification type
     */
    protected function sendAppNotification(array $action, object $emailType): void
    {
        $priority = $action['priority'] ?? 'Medium';
        foreach ($this->getRecipients($action) as $key => $user) {
            $receiver_contact = $user['email'] ?? $user['phone'];

            $this->data['request_device_info'] = addMessageDeviceInfo(request());
            $content = makeMessageContent($this->data, $emailType->id, CommonConst::APP);

            if ($content->status) {
                $receiver_id = $user['uuid'];

                $logData = [
                    'receiver_contact' => $receiver_contact,
                    'subject' => $content->subject,
                    'content' => $content->app_message,
                    'priority' =>  $priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $emailType->id,
                    'receiver_id' => $receiver_id,
                    'section_type' => CommonConst::APP,
                    "is_notification" => false,
                    'email_body' => json_encode($this->data),
                    'sender_id' => $this->user_id,
                    'module_id' => $user['uuid'] == $this->data['lead_uuid'] ? $this->data['lead_uuid'] : (isset($this->data['client_uuid']) && $user['uuid'] == $this->data['client_uuid'] ? $this->data['client_uuid'] : ($this->data['id'] ?? null)),
                ];

                $emailLog = NotificationLog::create($logData);

                # 1. Generate PDF and save to public storage
                // $pdf = PDF::loadView('pdf.test_pdf', ['data' => []]);
                // $filename = 'attachment_' . time() . '.pdf';
                // $path = 'email_attachments/' . $filename;
                // Storage::disk('public')->put($path, $pdf->output());

                # 2. Get public URL for media
                $fileUrl = null; # asset('storage/' . $path);
                $fileCaption = null;
                # Send message via
                $appMessage = str_replace(['<br>', '<br/>', '<br />'], "\n", $content->app_message);

                $emailLog->status = CommonConst::SUCCESS;
                $emailLog->message = $appMessage;
                $emailLog->save();

                try {
                    event(new NotificationMessage($emailLog->subject, $receiver_id, CommonConst::APP));
                } catch (\Exception $e) {
                    createExceptionError($e, "Notification Helper", __FUNCTION__);
                }
            }
        }
    }

    # TODO : Other Action Function
    /**
     * Retrieves model query for specified module.
     *
     * @param string $module Module identifier
     * @return Builder|null Eloquent query builder instance or null if module not mapped
     */
    public function getModelQuery(string $module): ?Builder
    {
        $modelMap = [
            CommonConst::MODULE_LEAD       => \Modules\Leads\Models\Lead::query(),
            CommonConst::MODULE_CLIENT     => \Modules\Clients\Models\Client::query(),
            CommonConst::MODULE_FOLLOW_UP  => \Modules\FollowUp\Models\FollowUp::query(),
            CommonConst::MODULE_SITE_VISIT => \Modules\SiteVisit\Models\SiteVisit::query(),
            CommonConst::MODULE_QUOTATION  => \Modules\Quotations\Models\Quotation::query(),
            CommonConst::MODULE_INVOICE    => \Modules\Invoices\Models\Invoice::query(),
        ];

        if (!isset($modelMap[$module])) {
            logger()->warning("Rule Trigger: No model query found for module: {$module}");
        }

        return $modelMap[$module] ?? null;
    }

    /**
     * Retrieves model instance for specified module and ID.
     *
     * @param string $module Module identifier
     * @param mixed $id Model ID
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getModelInstance(string $module, $id)
    {
        $query = $this->getModelQuery($module);

        if (!$query) {
            logger()->error("Model query not found for module: $module");
            return null;
        }

        return $query->where('id', $id)->first();
    }

    /**
     * Changes status of a model based on action configuration
     *
     * @param array $action Action configuration containing status_id
     * @return void
     */
    protected function actionChangeStatus(array $action): void
    {
        $module = $this->data['rule_module'] ?? '';
        $id = $this->data['id'] ?? null;
        $model = $this->getModelInstance($module, $id);

        if (!$model) return;

        $statusSlug = AdminControlConfig::where('status_for', $module)
            ->where('id', $action['status_id'])
            ->pluck('slug')
            ->first();

        if (!$statusSlug) {
            logger()->warning("Rule Trigger: Status slug not found for status_id: {$action['status_id']} in module: {$module}");
            return;
        }

        $model->status = $statusSlug;
        $model->save();
        i("Rule Trigger: Changed status for module: {$module}, ID: {$id} → Status: {$statusSlug}");
    }


    /**
     * Appends note to a model based on action configuration
     *
     * @param array $action Action configuration containing note
     * @return void
     */
    protected function actionAppendNote(array $action): void
    {
        $module = $this->data['rule_module'] ?? '';
        $id = $this->data['id'] ?? null;
        $model = $this->getModelInstance($module, $id);

        if (!$model) return;

        $model->note = $action['note'];
        $model->save();

        i("Rule Trigger: Appended note to module: {$module}, ID: {$id}");
    }

    /**
     * Converts a lead to a Lead using LeadService.
     *
     * @param array $action Action data (unused here)
     * @return void
     */
    protected function convertToClient(array $action): void
    {
        i("Rule Trigger: Converted Lead to Client ID: {$this->data['id']} Start ");
        $method = 'manual';
        $leadService = new LeadService();
        $leadService->convertToClient($this->data['id'], $method, $this->user_id);
        i("Rule Trigger: Converted Lead ID: {$this->data['id']} to Client manually.");
    }

    /**
     * Converts a lead to a client using ClientService.
     *
     * @param array $action Action data (unused here)
     * @return void
     */
    protected function convertToLead(array $action): void
    {
        i("Rule Trigger: Converted Client to Lead ID: {$this->data['id']} Start ");
        $method = 'manual';
        $clientService = new ClientService();
        $clientService->convertToLead($this->data['id'], $method, $this->user_id);
        i("Rule Trigger: Converted Client ID: {$this->data['id']} to Lead manually.");
    }

    /**
     * Retrieves relationships array for notification log queries
     *
     * @return array Array of relationships to eager load
     */
    public function onlyUseNotificationLogGet()
    {
        $with = [
            'sender:uuid,name,avatar',
            'receiver:uuid,name,avatar',
            'notification_type:id,title',
            'b_to_b_user:id,name,avatar',
            'receiver_b_to_b:id,name,avatar',
        ];

        if (\Nwidart\Modules\Facades\Module::has(CommonConst::MODULE_LEAD)) {
            $with[] = 'lead:id,name';
            $with[] = 'receiver_lead:id,name';
        }
        if (\Nwidart\Modules\Facades\Module::has(CommonConst::MODULE_CLIENT)) {
            $with[] = 'client:id,name,avatar';
            $with[] = 'receiver_client:id,name,avatar';
        }
        if (\Nwidart\Modules\Facades\Module::has(CommonConst::MODULE_SCHEDULING)) {
            $with[] = 'schedule';
        }
        if (\Nwidart\Modules\Facades\Module::has(CommonConst::MODULE_QUOTATION)) {
            $with[] = 'quotation';
        }
        if (\Nwidart\Modules\Facades\Module::has(CommonConst::MODULE_SITE_VISIT)) {
            $with[] = 'srm';
        }
        if (\Nwidart\Modules\Facades\Module::has(CommonConst::MODULE_CONTRACT)) {
            $with[] = 'contract';
        }
        if (\Nwidart\Modules\Facades\Module::has(CommonConst::MODULE_FOLLOW_UP)) {
            $with[] = 'follow_up';
        }

        return $with;
    }
}
