<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'purchase_no' => 'required|string|max:100|unique:products,purchase_no',
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
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.mrp' => 'required_with:variants|numeric|min:0',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.low_stock_alert' => 'nullable|integer|min:0',
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*.id' => 'nullable|string',
            'variants.*.images.*.url' => 'nullable|string',
            'variants.*.images.*.name' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required',
            'purchase_no.required' => 'The purchase number is required',
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
            'variants.*.images.array' => 'Variant images must be an array',
        ];
    }
}
