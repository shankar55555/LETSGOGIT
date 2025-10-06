<?php

namespace Modules\Targets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncentiveStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'target_id' => 'required|exists:targets,id',
            'user_id' => 'required|exists:users,uuid',
            'notes' => 'nullable|string|in:Daily,Weekly,Monthly',
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
