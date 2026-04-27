<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class InvoiceAnalyticsRequest extends FormRequest
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
            'status_filter' => 'nullable|array|string',
            'status_filter.*' => 'in:pending,paid,overdue,partially_paid',
            'aging_days' => 'nullable|integer|min:1|max:365',
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
            'status_filter.*.in' => 'Status filter must include: pending, paid, overdue, partially_paid',
            'aging_days.integer' => 'Aging days must be a valid integer',
            'aging_days.min' => 'Aging days must be at least 1',
            'aging_days.max' => 'Aging days cannot exceed 365',
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
