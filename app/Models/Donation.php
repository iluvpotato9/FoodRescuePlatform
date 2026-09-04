<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donation extends Model
{
    protected $fillable = [
        'donor_id',
        'title',
        'description',
        'category_id',
        'quantity',
        'unit',
        'expiry_date',
        'pickup_address',
        'pickup_time',
        'status',
        'is_active',
        'image_path',
        'collection_location',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'pickup_time' => 'datetime',
            'is_active' => 'boolean',
            'quantity' => 'integer',
        ];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DonationItem::class);
    }

    public function donationItems(): HasMany
    {
        return $this->hasMany(DonationItem::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function foodRequests(): HasMany
    {
        return $this->hasMany(FoodRequest::class);
    }

    public function pickupSchedules(): HasMany
    {
        return $this->hasMany(PickupSchedule::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->endOfDay()->isPast();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available'
            && $this->is_active
            && ! $this->isExpired()
            && $this->availableQuantity() > 0;
    }

    public function availableQuantity(): float
    {
        // 1. Total quantity locked in approved/fulfilled reservations
        $reserved = (float) $this->reservations()->sum('quantity_reserved');

        // 2. Total quantity held in active pending requests awaiting admin review
        $pending = (float) $this->foodRequests()
            ->where('status', 'pending')
            ->sum('quantity_requested');

        return max(0, (float) $this->quantity - ($reserved + $pending));
    }
}
