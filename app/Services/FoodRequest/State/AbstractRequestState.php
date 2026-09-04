<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\FoodRequest\State;

use App\Models\FoodRequest;
use InvalidArgumentException;

abstract class AbstractRequestState implements RequestState
{
    protected function transition(FoodRequest $request, string $newStatus): FoodRequest
    {
        $request->update(['status' => $newStatus]);

        return $request->fresh();
    }

    protected function invalidTransition(string $action): void
    {
        throw new InvalidArgumentException("Cannot {$action} a request in {$this->getName()} state.");
    }

    public function release(FoodRequest $request): FoodRequest
    {
        $this->invalidTransition('release');
    }
}
