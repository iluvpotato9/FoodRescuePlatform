<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\FoodRequest\State;

use App\Models\FoodRequest;

interface RequestState
{
    public function approve(FoodRequest $request): FoodRequest;

    public function reject(FoodRequest $request): FoodRequest;

    public function reserve(FoodRequest $request): FoodRequest;

    public function complete(FoodRequest $request): FoodRequest;

    public function release(FoodRequest $request): FoodRequest;

    public function cancel(FoodRequest $request): FoodRequest;

    public function getName(): string;
}
