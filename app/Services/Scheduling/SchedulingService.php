<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Scheduling;

use App\Models\Delivery;
use App\Models\DeliverySchedule;
use App\Models\Donation;
use App\Models\PickupSchedule;
use App\Models\Reservation;
use App\Models\User;
use App\Services\FoodRequest\FoodRequestService;
use App\Services\Scheduling\Observer\BeneficiaryNotificationObserver;
use App\Services\Scheduling\Observer\DonorNotificationObserver;
use App\Services\Scheduling\Observer\DriverNotificationObserver;
use App\Services\Scheduling\Observer\ScheduleSubject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchedulingService
{
    private ScheduleSubject $subject;

    public function __construct(private FoodRequestService $foodRequestService)
    {
        $this->subject = new ScheduleSubject;
        $this->subject->attach(new DonorNotificationObserver);
        $this->subject->attach(new DriverNotificationObserver);
        $this->subject->attach(new BeneficiaryNotificationObserver);
    }

    public function createPickup(array $data): PickupSchedule
    {
        $donation = Donation::findOrFail($data['donation_id']);

        if (! User::whereKey($data['driver_id'])->where('role', 'driver')->exists()) {
            throw ValidationException::withMessages(['driver_id' => 'The selected user is not a volunteer driver.']);
        }

        if (in_array($donation->status, ['expired', 'delivered'])) {
            throw ValidationException::withMessages(['donation_id' => 'This donation can no longer be scheduled for pickup.']);
        }

        if (PickupSchedule::where('donation_id', $donation->id)->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
            throw ValidationException::withMessages(['donation_id' => 'This donation already has an active pickup.']);
        }

        if (! $this->isDriverAvailable($data['driver_id'], Carbon::parse($data['scheduled_time']))) {
            throw ValidationException::withMessages(['driver_id' => 'This driver already has another assignment near that time.']);
        }

        $pickup = PickupSchedule::create([
            'donation_id' => $data['donation_id'],
            'driver_id' => $data['driver_id'],
            'scheduled_time' => $data['scheduled_time'],
            'status' => 'scheduled',
            'notes' => $data['notes'] ?? null,
        ])->load(['donation', 'driver']);

        $this->subject->notifyStatusChange('pickup', $pickup->id, 'created', 'scheduled');

        return $pickup;
    }

    public function findPickup(int $id): PickupSchedule
    {
        return PickupSchedule::with(['donation.donor', 'driver'])->findOrFail($id);
    }

    public function updatePickupStatus(PickupSchedule $pickup, string $status): PickupSchedule
    {
        $allowedTransitions = [
            'scheduled' => ['picked_up', 'completed', 'cancelled'],
            'picked_up' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

    $oldStatus = $pickup->status;

    if (
        ! array_key_exists($oldStatus, $allowedTransitions) ||
        ! in_array($status, $allowedTransitions[$oldStatus], true)
    ) {
        throw ValidationException::withMessages([
            'status' => "Pickup status cannot change from {$oldStatus} to {$status}.",
        ]);
    }

    $pickup->update(['status' => $status]);

        $this->subject->notifyStatusChange('pickup', $pickup->id, $oldStatus, $status);

        if ($status === 'picked_up') {
            $pickup->donation?->update(['status' => 'picked_up']);
        }

        if ($status === 'completed') {
            $donation = $pickup->donation;
            $donation?->update([
                'collection_location' => 'food_bank',
                'status' => $donation->reservations()->exists() ? 'reserved' : 'available',
            ]);
            if ($donation) {
                $this->foodRequestService->markDonationReady($donation->fresh());
            }
        }

        return $pickup->fresh()->load(['donation', 'driver']);
    }

    public function createDelivery(array $data): Delivery
    {
        return DB::transaction(function () use ($data) {
            $reservation = Reservation::with(['donation', 'foodRequest'])->findOrFail($data['reservation_id']);
            $pickup = ! empty($data['pickup_schedule_id'])
                ? PickupSchedule::findOrFail($data['pickup_schedule_id'])
                : null;

            if (! User::whereKey($data['driver_id'])->where('role', 'driver')->exists()) {
                throw ValidationException::withMessages(['driver_id' => 'The selected user is not a volunteer driver.']);
            }

            if ($pickup && $pickup->donation_id !== $reservation->donation_id) {
                throw ValidationException::withMessages(['pickup_schedule_id' => 'The pickup and reservation must refer to the same donation.']);
            }

            if ($reservation->donation->collection_location !== 'food_bank') {
                throw ValidationException::withMessages(['reservation_id' => 'The food is still with the donor. Schedule and complete a donor pickup first.']);
            }

            if ($reservation->foodRequest->fulfillment_method !== 'home_delivery') {
                throw ValidationException::withMessages(['reservation_id' => 'This beneficiary chose food bank collection, so home delivery is not required.']);
            }

            if (Delivery::where('reservation_id', $reservation->id)->exists()) {
                throw ValidationException::withMessages(['reservation_id' => 'This reservation already has a delivery.']);
            }

            if (! $reservation->foodRequest->fulfillment_scheduled_at) {
                throw ValidationException::withMessages(['reservation_id' => 'The beneficiary must choose a delivery time first.']);
            }

            $scheduledTime = $reservation->foodRequest->fulfillment_scheduled_at;
            if (! $this->isDriverAvailable($data['driver_id'], $scheduledTime)) {
                throw ValidationException::withMessages(['driver_id' => 'This driver is unavailable at the beneficiary’s selected time.']);
            }

            $deliverySchedule = DeliverySchedule::create([
                'reservation_id' => $data['reservation_id'],
                'driver_id' => $data['driver_id'],
                'scheduled_time' => $scheduledTime,
                'status' => 'scheduled',
                'notes' => $data['notes'] ?? null,
            ]);

            $delivery = Delivery::create([
                'reservation_id' => $data['reservation_id'],
                'pickup_schedule_id' => $data['pickup_schedule_id'] ?? null,
                'delivery_schedule_id' => $deliverySchedule->id,
            ]);

            $this->subject->notifyStatusChange('delivery', $deliverySchedule->id, 'created', 'scheduled');

            return $delivery->load(['reservation', 'pickupSchedule', 'deliverySchedule']);
        });
    }

    public function findDelivery(int $id): Delivery
    {
        return Delivery::with([
            'reservation.foodRequest.user',
            'pickupSchedule.driver',
            'deliverySchedule.driver',
        ])->findOrFail($id);
    }

    public function updateDeliveryStatus(Delivery $delivery, string $status): Delivery
    {
        $allowedTransitions = [
            'scheduled' => ['picked_up', 'in_transit', 'delivered', 'completed', 'cancelled'],
            'picked_up' => ['in_transit', 'delivered', 'completed', 'cancelled'],
            'in_transit' => ['delivered', 'completed', 'cancelled'],
            'delivered' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        $schedule = $delivery->deliverySchedule;
        $oldStatus = $schedule->status;

        if (
            ! array_key_exists($oldStatus, $allowedTransitions) ||
            ! in_array($status, $allowedTransitions[$oldStatus], true)
        ) {
            throw ValidationException::withMessages([
                'status' => "Delivery status cannot change from {$oldStatus} to {$status}.",
            ]);
        }

        $schedule->update(['status' => $status]);

        $this->subject->notifyStatusChange('delivery', $schedule->id, $oldStatus, $status);

        if ($status === 'delivered' || $status === 'completed') {
            $delivery->reservation?->donation?->update(['status' => 'delivered']);
        }

        $foodRequest = $delivery->reservation?->foodRequest;
        if ($status === 'completed' && $foodRequest?->status === 'reserved') {
            $this->foodRequestService->updateStatus($foodRequest, 'complete');
        }

        return $delivery->fresh()->load(['reservation', 'pickupSchedule', 'deliverySchedule']);
    }

    public function driverDashboard(int $driverId): array
    {
        return [
            'pickups' => PickupSchedule::with('donation')
                ->where('driver_id', $driverId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->orderBy('scheduled_time')
                ->get(),
            'deliveries' => DeliverySchedule::with(['reservation.foodRequest.user', 'reservation.donation', 'delivery'])
                ->where('driver_id', $driverId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->orderBy('scheduled_time')
                ->get(),
        ];
    }

    public function driversWithAvailability(Carbon|string $scheduledAt): Collection
    {
        $scheduledAt = $scheduledAt instanceof Carbon ? $scheduledAt : Carbon::parse($scheduledAt);

        return User::where('role', 'driver')
            ->orderBy('name')
            ->get()
            ->each(function (User $driver) use ($scheduledAt) {
                $driver->setAttribute('is_available', $this->isDriverAvailable($driver->id, $scheduledAt));
            });
    }

    public function isDriverAvailable(int $driverId, Carbon $scheduledAt): bool
    {
        $windowStart = $scheduledAt->copy()->subHours(2);
        $windowEnd = $scheduledAt->copy()->addHours(2);
        $activeStatuses = ['scheduled', 'picked_up', 'in_transit'];

        $pickupConflict = PickupSchedule::where('driver_id', $driverId)
            ->whereIn('status', $activeStatuses)
            ->whereBetween('scheduled_time', [$windowStart, $windowEnd])
            ->exists();

        $deliveryConflict = DeliverySchedule::where('driver_id', $driverId)
            ->whereIn('status', $activeStatuses)
            ->whereBetween('scheduled_time', [$windowStart, $windowEnd])
            ->exists();

        return ! $pickupConflict && ! $deliveryConflict;
    }
}
