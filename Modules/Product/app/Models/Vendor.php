<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'vendors';

    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'gstin',
        'created_by',
        'last_updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the creator of this vendor.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::user()->uuid ?? null;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->last_updated_by = Auth::user()->uuid ?? null;
            }
        });
    }

    /**
     * Get the full name of the vendor.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
