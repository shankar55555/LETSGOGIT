<?php

namespace Modules\Targets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TargetUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'target_type' => 'nullable|string|in:Daily,Weekly,Monthly',
            'target_value' => 'required|integer',
            'target_amount' => 'required',
            'incentive_percent' => 'required',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
