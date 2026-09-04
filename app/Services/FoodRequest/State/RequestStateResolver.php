<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\FoodRequest\State;

use InvalidArgumentException;

class RequestStateResolver
{
    public static function resolve(string $status): RequestState
    {
        return match ($status) {
            'pending' => new PendingState,
            'approved' => new ApprovedState,
            'reserved' => new ReservedState,
            'completed' => new CompletedState,
            'rejected' => new RejectedState,
            'cancelled' => new CancelledState,
            default => throw new InvalidArgumentException("Invalid request status: {$status}"),
        };
    }
}
