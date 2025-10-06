<?php

namespace Modules\SiteVisit\Services;

use App\Constants\CommonConst;
use App\Mail\MailSend;
use App\Models\AdminControlConfig;
use App\Models\ExportLog;
use App\Models\Setting;
use Modules\SiteVisit\Models\SiteVisit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Clients\Models\Client;
use Modules\Leads\Models\Lead;

use Modules\Product\Models\Product;
use Modules\SiteVisit\Models\SiteRiskManagement;
use Modules\SiteVisit\Models\SiteRiskMedia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Services\WhatsAppService;
use Modules\SiteVisit\Constants\SiteVisitConst;

class SiteVisitService
{
    public function getAllVisits(?string $status = null): Builder
    {
        $query = SiteVisit::with(['assignee', 'creator', 'updater']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function getVisitById(string $id): SiteVisit
    {
        $visit = SiteVisit::with(['assignee', 'creator', 'updater'])
            ->where('id', $id)
            ->first();

        if (!$visit) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("No query results for model [Modules\SiteVisit\Models\SiteVisit] with ID {$id}");
        }

        return $visit;
    }

    public function createVisit(array $data): SiteVisit
    {
        $data['created_by'] = Auth::user()->uuid;
        $siteVisit = SiteVisit::create($data);
        $this->existTriggerActionSendFile($siteVisit->id, $siteVisit->status);
        return $siteVisit;
    }

    public function updateVisit(string $id, array $data): SiteVisit
    {
        $data['last_updated_by'] = Auth::user()->uuid;
        $visit = $this->getVisitById($id);
        $oldStatus = $visit->status;
        $visit->update($data);
        $this->existTriggerActionSendFile($visit->id, $data['status'], $oldStatus);
        return $visit->fresh();
    }

    public function updateStatus(string $id, string $status): SiteVisit
    {
        $visit = $this->getVisitById($id);
        $oldStatus = $visit->status;
        $visit->update(['status' => $status]);
        $this->existTriggerActionSendFile($visit->id, $status, $oldStatus);
        return $visit->fresh();
    }

    public function deleteVisit(string $id): bool
    {
        $visit = $this->getVisitById($id);
        SiteRiskManagement::where('site_visit_id', $id)->delete();
        $medias = SiteRiskMedia::where('site_visit_id', $id)->get();
        foreach ($medias as $key => $media) {
            # Find the media record
            $media = SiteRiskMedia::findOrFail($id);
            # Delete the file from storage
            if (Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }
            # Delete the record from database
            $media->delete();
        }

        return $visit->delete();
    }

    public function existTriggerActionSendFile(string $siteVisitId, string $status, ?string $oldStatus = null)
    {
        if ($oldStatus != $status) {
            $RuleCheckHelper = new RuleCheckHelper();
            $RuleCheckHelper->onlyStatusChangeCheckRule(CommonConst::MODULE_SITE_VISIT, $status, [$siteVisitId], $oldStatus);
            // $statusInfo = AdminControlConfig::where('status_for', CommonConst::MODULE_SITE_VISIT)->where('slug', $status)->select('id', 'trigger_action', 'send_plat_forms')->first();
            // if ($statusInfo && in_array(SiteVisitConst::TRIGGER_GENERATE_CHECKLIST_CHALLAN, makeAnyIdArrayFormat($statusInfo->trigger_action))) {
            //     $platforms = makeAnyIdArrayFormat($statusInfo->send_plat_forms);
            //     return $this->statusTriggerActionSenFile($siteVisitId, $platforms);
            // }
        }
    }

    public function statusTriggerActionSenFile(string $siteVisitId, ?array $platForms = []): array
    {
        # Fetch site visit with its assignee
        $siteVisit = SiteVisit::with('assignee')->findOrFail($siteVisitId);
        $assignee = $siteVisit->assignee;

        # If no assignee found, skip sending
        if (!$assignee) {
            return [['status' => 409, 'message' => 'Assignee not found.']];
        }

        $results = [];

        foreach ($platForms as $platform) {
            $info = (object)[
                'module_name'        => CommonConst::MODULE_SITE_VISIT,
                'module_id'          => $siteVisitId,
                'receiver_id'        => $assignee->id,
                'name'               => $assignee->name ?? 'No Name',
                'email'              => $assignee->email ?? '',
                'receiver_column'    => "site_visit_id",
                'phone'              => $assignee->phone ?? '',
                'socialPlatform'     => $platform,
                'sendAttachmentType' => CommonConst::AUTO_SEND_FILE,
                'message'            => "Auto send file triggered by status update",
                'sender_id'          => Auth::user()->uuid ?? null,
            ];

            $results[] = $this->siteVisitSendMessage($info);
        }

        return $results;
    }

    public function siteVisitSendMessage($info)
    {
        $siteVisit = SiteVisit::findOrFail($info->module_id);

        $directory = 'SiteVisit';
        Storage::disk('public')->makeDirectory($directory, 0755, true);
        $fileUrl = $fileCaption = $extension = $filename = $path = '';
        # Attachment processing
        if ($info->sendAttachmentType == CommonConst::SELECT_FILE && $info->hasFile('file')) {
            $file = $info->file('file');
            $originalName = $file->getClientOriginalName();
            $filename = 'challan_' . formattedDateTime() . '_' . $originalName;
            $path = "$directory/$filename";
            Storage::disk('public')->putFileAs($directory, $file, $filename);

            $fileUrl = url("storage/$path");
            $fileCaption = $originalName;
            $mime = $file->getMimeType();
            $extension = Str::startsWith($mime, 'image/') ? 'Image' : 'Document';
        } elseif ($info->sendAttachmentType == CommonConst::AUTO_SEND_FILE) {
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

        # Log the exported file
        if ($info->sendAttachmentType != CommonConst::NO_ATTACHMENT && $fileUrl) {
            ExportLog::create([
                'name'       => $filename,
                'table_name' => 'site_visits',
                'extension'  => pathinfo($filename, PATHINFO_EXTENSION),
                'file_path'  => $fileUrl,
                'status'     => CommonConst::SUCCESS,
                'created_by' => $info->sender_id,
            ]);
        }

        # Build email body and additional info
        $receiverColumn = $info->receiver_column ?? 'receiver_id';

        $emailBody = [
            'module_name'        => $info->module_name,
            'module_id'          => $info->module_id,
            $receiverColumn      => $info->receiver_id,
            'name'               => $info->name,
            'email'              => $info->email,
            'phone'              => $info->phone,
            'socialPlatform'     => $info->socialPlatform,
            'sendAttachmentType' => $info->sendAttachmentType,
            'message'            => $info->message,
        ];

        $additionalInfo = $fileUrl ? [
            'fileUrl'     => $fileUrl,
            'extension'   => $extension,
            'fileCaption' => $fileCaption,
        ] : [];

        $receiverContact = $info->socialPlatform === CommonConst::WHATSAPP ? $info->phone : $info->email;

        # Notification log
        $log = NotificationLog::create([
            'receiver_contact'    => $receiverContact,
            'subject'             => "Site Visit Sent Info User " . $info->name,
            'content'             => $info->message,
            'priority'            => CommonConst::HIGH,
            'status'              => CommonConst::PENDING,
            'notification_type_id' => null,
            'receiver_id'         => null,
            'section_type'        => $info->socialPlatform,
            'is_notification'     => false,
            'email_body'          => json_encode($emailBody),
            'additional_info'     => json_encode($additionalInfo),
            'sender_id'           => $info->sender_id,
            'module_id'           => $info->module_id,
        ]);

        $message = '';

        # Sending message logic
        if ($info->socialPlatform === CommonConst::WHATSAPP) {
            $plainText = str_replace(['<br>', '<br/>', '<br />'], "\n", $info->message);
            $response = (new WhatsAppService())->sendMediaMessage(
                trim($info->name),
                $receiverContact,
                $plainText,
                $fileUrl,
                $fileCaption,
                $extension
            );
            $log->status = $response->status ? CommonConst::SUCCESS : CommonConst::FAILED;
            $log->message = $response->message ?? '';
            $message = $response->status ? 'WhatsApp message sent successfully.' : ($response->message ?? 'Failed to send WhatsApp message.');
        } elseif ($info->socialPlatform === CommonConst::EMAIL) {
            try {
                $mailData = [
                    'subject'                => "Site Visit Sent Info User " . $info->name,
                    'receiver_contact'       => $receiverContact,
                    'mail_log_id'            => $log->id,
                    'attachment_path'         => $fileUrl ? "public/$path" : null,
                    'attachment_original_name' => $filename ?: null,
                    'hidden_pre_header'      => "This is your Site Visit summary. Please check the attachment for full details.",
                    'content'                => $info->message,
                ];
                Mail::to($receiverContact)->send(new MailSend($mailData));
                $log->status = CommonConst::SUCCESS;
                $message = 'Email sent successfully.';
            } catch (\Exception $e) {
                createExceptionError($e, "SiteVisitService", __FUNCTION__);
                $log->status = CommonConst::FAILED;
                $log->message = $e->getMessage();
                $message = $e->getMessage();
            }
        }

        $log->save();

        # Fire notification event
        try {
            event(new NotificationMessage($log->subject, $info->sender_id, $info->socialPlatform));
        } catch (\Exception $e) {
            createExceptionError($e, "SiteVisitService", __FUNCTION__);
        }

        return [
            "status"             => $log->status == CommonConst::SUCCESS ? 200 : 409,
            "message"            => $message,
            "notification_log_id" => $log->id,
        ];
    }
}
