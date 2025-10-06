<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserTarget extends Model
{
    use HasUuids;

    protected $table = "targets_and_incentives";
    protected $fillable = ['user_id', 'target_amount', 'achieved_amount', 'incentive_percentage', 'incentive', 'month', 'is_paid'];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "uuid");
    }
}
