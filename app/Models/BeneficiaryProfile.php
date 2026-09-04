<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryProfile extends Model
{
    protected $fillable = [
        'user_id',
        'household_size',
        'dietary_needs',
        'income_level',
        'emergency_contact',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
