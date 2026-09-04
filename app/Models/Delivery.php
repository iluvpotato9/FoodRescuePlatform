<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'reservation_id',
        'pickup_schedule_id',
        'delivery_schedule_id',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function pickupSchedule(): BelongsTo
    {
        return $this->belongsTo(PickupSchedule::class);
    }

    public function deliverySchedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class);
    }
}
