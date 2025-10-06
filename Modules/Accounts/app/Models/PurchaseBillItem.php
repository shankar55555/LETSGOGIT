<?php

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Product\Models\Product;

class PurchaseBillItem extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'purchase_bill_id',
        'item_type', // 'inventory' or 'asset'
        'item_name',
        'account_id', // For inventory items
        'product_id', // For inventory items linked to products
        'variant_id', // Product variant ID for SKU selection
        'sku', // Product SKU
        'hsn_sac',
        'quantity',
        'rate',
        'discount',
        'gst_percentage',
        'amount',
        'created_by',
        'last_updated_by'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the purchase bill that owns the item.
     */
    public function purchaseBill()
    {
        return $this->belongsTo(PurchaseBill::class, 'purchase_bill_id', 'id');
    }

    /**
     * Get the account associated with the item (for inventory items).
     */
    // public function account()
    // {
    //     return $this->belongsTo(Account::class, 'account_id', 'id');
    // }

    /**
     * Get the product associated with the item (for inventory items).
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
