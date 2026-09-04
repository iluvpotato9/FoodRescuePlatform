<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Scheduling\Observer;

use App\Models\PickupSchedule;
use App\Notifications\ScheduleStatusNotification;
use Illuminate\Support\Facades\Log;

class DonorNotificationObserver implements ScheduleObserver
{
    public function onStatusChanged(string $entityType, int $entityId, string $oldStatus, string $newStatus, array $context = []): void
    {
        if ($entityType !== 'pickup') {
            return;
        }

        $pickup = PickupSchedule::with('donation.donor')->find($entityId);
        if (! $pickup?->donation?->donor) {
            return;
        }

        $donor = $pickup->donation->donor;
        $message = "Pickup status for donation '{$pickup->donation->title}' changed from {$oldStatus} to {$newStatus}.";

        Log::info('Donor notification', [
            'donor_id' => $donor->id,
            'email' => $donor->email,
            'message' => $message,
        ]);

        $donor->notify(new ScheduleStatusNotification(
            'Pickup status updated',
            $message,
            'pickup',
            $pickup->id,
            $newStatus,
        ));
    }
}
