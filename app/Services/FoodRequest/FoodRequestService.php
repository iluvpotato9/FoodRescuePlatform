<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\FoodRequest;

use App\Models\Donation;
use App\Models\FoodRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\AdminActionRequiredNotification;
use App\Notifications\FoodReadyNotification;
use App\Notifications\FoodRequestStatusNotification;
use App\Services\FoodRequest\State\RequestStateResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FoodRequestService
{
    public function create(array $data): FoodRequest
    {
        $donation = Donation::findOrFail($data['donation_id']);
        $quantity = (float) $data['quantity_requested'];

        if ($quantity < 1 || floor($quantity) !== $quantity) {
            throw new InvalidArgumentException('Quantity must be a positive whole number.');
        }

        if ($donation->isExpired()) {
            throw new InvalidArgumentException('This food item has expired and can no longer be requested.');
        }

        $available = (int) $donation->availableQuantity();
        if ($available <= 0) {
            throw new InvalidArgumentException('This food item is fully allocated and no longer available.');
        }

        if ($quantity > $available) {
            throw new InvalidArgumentException("Only {$available} {$donation->unit} currently available for this food item.");
        }

        return FoodRequest::create([
            'user_id' => $data['user_id'],
            'request_date' => $data['request_date'] ?? now()->toDateString(),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'fulfillment_method' => $data['fulfillment_method'] ?? 'food_bank_pickup',
            'delivery_address' => ($data['fulfillment_method'] ?? 'food_bank_pickup') === 'home_delivery'
                ? ($data['delivery_address'] ?? null)
                : null,
            'donation_id' => $donation->id,
            'quantity_requested' => $quantity,
        ]);
    }

    public function find(int $id): FoodRequest
    {
        return FoodRequest::with(['user', 'donation', 'reservations.donation'])->findOrFail($id);
    }

    public function listForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return FoodRequest::with(['donation', 'reservations.donation'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function listApproved(): \Illuminate\Database\Eloquent\Collection
    {
        return FoodRequest::with(['user', 'reservations'])
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();
    }

    public function updateStatus(FoodRequest $request, string $action): FoodRequest
    {
        $state = RequestStateResolver::resolve($request->status);

        $updated = match ($action) {
            'approve' => $state->approve($request),
            'reject' => $state->reject($request),
            'reserve' => $state->reserve($request),
            'complete' => $state->complete($request),
            'release' => $state->release($request),
            'cancel' => $state->cancel($request),
            default => throw new InvalidArgumentException("Invalid action: {$action}"),
        };

        if ($action === 'reject') {
            $updated->loadMissing(['user', 'donation']);
            $updated->user->notify(new FoodRequestStatusNotification($updated, 'rejected'));
        }

        return $updated;
    }

    public function approveRequest(FoodRequest $request): FoodRequest
    {
        return DB::transaction(function () use ($request) {
            $request = FoodRequest::lockForUpdate()
                ->with(['donation', 'user'])
                ->findOrFail($request->id);

            if ($request->status !== 'pending') {
                throw new InvalidArgumentException('Only pending requests can be approved.');
            }

            $donation = Donation::lockForUpdate()
                ->findOrFail($request->donation_id);

            // Do NOT use isAvailable() here.
            // This request has already reserved/held its quantity while pending.
            if (! $donation->is_active || $donation->isExpired()) {
                throw new InvalidArgumentException(
                    'This food item is no longer available.'
                );
            }

            // availableQuantity() already subtracts ALL pending requests,
            // including the request currently being approved.
            // Add this request's own pending quantity back for the approval check.
            $currentAvailable = (float) $donation->availableQuantity();
            $requestQuantity = (float) $request->quantity_requested;
            $effectiveAvailable = $currentAvailable + $requestQuantity;

            if ($requestQuantity > $effectiveAvailable) {
                throw new InvalidArgumentException(
                    'There is not enough food left to approve this request.'
                );
            }

            // Change request from pending -> approved
            $this->updateStatus($request, 'approve');

            // Convert the pending allocation into an actual reservation
            $reservation = Reservation::create([
                'request_id' => $request->id,
                'donation_id' => $donation->id,
                'quantity_reserved' => $requestQuantity,
                'reservation_date' => now()->toDateString(),
            ]);

            // If there is no quantity left for NEW requests,
            // mark the donation as reserved.
            if ($donation->availableQuantity() <= 0) {
                $donation->update(['status' => 'reserved']);
            }

            if ($donation->collection_location === 'food_bank') {
                $this->markRequestReady($request->fresh());
            } else {
                User::where('role', 'admin')
                    ->each(function (User $admin) use ($reservation, $donation, $request) {
                        $admin->notify(new AdminActionRequiredNotification(
                            'Donor pickup required',
                            "{$donation->title} was approved for {$request->user->name}. Schedule a driver to bring it to the food bank.",
                            'schedule_pickup',
                            $reservation->id,
                        ));
                    });
            }

            return $request->fresh()->load([
                'donation',
                'reservations',
            ]);
        });
    }

    public function markDonationReady(Donation $donation): void
    {
        $donation->reservations()
            ->with('foodRequest')
            ->get()
            ->each(function (Reservation $reservation) {
                if ($reservation->foodRequest?->status === 'approved') {
                    $this->markRequestReady($reservation->foodRequest);
                }
            });
    }

    public function scheduleFulfillment(FoodRequest $request, string $scheduledAt): FoodRequest
    {
        if ($request->status !== 'reserved' || ! $request->ready_at || ! $request->collection_deadline) {
            throw new InvalidArgumentException('This food is not ready for scheduling yet.');
        }

        $selected = Carbon::parse($scheduledAt);
        $earliest = $request->ready_at->copy()->addDay()->startOfDay();

        if ($selected->lt($earliest) || $selected->gt($request->collection_deadline)) {
            throw new InvalidArgumentException(
                "Choose a time between {$earliest->format('M j, Y')} and {$request->collection_deadline->format('M j, Y')}."
            );
        }

        if (! in_array($selected->dayOfWeekIso, config('foodrescue.operating_days'), true)) {
            throw new InvalidArgumentException('Choose a Monday to Saturday time during food bank operating hours.');
        }

        $opensAt = $selected->copy()->setTimeFromTimeString(config('foodrescue.opens_at'));
        $closesAt = $selected->copy()->setTimeFromTimeString(config('foodrescue.closes_at'));
        if ($selected->lt($opensAt) || $selected->gt($closesAt)) {
            throw new InvalidArgumentException('Choose a time between 9:00 AM and 6:00 PM.');
        }

        $request->update(['fulfillment_scheduled_at' => $selected]);

        return $request->fresh();
    }

    private function markRequestReady(FoodRequest $request): FoodRequest
    {
        if ($request->status === 'approved') {
            $this->updateStatus($request, 'reserve');
        }

        $readyAt = now();
        $threeDayDeadline = $readyAt->copy()->addDays(3)->endOfDay();
        $expiryDeadline = Carbon::parse($request->donation()->value('expiry_date'))->endOfDay();
        $request->update([
            'ready_at' => $readyAt,
            'collection_deadline' => $expiryDeadline->lt($threeDayDeadline) ? $expiryDeadline : $threeDayDeadline,
        ]);

        $request = $request->fresh()->load('donation');
        $request->user->notify(new FoodReadyNotification($request));

        return $request;
    }

    public function createReservation(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $quantity = (float) $data['quantity_reserved'];
            if ($quantity < 1 || floor($quantity) !== $quantity) {
                throw new InvalidArgumentException('Quantity must be a positive whole number.');
            }

            $donation = Donation::lockForUpdate()->findOrFail($data['donation_id']);
            $request = FoodRequest::findOrFail($data['request_id']);

            if ($request->user_id !== $data['user_id'] && ! auth()->user()?->isAdmin()) {
                throw new InvalidArgumentException('You can only create reservations for your own requests.');
            }

            if (! in_array($request->status, ['approved', 'reserved'])) {
                throw new InvalidArgumentException('Request must be approved before reserving.');
            }

            if (! $donation->isAvailable()) {
                throw new InvalidArgumentException('Donation is not available.');
            }

            $availableQty = $donation->availableQuantity();
            if ($data['quantity_reserved'] > $availableQty) {
                throw new InvalidArgumentException("Insufficient quantity. Available: {$availableQty}");
            }

            $existing = Reservation::where('donation_id', $data['donation_id'])
                ->where('request_id', $data['request_id'])
                ->exists();

            if ($existing) {
                throw new InvalidArgumentException('A reservation already exists for this request and donation.');
            }

            $reservation = Reservation::create([
                'request_id' => $data['request_id'],
                'donation_id' => $data['donation_id'],
                'quantity_reserved' => $data['quantity_reserved'],
                'reservation_date' => $data['reservation_date'] ?? now()->toDateString(),
            ]);

            if ($request->status === 'approved') {
                $this->updateStatus($request, 'reserve');
            }

            if ($donation->availableQuantity() <= 0) {
                $donation->update(['status' => 'reserved']);
            }

            if ($donation->collection_location === 'donor') {
                $beneficiaryName = $request->user()->value('name');
                User::where('role', 'admin')->each(function (User $admin) use ($reservation, $donation, $beneficiaryName) {
                    $admin->notify(new AdminActionRequiredNotification(
                        'Donor pickup required',
                        "{$donation->title} was reserved for {$beneficiaryName} and is still with the donor. Please schedule a pickup to the food bank.",
                        'schedule_pickup',
                        $reservation->id,
                    ));
                });
            }

            return $reservation->load(['donation', 'foodRequest']);
        });
    }

    public function deleteReservation(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $donation = $reservation->donation;
            $foodRequest = $reservation->foodRequest;
            $reservation->delete();

            if ($donation && $donation->status === 'reserved' && $donation->availableQuantity() > 0) {
                $donation->update(['status' => 'available']);
            }

            if ($foodRequest?->status === 'reserved' && ! $foodRequest->reservations()->exists()) {
                $this->updateStatus($foodRequest, 'release');
            }
        });
    }
}
