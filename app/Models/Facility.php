<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function facilityInsurances()
    {
        return $this->hasMany(FacilityInsurance::class);
    }

    public function insurances()
    {
        return $this->belongsToMany(Insurance::class, 'facility_insurances')
            ->withPivot(['coverage_percentage', 'max_claim_amount', 'requires_preauth', 'is_active'])
            ->wherePivot('is_active', true);
    }
}
