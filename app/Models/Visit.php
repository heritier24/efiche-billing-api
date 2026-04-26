<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_type',
        'status'
    ];

    protected $casts = [
        'completed_at' => 'datetime'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
