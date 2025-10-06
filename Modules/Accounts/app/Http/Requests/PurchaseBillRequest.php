<?php

namespace Modules\Accounts\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'bill_number' => 'required|string|max:255',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'vendor_id' => 'required|uuid|exists:vendors,id',
            'vendor_state' => 'required|string|max:255',
            'purchase_mode' => 'required|string|in:inventory,asset,both',
            'payment_mode' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'sub_total' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'cgst_amount' => 'nullable|numeric|min:0',
            'sgst_amount' => 'nullable|numeric|min:0',
            'igst_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:255',
            'bill_image' => 'nullable|image|max:2048',

            // Items validation
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_type' => 'required|string|in:inventory,asset',
            'items.*.account_id' => 'nullable',
            'items.*.product_id' => 'nullable|uuid|exists:products,id',
            'items.*.variant_id' => 'nullable|uuid|exists:product_variants,id',
            'items.*.sku' => 'nullable|string|max:255',
            'items.*.hsn_sac' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.gst_percentage' => 'required|numeric|min:0',
            'items.*.amount' => 'required|numeric|min:0',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Decode JSON items if it's a string (from FormData)
        if ($this->has('items') && is_string($this->items)) {
            $this->merge([
                'items' => json_decode($this->items, true)
            ]);
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'bill_number.required' => 'Bill number is required',
            'bill_date.required' => 'Bill date is required',
            'due_date.required' => 'Due date is required',
            'due_date.after_or_equal' => 'Due date must be after or equal to bill date',
            'vendor_id.required' => 'Vendor is required',
            'vendor_state.required' => 'Vendor state is required',
            'purchase_mode.required' => 'Purchase mode is required',
            'payment_mode.required' => 'Payment mode is required',
            'sub_total.required' => 'Subtotal is required',
            'tax_amount.required' => 'Tax amount is required',
            'total_amount.required' => 'Total amount is required',
            'items.required' => 'At least one item is required',
            'items.*.item_name.required' => 'Item name is required',
            'items.*.quantity.required' => 'Quantity is required',
            'items.*.quantity.min' => 'Quantity must be greater than 0',
            'items.*.rate.required' => 'Rate is required',
            'items.*.gst_percentage.required' => 'GST percentage is required',
            'items.*.amount.required' => 'Amount is required',
        ];
    }
}
