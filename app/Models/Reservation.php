<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = [
        'request_id',
        'donation_id',
        'quantity_reserved',
        'reservation_date',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'quantity_reserved' => 'integer',
        ];
    }

    public function foodRequest(): BelongsTo
    {
        return $this->belongsTo(FoodRequest::class, 'request_id');
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function deliverySchedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class);
    }
}
