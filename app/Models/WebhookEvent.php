<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'source',
        'event_type',
        'payload',
        'status',
        'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime'
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function markAsProcessed()
    {
        $this->status = 'processed';
        $this->processed_at = now();
        $this->save();
    }
}
