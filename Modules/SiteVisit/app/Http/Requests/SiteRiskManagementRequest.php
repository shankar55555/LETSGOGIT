<?php

namespace Modules\SiteVisit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteRiskManagementRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => [
                'required',
                'string',
            ],
            'phone' => [
                'required',
                'string',
                'max:16',
                'regex:/^[0-9+\-\s()]+$/'
            ],
            'email' => [
                'required',
                'email',
                'max:255'
            ],
            'address' => [
                'required',
                'string',
                'min:10',
                'max:500'
            ],
            'building_type' => [
                'required',
                'string',
            ],
            'roof_type' => [
                'required',
                'string',
            ],
            'height_of_roof' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[0-9]+(\.[0-9]+)?\s*(m|ft|meters|feet)?$/'
            ],
            'service' => [
                'required',
                'string',
            ],
            'visit_datetime' => [
                'required',
                'date'
            ],
            'solution_recommended' => [
                'required',
                'string',
                'min:20',
                'max:2000'
            ],
            'visit_assignee_id' => [
                'required',
                'string',
                'exists:users,uuid'
            ],
            'status' => [
                'required',
                'string',
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Customer name is required',
            'customer_name.regex' => 'Customer name should contain only letters and spaces',
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Please enter a valid phone number',
            'email.email' => 'Please enter a valid email address',
            'address.required' => 'Address is required',
            'address.min' => 'Address should be at least 10 characters',
            'building_type.required' => 'Building type is required',
            'building_type.in' => 'Please select a valid building type',
            'roof_type.required' => 'Roof type is required',
            'roof_type.in' => 'Please select a valid roof type',
            'height_of_roof.regex' => 'Please enter a valid height (e.g., 10m or 30ft)',
            'service.required' => 'Service type is required',
            'service.in' => 'Please select a valid service type',
            'visit_datetime.required' => 'Visit date and time is required',
            'visit_datetime.after_or_equal' => 'Visit date cannot be in the past',
            'solution_recommended.required' => 'Solution recommendation is required',
            'solution_recommended.min' => 'Solution recommendation should be at least 20 characters',
            'visit_assignee_id.exists' => 'Selected assignee does not exist'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_name' => trim($this->customer_name),
            'phone' => preg_replace('/[^0-9+]/', '', $this->phone),
            'email' => strtolower(trim($this->email)),
            'address' => trim($this->address),
            'height_of_roof' => trim($this->height_of_roof),
            'solution_recommended' => trim($this->solution_recommended)
        ]);
    }
}
