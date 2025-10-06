<?php

namespace Modules\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalEntryRequest extends FormRequest
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
            'entry_date' => 'required|date',
            'voucher_type' => 'required|string|max:50',
            'description' => 'required|string|max:500',
            'status' => 'sometimes|in:pending,approved,rejected',
            'debit_entries' => 'required|array|min:1',
            'debit_entries.*.account_id' => 'required|integer|exists:accounts,id',
            'debit_entries.*.amount' => 'required|numeric|min:0.01',
            'credit_entries' => 'required|array|min:1',
            'credit_entries.*.account_id' => 'required|integer|exists:accounts,id',
            'credit_entries.*.amount' => 'required|numeric|min:0.01',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'entry_date.required' => 'Entry date is required.',
            'entry_date.date' => 'Entry date must be a valid date.',
            'voucher_type.required' => 'Voucher type is required.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'status.in' => 'Status must be pending, approved, or rejected.',
            'debit_entries.required' => 'At least one debit entry is required.',
            'debit_entries.min' => 'At least one debit entry is required.',
            'debit_entries.*.account_id.required' => 'Debit account is required.',
            'debit_entries.*.account_id.integer' => 'Debit account must be a valid ID.',
            'debit_entries.*.account_id.exists' => 'Selected debit account does not exist.',
            'debit_entries.*.amount.required' => 'Debit amount is required.',
            'debit_entries.*.amount.numeric' => 'Debit amount must be a number.',
            'debit_entries.*.amount.min' => 'Debit amount must be greater than 0.',
            'credit_entries.required' => 'At least one credit entry is required.',
            'credit_entries.min' => 'At least one credit entry is required.',
            'credit_entries.*.account_id.required' => 'Credit account is required.',
            'credit_entries.*.account_id.integer' => 'Credit account must be a valid ID.',
            'credit_entries.*.account_id.exists' => 'Selected credit account does not exist.',
            'credit_entries.*.amount.required' => 'Credit amount is required.',
            'credit_entries.*.amount.numeric' => 'Credit amount must be a number.',
            'credit_entries.*.amount.min' => 'Credit amount must be greater than 0.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $debitTotal = collect($this->debit_entries)->sum('amount');
            $creditTotal = collect($this->credit_entries)->sum('amount');

            // Check if debit and credit totals are balanced
            if (abs($debitTotal - $creditTotal) > 0.01) {
                $validator->errors()->add('balance', 'Total debit amount must equal total credit amount.');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Clean up debit entries - remove empty entries
        if ($this->has('debit_entries')) {
            $debitEntries = collect($this->debit_entries)
                ->filter(function ($entry) {
                    return !empty($entry['account_id']) && !empty($entry['amount']);
                })
                ->values()
                ->toArray();

            $this->merge(['debit_entries' => $debitEntries]);
        }

        // Clean up credit entries - remove empty entries
        if ($this->has('credit_entries')) {
            $creditEntries = collect($this->credit_entries)
                ->filter(function ($entry) {
                    return !empty($entry['account_id']) && !empty($entry['amount']);
                })
                ->values()
                ->toArray();

            $this->merge(['credit_entries' => $creditEntries]);
        }
    }
}
