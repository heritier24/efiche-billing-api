<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'amount' => (float) $this->amount,
            'method' => $this->payment_method,
            'phone' => $this->phone ?? null, // Include phone if available
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'transaction_ref' => $this->transaction_ref,
            'cashier_id' => $this->cashier_id,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            
            // Enhanced data for frontend optimization
            'patient' => $this->when(
                $this->relationLoaded('invoice') && $this->invoice->relationLoaded('visit') && $this->invoice->visit->relationLoaded('patient'),
                function () {
                    return [
                        'id' => $this->invoice->visit->patient->id,
                        'full_name' => $this->invoice->visit->patient->full_name,
                        'first_name' => $this->invoice->visit->patient->first_name,
                        'last_name' => $this->invoice->visit->patient->last_name,
                    ];
                }
            ),
            
            'invoice' => $this->when(
                $this->relationLoaded('invoice'),
                function () {
                    return [
                        'id' => $this->invoice->id,
                        'invoice_number' => $this->invoice->invoice_number,
                        'total_amount' => (float) $this->invoice->total_amount,
                        'status' => $this->invoice->status,
                    ];
                }
            ),
        ];
    }
}
