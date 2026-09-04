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

class BeneficiaryNotificationObserver implements ScheduleObserver
{
    public function onStatusChanged(string $entityType, int $entityId, string $oldStatus, string $newStatus, array $context = []): void
    {
        if ($entityType !== 'delivery') {
            return;
        }

        $delivery = DeliverySchedule::with('reservation.foodRequest.user')->find($entityId);
        $beneficiary = $delivery?->reservation?->foodRequest?->user;

        if (! $beneficiary) {
            return;
        }

        $foodRequest = $delivery->reservation->foodRequest;
        $foodName = $delivery->reservation->donation?->title ?? 'your food';
        $message = "Request #{$foodRequest->id} for {$foodName}: delivery changed from {$oldStatus} to {$newStatus}.";

        Log::info('Beneficiary notification', [
            'beneficiary_id' => $beneficiary->id,
            'email' => $beneficiary->email,
            'message' => $message,
        ]);

        $beneficiary->notify(new ScheduleStatusNotification(
            "Request #{$foodRequest->id} delivery updated",
            $message,
            'delivery',
            $entityId,
            $newStatus,
            $foodRequest->id,
        ));
    }
}
