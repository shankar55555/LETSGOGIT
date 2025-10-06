<?php

namespace Modules\AlertAndNotification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationVariable extends Model
{
    use HasFactory, HasUuids;

    protected $table = "notification_variables";
    protected $fillable = ['notification_type_id', 'variables', 'enable'];

    public function notification_type()
    {
        return $this->hasOne(NotificationType::class, 'id', 'notification_type_id');
    }
}
