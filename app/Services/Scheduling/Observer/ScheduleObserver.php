<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Scheduling\Observer;

interface ScheduleObserver
{
    public function onStatusChanged(string $entityType, int $entityId, string $oldStatus, string $newStatus, array $context = []): void;
}
