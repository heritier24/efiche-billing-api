<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'contact_phone',
        'contact_email'
    ];

    public function facilityInsurances()
    {
        return $this->hasMany(FacilityInsurance::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'facility_insurances')
            ->withPivot(['coverage_percentage', 'max_claim_amount', 'requires_preauth', 'is_active'])
            ->wherePivot('is_active', true);
    }
}
