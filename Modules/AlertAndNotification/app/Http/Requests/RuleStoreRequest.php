<?php

namespace Modules\AlertAndNotification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RuleStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'rule' => 'required|string|max:255|unique:rules,rule',
            'rule_slug' => 'required|string|max:255',
            'condition_type' => 'nullable|string',
            'conditions' => 'nullable|json',
            'actions' => 'required|json',
            'status' => 'required',
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
