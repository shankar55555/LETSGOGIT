<?php

namespace Modules\AlertAndNotification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationCategory extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'notification_category';

    protected $fillable = ['category', 'is_delete'];

    public function notification_types()
    {
        return $this->hasMany(NotificationType::class, 'category_id', 'id')->with('notification_template_section', 'notification_variables');
    }
}
