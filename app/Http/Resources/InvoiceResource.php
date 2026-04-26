<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_id' => $this->visit_id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'insurance_coverage' => (float) $this->insurance_coverage,
            'patient_responsibility' => (float) $this->patient_responsibility,
            'total_paid' => (float) $this->total_paid,
            'remaining_balance' => (float) $this->remaining_balance,
            'due_date' => $this->due_date->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'line_items' => InvoiceLineItemResource::collection($this->whenLoaded('lineItems')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'visit' => [
                'id' => $this->visit->id,
                'patient' => [
                    'id' => $this->visit->patient->id,
                    'full_name' => $this->visit->patient->full_name,
                    'phone' => $this->visit->patient->phone,
                ],
                'facility' => [
                    'id' => $this->visit->facility->id,
                    'name' => $this->visit->facility->name,
                ],
            ],
        ];
    }
}
