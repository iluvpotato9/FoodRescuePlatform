<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Donation\Strategy;

use InvalidArgumentException;

class DonationSortStrategyResolver
{
    public static function resolve(string $sortBy): DonationSortStrategy
    {
        return match ($sortBy) {
            'expiry_date' => new ExpiryDateSortStrategy,
            'newest' => new NewestSortStrategy,
            'category' => new CategorySortStrategy,
            'location' => new LocationSortStrategy,
            default => throw new InvalidArgumentException("Invalid sort strategy: {$sortBy}"),
        };
    }
}
