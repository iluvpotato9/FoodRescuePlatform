<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Scheduling\Observer;

class ScheduleSubject
{
    /** @var ScheduleObserver[] */
    private array $observers = [];

    public function attach(ScheduleObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notifyStatusChange(string $entityType, int $entityId, string $oldStatus, string $newStatus, array $context = []): void
    {
        foreach ($this->observers as $observer) {
            $observer->onStatusChanged($entityType, $entityId, $oldStatus, $newStatus, $context);
        }
    }
}
