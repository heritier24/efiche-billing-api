<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'payment_method',
        'amount',
        'status',
        'transaction_ref',
        'cashier_id',
        'confirmed_at',
        'webhook_event_id',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'confirmed_at' => 'datetime'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function webhookEvent()
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    public function confirm()
    {
        $this->status = 'confirmed';
        $this->confirmed_at = now();
        $this->save();
        
        $this->invoice->updateStatus();
    }
}
