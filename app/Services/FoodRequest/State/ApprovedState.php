<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\FoodRequest\State;

use App\Models\FoodRequest;

class ApprovedState extends AbstractRequestState
{
    public function approve(FoodRequest $request): FoodRequest
    {
        $this->invalidTransition('approve');
    }

    public function reject(FoodRequest $request): FoodRequest
    {
        return $this->transition($request, 'rejected');
    }

    public function reserve(FoodRequest $request): FoodRequest
    {
        return $this->transition($request, 'reserved');
    }

    public function complete(FoodRequest $request): FoodRequest
    {
        $this->invalidTransition('complete');
    }

    public function cancel(FoodRequest $request): FoodRequest
    {
        return $this->transition($request, 'cancelled');
    }

    public function getName(): string
    {
        return 'approved';
    }
}
