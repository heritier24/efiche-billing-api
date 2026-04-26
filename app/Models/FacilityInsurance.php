<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'insurance_id',
        'is_active',
        'coverage_percentage',
        'max_claim_amount',
        'requires_preauth'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'coverage_percentage' => 'decimal:2',
        'max_claim_amount' => 'decimal:2',
        'requires_preauth' => 'boolean'
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }
}
