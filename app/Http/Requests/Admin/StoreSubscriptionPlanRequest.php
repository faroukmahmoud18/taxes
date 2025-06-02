<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreSubscriptionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name.en' => 'required|string|max:255',
            'name.de' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'features.en' => 'nullable|string',
            'features.de' => 'nullable|string',
            'features.ar' => 'nullable|string',
            'paypal_plan_id' => 'nullable|string|max:255|unique:subscription_plans,paypal_plan_id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.en.required' => 'The English name is required.',
            'name.de.required' => 'The German name is required.',
            'name.ar.required' => 'The Arabic name is required.',
            // Add other custom messages as needed
        ];
    }
}
