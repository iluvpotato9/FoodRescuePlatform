<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function beneficiaryProfile(): HasOne
    {
        return $this->hasOne(BeneficiaryProfile::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }

    public function foodRequests(): HasMany
    {
        return $this->hasMany(FoodRequest::class);
    }

    public function pickupSchedules(): HasMany
    {
        return $this->hasMany(PickupSchedule::class, 'driver_id');
    }

    public function deliverySchedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class, 'driver_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBeneficiary(): bool
    {
        return $this->role === 'beneficiary';
    }

    public function isDonor(): bool
    {
        return $this->role === 'donor';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }
}
