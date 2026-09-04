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

class FoodRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_date',
        'status',
        'notes',
        'fulfillment_method',
        'delivery_address',
        'donation_id',
        'quantity_requested',
        'ready_at',
        'collection_deadline',
        'fulfillment_scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'quantity_requested' => 'integer',
            'ready_at' => 'datetime',
            'collection_deadline' => 'datetime',
            'fulfillment_scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class, 'request_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'request_id');
    }
}
