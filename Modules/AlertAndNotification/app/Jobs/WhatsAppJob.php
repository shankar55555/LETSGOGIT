<?php

namespace Modules\AlertAndNotification\Jobs;

use App\Constants\CommonConst;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Services\WhatsAppService;

class WhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $priority;
    protected ?string $logId;
    protected array $userList;
    protected array $contentInfo;
    protected array $additionalInfo;

    public function __construct(?string $logId = null, array $userList = [], array $contentInfo = [], array $additionalInfo = [])
    {
        $this->logId = $logId;
        $this->userList = $userList;
        $this->contentInfo = $contentInfo;
        $this->additionalInfo = $additionalInfo;
        $this->priority = CommonConst::HIGH;
    }

    public function handle(): void
    {
        i("WhatsAppJob handle === ", 0);
        if ($this->logId) {
            $logs = NotificationLog::where('id', $this->logId)->get();
            i("WhatsAppJob handle === ", 1);
            foreach ($logs as $log) {
                $this->processAndSend($log);
            }
        } elseif (!empty($this->userList) || !empty($this->contentInfo)) {
            i("WhatsAppJob handle === ", 2);
            $this->createAndSendNewMessage();
        } else {
            i("WhatsAppJob handle === ", 3);
            $logs = NotificationLog::where('status', CommonConst::PENDING)
                ->where('priority', $this->priority)
                ->limit(20)
                ->get();

            foreach ($logs as $log) {
                $this->processAndSend($log);
            }
        }
    }

    protected function createAndSendNewMessage(): void
    {
        foreach ($this->userList as $key => $user) {
            i("WhatsAppJob createAndSendNewMessage ===", 201);
            $this->additionalInfo['receiver_id'] = $user['uuid'];
            $this->additionalInfo['receiver_contact'] = $user['phone'];
            $this->additionalInfo['user_name'] = trim($user['name']);

            $userName = trim($user['name']);

            $notificationTypeId = $this->additionalInfo['notification_type_id'] ?? null;
            $receiverContact = $this->additionalInfo['receiver_contact'];
            $isNotification = $this->additionalInfo['is_notification'] ?? true;

            if ($notificationTypeId) {
                $content = makeMessageContent($this->contentInfo, $notificationTypeId, CommonConst::WHATSAPP);
            } else {
                $content = (object)[
                    "status" => true,
                    "hidden_pre_header" => "",
                    "subject" => $this->additionalInfo['subject'] . $userName,
                    "priority" => CommonConst::HIGH,
                    "whats_app_message" => $this->contentInfo['message'],
                ];
                $this->additionalInfo['subject'] =  $this->additionalInfo['subject'] . $userName;
            }

            if ($content->status) {
                $this->additionalInfo['hidden_pre_header'] = $content->hidden_pre_header;
                $logData = [
                    'receiver_contact' => $receiverContact,
                    'subject' => $content->subject,
                    'content' => $content->whats_app_message,
                    'priority' => $content->priority,
                    'status' => CommonConst::PENDING,
                    'notification_type_id' => $notificationTypeId,
                    'receiver_id' => $this->additionalInfo['receiver_id'] ?? null,
                    'section_type' => CommonConst::WHATSAPP,
                    'is_notification' => $isNotification,
                    'email_body' => json_encode($this->contentInfo),
                    'additional_info' => json_encode($this->additionalInfo),
                    'sender_id' => $this->additionalInfo['sender_id'] ?? null,
                    'module_id' => $this->contentInfo && isset($this->contentInfo['id']) ? $this->contentInfo['id'] : null,
                ];

                $log = NotificationLog::create($logData);
                i("WhatsAppJob createAndSendNewMessage ===",  $log->receiver_contact);
                if ($log && !$isNotification) {
                    $this->sendMessage($userName, $receiverContact, $content, $this->additionalInfo, $log);
                } else {
                    $log->status = CommonConst::SUCCESS;
                    i("WhatsAppJob createAndSendNewMessage Else ===",  $log->status);
                    $log->save();
                }

                try {
                    event(new NotificationMessage($log->subject, $log->receiver_id, CommonConst::WHATSAPP));
                } catch (\Exception $e) {
                    createExceptionError($e, 'WhatsAppJob', __FUNCTION__);
                }
            }
        }
    }

    protected function processAndSend(NotificationLog $log): void
    {
        $additionalInfo = is_array($log->additional_info) ? $log->additional_info : json_decode($log->additional_info, true);
        $email_body = is_array($log->email_body) ? $log->email_body : json_decode($log->email_body, true);

        if ($log->notification_type_id) {
            $content = makeMessageContent($email_body, $log->notification_type_id, CommonConst::WHATSAPP);
        } else {
            $content = (object)[
                "status" => true,
                "hidden_pre_header" => "",
                "subject" => $additionalInfo['subject'],
                "priority" => CommonConst::HIGH,
                "whats_app_message" => $email_body['message'],
            ];
        }

        if (!$content->status) return;
        $this->sendMessage($additionalInfo['user_name'], $log->receiver_contact, $content, $additionalInfo, $log);
    }

    protected function sendMessage(string $userName, string $receiver_contact, object $content, array $info, NotificationLog $log): void
    {
        i("WhatsAppJob sendMessage ===");
        try {
            $plainTextMessage = str_replace(['<br>', '<br/>', '<br />'], "\n", $content->whats_app_message);
            $fileUrl = $info['fileUrl'] ?? '';
            $fileCaption = $info['fileCaption'] ?? '';
            $extension = $info['extension'] ?? '';

            $response = (new WhatsAppService())->sendMediaMessage($userName, $receiver_contact, $plainTextMessage, $fileUrl, $fileCaption, $extension);
            $log->status = $response->status ? CommonConst::SUCCESS : CommonConst::FAILED;
            $log->message = $response->message ?? '';
            $log->save();
        } catch (\Exception $e) {
            createExceptionError($e, 'WhatsAppJob', __FUNCTION__);
            $log->status = CommonConst::FAILED;
            $log->message = $e->getMessage();
            i("WhatsAppJob sendMessage FAILED ===", json_encode($log->message), 1);
        }

        $log->save();
    }

    public function failed(Exception $exception): void
    {
        er("WhatsAppJob : Failed: " . $exception->getMessage(), 1);
    }
}
