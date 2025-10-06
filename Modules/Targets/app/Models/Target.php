<?php

namespace Modules\Targets\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;

class Target extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        "title",
        "target_type",
        "target_value",
        'target_amount',
        'incentive_percent',
        "start_date",
        "end_date",
        "created_by",
        "last_updated_by"
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'incentive_percent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->setDatesBasedOnType();
        });

        static::updating(function ($model) {
            if ($model->isDirty('target_type')) {
                $model->setDatesBasedOnType();
            }
        });
    }

    public function setDatesBasedOnType()
    {
        $now = Carbon::now();

        switch ($this->target_type) {
            case 'Daily':
                $this->start_date = $now->format('Y-m-d');
                $this->end_date = $now->format('Y-m-d');
                break;

            case 'Weekly':
                $this->start_date = $now->startOfWeek()->format('Y-m-d');
                $this->end_date = $now->endOfWeek()->format('Y-m-d');
                break;

            case 'Monthly':
                $this->start_date = $now->startOfMonth()->format('Y-m-d');
                $this->end_date = $now->endOfMonth()->format('Y-m-d');
                break;
        }
    }

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"]);
        });
    }

    public function softDelete()
    {
        return $this->update([
            'last_updated_by' => Auth::user()->uuid,
            'updated_at' => now(),
        ]);
    }

    public function incentive()
    {
        return $this->hasOne(Incentive::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function calculateProgress($currentValue)
    {
        return ($currentValue / $this->target_value) * 100;
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }
}
