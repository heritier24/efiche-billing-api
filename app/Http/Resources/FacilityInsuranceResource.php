<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityInsuranceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->insurance->name,
            'code' => $this->insurance->code,
            'coverage_percentage' => (float) $this->coverage_percentage,
            'max_claim_amount' => $this->max_claim_amount ? (float) $this->max_claim_amount : null,
            'requires_preauth' => $this->requires_preauth,
            'is_active' => $this->is_active,
        ];
    }
}
