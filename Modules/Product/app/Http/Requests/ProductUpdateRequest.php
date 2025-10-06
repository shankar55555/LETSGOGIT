<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Models\ProductVariant;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'purchase_no' => 'sometimes|string|max:100',
            'category' => 'nullable|string|max:100',
            'collection' => 'nullable|string|max:100',

            // Product Details
            'material_fabric' => 'nullable|string|max:100',
            'care_instruction' => 'nullable|string',
            'season' => 'nullable|string|max:50',

            // Media & Branding
            'tags' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
            'short_description' => 'nullable|string',
            'detail_description' => 'nullable|string',

            // Variants
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|string',
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.mrp' => 'required_with:variants|numeric|min:0',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.low_stock_alert' => 'nullable|integer|min:0',
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*.url' => 'required_with:variants.*.images|string',
            'variants.*.images.*.name' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The name must be a string',
            'purchase_no.unique' => 'The purchase number must be unique',
            'category.string' => 'The category must be a string',
            'collection.string' => 'The collection must be a string',

            // Product Details
            'material_fabric.string' => 'The material/fabric must be a string',
            'care_instruction.string' => 'The care instruction must be a string',
            'season.string' => 'The season must be a string',

            // Media & Branding
            'tags.array' => 'Tags must be an array',
            'status.in' => 'The status must be either active or inactive',

            // Variants
            'variants.array' => 'Variants must be an array',
            'variants.*.sku.required_with' => 'SKU is required for each variant',
            'variants.*.sku.string' => 'SKU must be a string',
            'variants.*.mrp.required_with' => 'MRP is required for each variant',
            'variants.*.mrp.numeric' => 'MRP must be a number',
            'variants.*.mrp.min' => 'MRP cannot be negative',
            'variants.*.stock_quantity.integer' => 'Stock quantity must be an integer',
            'variants.*.stock_quantity.min' => 'Stock quantity cannot be negative',
            'variants.*.low_stock_alert.integer' => 'Low stock alert must be an integer',
            'variants.*.low_stock_alert.min' => 'Low stock alert cannot be negative',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateVariantSkuUniqueness($validator);
        });
    }

    /**
     * Validate that variant SKUs are unique globally except for variants of this product.
     */
    protected function validateVariantSkuUniqueness($validator)
    {
        $variants = $this->input('variants', []);
        $productId = $this->route('id');
        
        foreach ($variants as $index => $variant) {
            if (!isset($variant['sku'])) {
                continue;
            }
            
            $sku = $variant['sku'];
            $variantId = $variant['id'] ?? null;
            
            // Check if SKU exists in other products' variants
            $existingVariant = ProductVariant::where('sku', $sku)
                ->when($variantId, function ($query) use ($variantId) {
                    return $query->where('id', '!=', $variantId);
                })
                ->whereHas('product', function ($query) use ($productId) {
                    return $query->where('id', '!=', $productId);
                })
                ->first();
            
            if ($existingVariant) {
                $validator->errors()->add(
                    "variants.{$index}.sku",
                    "The SKU '{$sku}' is already used by another product variant."
                );
            }
        }
    }
}