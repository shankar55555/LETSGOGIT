<?php

namespace Modules\Targets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;

class Incentive extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        "user_id",
        "target_id",
        "amount",
        "status",
        "payment_date",
        "notes",
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(status) LIKE ?', ["%{$term}%"]);
        });
    }

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => now()
        ]);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'uuid');
    }
}
