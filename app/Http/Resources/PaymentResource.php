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
            'status' => $this->status,
            'transaction_ref' => $this->transaction_ref,
            'cashier_id' => $this->cashier_id,
            'processed_at' => $this->processed_at->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'notes' => $this->notes,
        ];
    }
}
