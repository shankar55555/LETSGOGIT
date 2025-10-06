<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use App\Models\User;

class Attendance extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'user_id',
        'attendance_date',
        'login_time',
        'logout_time',
        'session_token',
        'device_info',
        'is_manual',
    ];


    protected $casts = [
        'attendance_date' => 'date:Y-m-d',
        'login_time' => 'datetime:h:i:s A',
        'logout_time' => 'datetime:h:i:s A',
        'is_manual' => 'boolean',
        'device_info' => 'array',
    ];

    protected $attributes = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id", 'uuid');
    }
}
