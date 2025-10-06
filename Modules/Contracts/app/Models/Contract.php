<?php

namespace Modules\Contracts\Models;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Modules\Clients\Models\Client;
use Modules\Invoices\Models\Invoice;
use Modules\Quotations\Models\Quotation;

class Contract extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'items',
        'start_date',
        'end_date',
        'sub_total',
        'discount',
        'tax',
        'total',
        'status',
        'client_id',
        'quotation_id',
        'invoice_id',
        'created_by',
        'last_updated_by'
    ];

    protected $casts = [
        'items' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'sub_total' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"]);
        });
    }

    // Changed: Simplified status scope (no enum)
    public function scopeFilterByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWhereClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public static function createWithAttributes(array $attributes)
    {
        return static::create(array_merge([
            'id' => Str::orderedUuid(),
        ], $attributes));
    }

    public function updateWithAttributes(array $attributes)
    {
        return $this->update(array_merge($attributes, [
            'last_updated_by' => loginUserId(),
        ]));
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }

    public function status_info()
    {
        return $this->hasOne(AdminControlConfig::class, 'slug', 'call_status')->where('status_for', CommonConst::MODULE_CONTRACT)->select('id', 'status_for', 'status_text', 'slug', 'status_color');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function quotation()
    {
        return $this->hasOne(Quotation::class, 'id',  'quotation_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'contract_id',  'id');
    }
}
