<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class MailSend extends Mailable
{
    use Queueable, SerializesModels;

    public $email_info;

    public function __construct($email_info)
    {
        $this->email_info = $email_info;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->email_info['subject'] ?? 'Mail Subject'
        );
    }

    public function content(): Content
    {
        $setting = Setting::pluck('value', 'key') ?? [];

        # Ensure app URL has no trailing slash
        $appUrl = rtrim(config('app.url'), '/');

        # Handle company_logo path
        if (!empty($setting['company_logo'])) {
            $setting['company_logo'] = $appUrl . '/' . ltrim($setting['company_logo'], '/');
        }

        return new Content(
            view: 'email.email_content',
            with: [
                'content' => $this->email_info['content'],
                'hidden_pre_header' => $this->email_info['hidden_pre_header'],
                'setting' => $setting,
            ]
        );
    }

    public function attachments(): array
    {
        if (!empty($this->email_info['attachment_path'])) {
            return [
                Attachment::fromStorage($this->email_info['attachment_path'])
                    ->as($this->email_info['attachment_original_name'] ?? 'attachment.pdf')
            ];
        }

        return [];
    }
}
