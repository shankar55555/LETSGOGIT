<?php

namespace Modules\AlertAndNotification\Jobs;

use App\Constants\CommonConst;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\AlertAndNotification\Models\NotificationLog;
use App\Mail\MailSend;
use Throwable;

class EmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $emailPriority;
    protected ?string $mailLogId;
    protected array $userList;
    protected array $emailContentInfo;
    protected array $additionalInfo;

    public function __construct(?string $mailLogId = null, array $userList = [], array $emailContentInfo = [], array $additionalInfo = [])
    {
        $this->mailLogId = $mailLogId;
        $this->userList = $userList;
        $this->emailContentInfo = $emailContentInfo;
        $this->additionalInfo = $additionalInfo;
        $this->emailPriority = CommonConst::HIGH;
    }

    public function handle(): void
    {
        i("EmailJob : handle ===");

        if ($this->mailLogId) {
            i("EmailJob : Fetching NotificationLog by ID: {$this->mailLogId}");
            $logs = NotificationLog::where('id', $this->mailLogId)->get();

            foreach ($logs as $log) {
                $this->processAndSend($log);
            }
        } elseif (!empty($this->userList) && !empty($this->emailContentInfo)) {
            i("EmailJob :Creating and sending new email(s) to userList...");
            $this->createAndSendNewEmail();
        } else {
            i("EmailJob :No mailLogId or userList. Fetching PENDING logs with priority = {$this->emailPriority}");
            $logs = NotificationLog::where('status', CommonConst::PENDING)
                ->where('priority', $this->emailPriority)
                ->limit(20)
                ->get();

            foreach ($logs as $log) {
                $this->processAndSend($log);
            }
        }

        i("EmailJob : handle END ===");
    }

    protected function createAndSendNewEmail(): void
    {
        foreach ($this->userList as $user) {
            i("EmailJob : [createAndSendNewEmail] Processing user: {$user['uuid']}");

            $this->additionalInfo['receiver_id'] = $user['uuid'];
            $this->additionalInfo['receiver_contact'] = $user['email'];
            $this->additionalInfo['user_name'] = $user['name'];

            $notificationTypeId = $this->additionalInfo['notification_type_id'] ?? null;
            $receiverContact = $this->additionalInfo['receiver_contact'];
            $isNotification = $this->additionalInfo['is_notification'] ?? true;

            if ($notificationTypeId) {
                $content = makeMessageContent($this->emailContentInfo, $notificationTypeId, CommonConst::EMAIL);
            } else {
                $userName = $user['name'];
                $content = (object)[
                    "status" => true,
                    "hidden_pre_header" => "",
                    "subject" => $this->additionalInfo['subject'] . ' ' . $userName,
                    "priority" => CommonConst::HIGH,
                    "simple_content" => $this->emailContentInfo['message'] ?? 'No content'
                ];
            }

            if (!$content->status) {
                er("EmailJob : [createAndSendNewEmail] Failed to generate content for user {$user['email']}");
                continue;
            }

            $this->additionalInfo['hidden_pre_header'] = $content->hidden_pre_header;

            $logData = [
                'receiver_contact' => $receiverContact,
                'subject' => $content->subject,
                'content' => $content->content ?? $content->simple_content ?? '',
                'priority' => $content->priority,
                'status' => CommonConst::PENDING,
                'notification_type_id' => $notificationTypeId,
                'receiver_id' => $this->additionalInfo['receiver_id'],
                'section_type' => CommonConst::EMAIL,
                'is_notification' => $isNotification,
                'email_body' => json_encode($this->emailContentInfo),
                'additional_info' => json_encode($this->additionalInfo),
                'sender_id' => $this->additionalInfo['sender_id'] ?? null,
                'module_id' => $this->emailContentInfo && isset($this->emailContentInfo['id']) ? $this->emailContentInfo['id'] : null,
            ];

            $log = NotificationLog::create($logData);
            i("EmailJob : [createAndSendNewEmail] Log created ID: {$log->id} for {$receiverContact}");

            if ($log && !$isNotification) {
                $this->sendEmail($receiverContact, $content, $this->additionalInfo, $log);
            } else {
                $log->status = CommonConst::SUCCESS;
                $log->save();
                i("EmailJob : [createAndSendNewEmail] Notification-only flag set. Marked as SUCCESS.");
            }
        }
    }

    protected function processAndSend(NotificationLog $log): void
    {
        i("EmailJob : [processAndSend] Log ID: {$log->id}");

        $additionalInfo = is_array($log->additional_info) ? $log->additional_info : json_decode($log->additional_info, true);
        $emailBody = is_array($log->email_body) ? $log->email_body : json_decode($log->email_body, true);

        $content = makeMessageContent($emailBody, $log->notification_type_id, CommonConst::EMAIL);

        if (!$content->status) {
            er("EmailJob : [processAndSend] Failed to generate content for Log ID: {$log->id}");
            return;
        }

        $this->sendEmail($log->receiver_contact, $content, $additionalInfo, $log);
    }

    protected function sendEmail(string $to, object $content, array $info, NotificationLog $log): void
    {
        try {
            i("EmailJob : [sendEmail] Sending email to: {$to}");

            $emailData = [
                'subject' => $content->subject,
                'attachment_path' => $info['attachment_path'] ?? null,
                'attachment_original_name' => $info['attachment_original_name'] ?? null,
                'hidden_pre_header' => $content->hidden_pre_header,
                'content' => $content->simple_content ?? $content->content ?? '',
            ];

            i("EmailJob : [sendEmail] Email data: " . json_encode($emailData));

            Mail::to($to)->send(new MailSend($emailData));

            $log->status = CommonConst::SUCCESS;
            i("EmailJob : [sendEmail] Email sent successfully to {$to}");
        } catch (\Exception $e) {
            createExceptionError($e, 'EmailJob', __FUNCTION__);
            $log->status = CommonConst::FAILED;
            $log->message = $e->getMessage();
            er("EmailJob : [sendEmail] FAILED to send email to {$to}. Reason: {$e->getMessage()}");
        }

        $log->save();
    }

    /**
     * Handle a failed job.
     */
    public function failed(Throwable $exception): void
    {
        er("EmailJob : [EmailJob FAILED] Reason: {$exception->getMessage()}");
        createExceptionError($exception, 'EmailJob', 'failed');
    }
}
