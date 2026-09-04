<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Donation\Strategy;

use Illuminate\Database\Eloquent\Builder;

class ExpiryDateSortStrategy implements DonationSortStrategy
{
    public function sort(Builder $query): Builder
    {
        return $query->orderBy('expiry_date', 'asc');
    }
}
