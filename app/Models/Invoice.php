<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'invoice_number',
        'status',
        'total_amount',
        'insurance_coverage',
        'total_paid',
        'due_date'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'insurance_coverage' => 'decimal:2',
        'patient_responsibility' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'due_date' => 'datetime'
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function confirmedPayments()
    {
        return $this->payments()->where('status', 'confirmed');
    }

    public function getRemainingBalanceAttribute()
    {
        return $this->patient_responsibility - $this->total_paid;
    }

    public function updateStatus()
    {
        $totalConfirmed = $this->confirmedPayments()->sum('amount');
        $this->total_paid = $totalConfirmed;
        
        if ($totalConfirmed >= $this->patient_responsibility) {
            $this->status = 'paid';
        } elseif ($totalConfirmed > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }
}
