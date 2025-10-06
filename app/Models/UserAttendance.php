<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAttendance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_attendance';

    protected $fillable = [
        'user_id',
        'attendance_date',
        'time_in',
        'time_out',
        'work',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }
}
