<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class PaymentMethodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'date_from' => 'required|date|after:2020-01-01',
            'date_to' => 'required|date|after:date_from',
            'group_by' => 'nullable|in:method,status,daily',
            'facility_id' => 'nullable|integer|exists:facilities,id',
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.required' => 'Start date is required',
            'date_from.date' => 'Start date must be a valid date',
            'date_from.after' => 'Start date must be after January 1, 2020',
            'date_to.required' => 'End date is required',
            'date_to.date' => 'End date must be a valid date',
            'date_to.after' => 'End date must be after start date',
            'group_by.in' => 'Group by must be one of: method, status, daily',
            'facility_id.integer' => 'Facility ID must be a valid integer',
            'facility_id.exists' => 'Selected facility does not exist',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
