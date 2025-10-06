<?php

namespace Modules\AlertAndNotification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplateSection extends Model
{
    use HasFactory, HasUuids;

    protected $table = "notification_template_sections";
    protected $fillable = ["title", 'notification_type_id', 'is_enable', 'email_subject', 'email_body', "whats_app_message", "sms_message", "app_message", "bell_notification_message", 'hidden_pre_header', 'priority'];

    public function notification_type()
    {
        return $this->hasOne(NotificationType::class, 'id', 'notification_type_id');
    }
}
