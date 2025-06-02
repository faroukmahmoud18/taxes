<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // More specific authorization (ownership) should be in the controller or a policy
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'description.en' => 'required_without_all:description.de,description.ar|nullable|string|max:1000',
            'description.de' => 'required_without_all:description.en,description.ar|nullable|string|max:1000',
            'description.ar' => 'required_without_all:description.en,description.de|nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'expense_date' => 'required|date|before_or_equal:today',
            'category' => 'nullable|string|max:100',
            'is_business_expense' => 'sometimes|boolean',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }
    
    public function messages(): array
    {
        return [
            'description.en.required_without_all' => 'At least one language description (e.g., English) is required.',
            'description.de.required_without_all' => 'At least one language description (e.g., German) is required.',
            'description.ar.required_without_all' => 'At least one language description (e.g., Arabic) is required.',
            'amount.required' => 'The expense amount is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.01.',
            'expense_date.required' => 'The expense date is required.',
            'expense_date.date' => 'The expense date must be a valid date.',
            'expense_date.before_or_equal' => 'The expense date cannot be in the future.',
            'receipt.mimes' => 'Receipt must be a file of type: jpg, jpeg, png, pdf.',
            'receipt.max' => 'Receipt may not be greater than 2MB.',
        ];
    }

    protected function passedValidation()
    {
        $descriptions = array_filter($this->input('description', []));
        if (empty($descriptions) && !$this->has('description_is_optional_marker_for_empty_allowed')) {
            $validator = $this->getValidatorInstance();
             if ($validator->errors()->isEmpty()) {
                $validator->errors()->add('description', 'At least one description (English, German, or Arabic) must be provided.');
            }
        }
        
        // Convert is_business_expense to boolean
        // If 'is_business_expense' is not present in the request, it defaults to false (due to checkbox behavior)
        // If it is present (even as "0" from hidden field), boolean() handles it.
        $this->merge(['is_business_expense' => $this->boolean('is_business_expense')]);
    }
}
