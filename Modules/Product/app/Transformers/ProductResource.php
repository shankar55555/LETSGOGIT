<?php

namespace Modules\Product\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'purchase_no' => $this->purchase_no,
            'creator' => $this->creator,
            'updater' => $this->updater,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,

            // Core Information
            'category' => $this->category,
            'collection' => $this->collection,

            // Product Details
            'material_fabric' => $this->material_fabric,
            'care_instruction' => $this->care_instruction,
            'season' => $this->season,

            // Media & Branding
            'tags' => $this->tags ?? [],
            'status' => $this->status,
            'short_description' => $this->short_description,
            'detail_description' => $this->detail_description,

            // Variants
            'variants' => $this->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'image' => $variant->image,
                    'sku' => $variant->sku,
                    'mrp' => $variant->mrp,
                    'stock_quantity' => $variant->stock_quantity,
                    'low_stock_alert' => $variant->low_stock_alert,
                    'is_low_stock' => $variant->isLowStock(),
                    'images' => $variant->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'url' => $image->url,
                            'name' => $image->name,
                            'sort_order' => $image->sort_order,
                            'is_primary' => $image->is_primary,
                        ];
                    }) ?? [],
                ];
            }) ?? [],
        ];
    }
}
