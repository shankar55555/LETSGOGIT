<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\ProductVariantImage;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'mrp',
        'stock_quantity',
        'low_stock_alert',
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_alert' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the images for the variant.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductVariantImage::class, 'variant_id', 'id')->ordered();
    }

    /**
     * Get the primary image for the variant.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductVariantImage::class, 'variant_id', 'id')->primary();
    }

    /**
     * Scope to filter variants with low stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_alert');
    }

    /**
     * Scope to filter variants by product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Check if variant is low on stock.
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_alert;
    }
}
