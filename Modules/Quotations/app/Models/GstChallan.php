<?php

namespace Modules\Quotations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Uuids;

class GstChallan extends Model
{
    use SoftDeletes, Uuids;

    protected $fillable = [
        'cpin',
        'challan_number',
        'gstin',
        'payment_type',
        'financial_year',
        'tax_period',
        'tax_items',
        'total_amount',
        'challan_date',
        'status',
        'created_by',
        'last_updated_by'
    ];

    protected $casts = [
        'challan_date' => 'date',
        'tax_items' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'last_updated_by', 'uuid');
    }
} 
