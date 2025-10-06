<?php

namespace Modules\Leads\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:128',
            'contact_person' => 'nullable|string|max:64',
            'contact_person_role' => 'nullable|string|max:64',
            'email' => [
                'nullable',
                'email',
                'max:128',
                Rule::unique('leads')->whereNull('deleted_at')
            ],
            // 'phone' => 'required|string|max:16|unique:leads,phone',
            'phone' =>  Rule::unique('leads', 'phone')->whereNull('deleted_at'),
            'secondary_phone' => 'nullable',
            'address' => 'nullable|string',
            'status' => [
                'required',
                'string',
                'max:32',
            ],
            'source' => 'nullable|string|max:64',
            'referral_detail' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    // Require referral_detail when source is "Referral"
                    if ($this->input('source') === 'Referral' && empty($value)) {
                        $fail('Referral detail is required when source is Referral.');
                    }
                }
            ],
            'assigned_user' => 'nullable|uuid|exists:users,uuid',
            'note' => 'nullable|string',
            'visit_time' => 'nullable|date',
            "anniversary_date" => 'nullable',
            "date_of_birth" => 'nullable',
            "city_id" => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already associated with another lead',
            'assigned_user.exists' => 'The selected user does not exist',
            'status.in' => 'Invalid status value'
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/[^0-9+]/', '', $this->phone)]);
        }
    }
}
