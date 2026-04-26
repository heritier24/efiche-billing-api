<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->verifySignature();
    }

    public function rules(): array
    {
        return [
            'eventId' => 'required|string|max:100',
            'status' => ['required', Rule::in(['PAYMENT_COMPLETE', 'PAYMENT_FAILED'])],
            'orderNumber' => 'required|string|max:100',
            'amount' => 'required|integer|min:1',
            'phoneNumber' => 'required|regex:/^\+2507\d{8}$/',
            'timestamp' => 'required|date'
        ];
    }

    protected function verifySignature(): bool
    {
        $signature = $this->header('X-EfichePay-Signature');
        $payload = $this->getContent();
        
        if (!$signature || !$payload) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, config('services.efichepay.webhook_secret'));
        
        return hash_equals($expectedSignature, $signature);
    }

    public function messages(): array
    {
        return [
            'phoneNumber.regex' => 'Phone number must be a valid Rwanda number',
            'status.in' => 'Status must be either PAYMENT_COMPLETE or PAYMENT_FAILED'
        ];
    }
}
