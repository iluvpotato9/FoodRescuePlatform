<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Scheduling\Observer;

use App\Models\DeliverySchedule;
use App\Notifications\ScheduleStatusNotification;
use Illuminate\Support\Facades\Log;

class DriverNotificationObserver implements ScheduleObserver
{
    public function onStatusChanged(string $entityType, int $entityId, string $oldStatus, string $newStatus, array $context = []): void
    {
        $schedule = match ($entityType) {
            'pickup' => \App\Models\PickupSchedule::with('driver')->find($entityId),
            'delivery' => DeliverySchedule::with('driver')->find($entityId),
            default => null,
        };

        if (! $schedule?->driver) {
            return;
        }

        $message = ucfirst($entityType)." schedule #{$entityId} status changed from {$oldStatus} to {$newStatus}.";

        Log::info('Driver notification', [
            'driver_id' => $schedule->driver->id,
            'email' => $schedule->driver->email,
            'message' => $message,
        ]);

        $schedule->driver->notify(new ScheduleStatusNotification(
            ucfirst($entityType).' status updated',
            $message,
            $entityType,
            $entityId,
            $newStatus,
        ));
    }
}
