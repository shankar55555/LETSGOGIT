<?php

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Models\Vendor;

class PurchaseBill extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'bill_number',
        'bill_date',
        'due_date',
        'vendor_id',
        'vendor_state',
        'purchase_mode',
        'items',
        'notes',
        'bill_image',
        'sub_total',
        'tax_amount',
        'discount_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'status',
        'payment_mode',
        'created_by',
        'last_updated_by'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'items' => 'array',
        'sub_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::user()->uuid;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->last_updated_by = Auth::user()->uuid;
            }
        });
    }

    /**
     * Get the vendor associated with the purchase bill.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    /**
     * Get the items associated with the purchase bill.
     */
    public function items()
    {
        return $this->hasMany(PurchaseBillItem::class, 'purchase_bill_id', 'id');
    }

    /**
     * Get the user who created the purchase bill.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    /**
     * Get the user who last updated the purchase bill.
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }
}
