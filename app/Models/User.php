<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'facility_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'cashier_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'cashier', 'staff']);
    }

    public function canAccessFacility(int $facilityId): bool
    {
        return $this->isAdmin() || $this->facility_id === $facilityId;
    }
}
