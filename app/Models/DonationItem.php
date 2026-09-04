<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationItem extends Model
{
    protected $fillable = [
        'donation_id',
        'item_name',
        'quantity',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
