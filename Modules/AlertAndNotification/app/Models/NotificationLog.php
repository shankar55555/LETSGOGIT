<?php

namespace Modules\AlertAndNotification\Models;

use App\Constants\CommonConst;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\FollowUp\Models\FollowUp;
use Nwidart\Modules\Facades\Module;

class NotificationLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notification_logs';

    protected $fillable = [
        'priority',
        'status',
        'receiver_contact',
        'subject',
        'content',
        'message',
        'receiver_id',
        'sender_id',
        'is_delete',
        'notification_type_id',
        'module_id',
        'section_type',
        'email_body',
        'additional_info',
        "is_notification",
        "showing_user_ids",
    ];

    public $casts = [
        'email_body' => 'string',
        'additional_info' => 'string',
        'showing_user_ids' => 'array',
    ];
    protected $appends = ['is_read_by_user'];

    # Custom Accessor
    public function getIsReadByUserAttribute()
    {
        $userId = Auth::User()->uuid ?? null;
        if (!$userId)  return false;
        return in_array($userId, $this->showing_user_ids ?? []);
    }

    /**
     * Scope a query to apply a flexible search across key fields.
     */
    public function scopeSearch($query, $search)
    {
        $term = strtolower($search);

        return $query->where(function ($q) use ($term) {
            $q->where('receiver_contact', 'ILIKE', "%{$term}%")
                ->orWhere('subject', 'ILIKE', "%{$term}%")
                ->orWhere('status', 'ILIKE', "%{$term}%")
                ->orWhereHas('notification_type', fn($sub) => $sub->whereRaw("LOWER(title) LIKE ?", ["%{$term}%"]))
                ->orWhereHas('sender', fn($sub) => $sub->whereRaw("LOWER(name) LIKE ?", ["%{$term}%"]));
        });
    }

    public function sender()
    {
        return $this->hasOne(User::class, 'uuid', 'sender_id')->select('uuid', 'name', 'email', 'avatar');
    }

    public function receiver()
    {
        return $this->hasOne(User::class, 'uuid', 'receiver_id')->select('uuid', 'name', 'email', 'avatar');
    }

    public function receiver_b_to_b()
    {
        if (Module::has(CommonConst::MODULE_ALERT_AND_NOTIFICATION)) {
            return $this->hasOne(\Modules\AlertAndNotification\Models\BToBUser::class, 'id', 'receiver_id')->select('id', 'name', 'avatar');
        } else {
            return null;
        }
    }

    public function receiver_client()
    {
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            return $this->hasOne(\Modules\Clients\Models\Client::class, 'id', 'receiver_id')->select('id', 'name', 'avatar');
        } else {
            return null;
        }
    }

    public function receiver_lead()
    {
        if (Module::has(CommonConst::MODULE_LEAD)) {
            return $this->hasOne(\Modules\Leads\Models\Lead::class, 'id', 'receiver_id')->select('id', 'name');
        } else {
            return null;
        }
    }

    public function notification_type()
    {
        return $this->hasOne(NotificationType::class, 'id', 'notification_type_id')->select('id', 'title');
    }

    public function lead()
    {
        if (Module::has(CommonConst::MODULE_LEAD)) {
            return $this->hasOne(\Modules\Leads\Models\Lead::class, 'id', 'module_id');
        } else {
            return null;
        }
    }

    public function client()
    {
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            return $this->hasOne(\Modules\Clients\Models\Client::class, 'id', 'module_id');
        } else {
            return null;
        }
    }

    public function quotation()
    {
        if (Module::has(CommonConst::MODULE_QUOTATION)) {
            return $this->hasOne(\Modules\Quotations\Models\Quotation::class, 'id', 'module_id');
        } else {
            return null;
        }
    }

    public function srm()
    {
        if (Module::has(CommonConst::MODULE_SITE_VISIT)) {
            return $this->hasOne(\Modules\SiteVisit\Models\SiteVisit::class, 'id', 'module_id');
        } else {
            return null;
        }
    }

    public function contract()
    {
        if (Module::has(CommonConst::MODULE_CONTRACT)) {
            return $this->hasOne(\Modules\Contracts\Models\Contract::class, 'id', 'module_id');
        } else {
            return null;
        }
    }

    public function schedule()
    {
        return null;
    }

    public function b_to_b_user()
    {
        return $this->hasOne(BToBUser::class, 'id', 'module_id');
    }

    public function follow_up()
    {
        return $this->hasOne(FollowUp::class, 'id', 'module_id');
    }
}
