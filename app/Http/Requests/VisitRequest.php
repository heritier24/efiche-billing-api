<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class VisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/service
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'facility_id' => 'sometimes|integer|exists:facilities,id',
            'visit_type' => 'required|in:consultation,follow_up,emergency,general',
            'status' => 'sometimes|in:active,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Patient ID is required',
            'patient_id.integer' => 'Patient ID must be a valid integer',
            'patient_id.exists' => 'Selected patient does not exist',
            'facility_id.integer' => 'Facility ID must be a valid integer',
            'facility_id.exists' => 'Selected facility does not exist',
            'visit_type.required' => 'Visit type is required',
            'visit_type.in' => 'Visit type must be one of: consultation, follow_up, emergency, general',
            'status.in' => 'Status must be one of: active, completed, cancelled',
            'notes.string' => 'Notes must be text',
            'notes.max' => 'Notes cannot exceed 1000 characters',
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
