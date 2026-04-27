<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|regex:/^\d{1,8}(\.\d{1,2})?$/',
            'method' => ['required', Rule::in(['cash', 'mobile_money', 'insurance'])],
            'phone' => 'required_if:method,mobile_money|regex:/^\+2507\d{8}$/',
            'notes' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'amount.regex' => 'Amount must be a valid currency format (e.g., 10000.50)',
            'phone.regex' => 'Phone number must be a valid Rwanda number (e.g., +250788123456)',
            'phone.required_if' => 'Phone number is required for mobile money payments'
        ];
    }
}
