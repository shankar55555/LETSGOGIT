<?php

namespace Modules\Clients\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Clients\app\Models\Client;

class ClientUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $clientId = $this->route('client'); // Gets the UUID from route parameter
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:255',
            'contact_person' => 'nullable',
            'contact_person_role' => 'nullable',
            'gst' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients')->ignore($clientId)->whereNull('deleted_at')
            ],
            'phone' => 'sometimes|string|max:20',
            'secondary_phone' => 'nullable',
            'status' => [
                'sometimes',
                'string',
                'max:50'
            ],
            'assigned_user' => 'nullable',
            'lead_id' => [
                'nullable',
                'uuid',
                Rule::exists('leads', 'id')->whereNull('deleted_at')
            ],
            'city_id' => 'nullable',
            'date_of_birth' => 'nullable|date',
            'anniversary_date' => 'nullable|date',
        ];
    }
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already associated with another client',
            'assigned_user.exists' => 'The specified user does not exist',
            'lead_id.exists' => 'The referenced lead does not exist or was deleted',
            'status.in' => 'Invalid status value'
        ];
    }
    public function prepareForValidation()
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/[^0-9+]/', '', $this->phone)]);
        }
    }
    public function attributes(): array
    {
        return [
            'lead_id' => 'lead reference',
            'assigned_user' => 'assigned user'
        ];
    }
}
