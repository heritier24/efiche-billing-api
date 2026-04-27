<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'facility_id' => $this->facility_id,
            'visit_type' => $this->visit_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Include patient data when loaded
            'patient' => $this->when(
                $this->relationLoaded('patient'),
                function () {
                    return [
                        'id' => $this->patient->id,
                        'full_name' => $this->patient->full_name,
                        'first_name' => $this->patient->first_name,
                        'last_name' => $this->patient->last_name,
                        'phone' => $this->patient->phone,
                        'email' => $this->patient->email,
                    ];
                }
            ),
            
            // Include invoice data when loaded
            'invoices' => $this->when(
                $this->relationLoaded('invoices'),
                function () {
                    return $this->invoices->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'status' => $invoice->status,
                            'total_amount' => (float) $invoice->total_amount,
                            'created_at' => $invoice->created_at->toISOString(),
                        ];
                    });
                }
            ),
        ];
    }
}
