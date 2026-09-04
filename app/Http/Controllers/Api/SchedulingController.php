<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\PickupSchedule;
use App\Services\Scheduling\SchedulingService;
use App\Services\Scheduling\ApprovedRequestsWebServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SchedulingController extends Controller
{
    public function __construct(
        private SchedulingService $schedulingService,
        private ?ApprovedRequestsWebServiceClient $approvedRequestsClient = null,
    ) {
        $this->approvedRequestsClient ??= new ApprovedRequestsWebServiceClient;
    }

    public function storePickup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donation_id' => 'required|exists:donations,id',
            'driver_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'driver'),
            ],
            'scheduled_time' => 'required|date|after:now',
            'notes' => 'nullable|string|max:500',
        ]);

        $pickup = $this->schedulingService->createPickup($validated);

        return response()->json([
            'message' => 'Pickup scheduled.',
            'pickup' => $pickup,
        ], 201);
    }

    public function showPickup(int $id): JsonResponse
    {
        $pickup = $this->schedulingService->findPickup($id);
        $user = Auth::user();

        if (! $user->isAdmin() && $pickup->driver_id !== $user->id && $pickup->donation->donor_id !== $user->id) {
            abort(403, 'You are not authorized to view this pickup.');
        }

        return response()->json($pickup);
    }

    public function updatePickupStatus(Request $request, PickupSchedule $pickup): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,picked_up,in_transit,delivered,completed,cancelled',
        ]);

        $user = Auth::user();
        if (! $user->isAdmin() && $pickup->driver_id !== $user->id) {
            abort(403, 'Only assigned drivers or admins can update pickup status.');
        }
        if ($user->isDriver() && $pickup->scheduled_time->copy()->startOfDay()->gt(today())) {
            return response()->json([
                'message' => 'Status updates open on '.$pickup->scheduled_time->format('M j, Y').'.',
            ], 422);
        }

        $updated = $this->schedulingService->updatePickupStatus($pickup, $validated['status']);

        return response()->json([
            'message' => 'Pickup status updated.',
            'pickup' => $updated,
        ]);
    }

    public function storeDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'pickup_schedule_id' => 'nullable|exists:pickup_schedules,id',
            'driver_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'driver'),
            ],
            'notes' => 'nullable|string|max:500',
        ]);

        $delivery = $this->schedulingService->createDelivery($validated);

        return response()->json([
            'message' => 'Delivery scheduled.',
            'delivery' => $delivery,
        ], 201);
    }

    public function showDelivery(int $id): JsonResponse
    {
        $delivery = $this->schedulingService->findDelivery($id);
        $user = Auth::user();
        $isBeneficiary = $delivery->reservation?->foodRequest?->user_id === $user->id;
        $isDriver = $delivery->deliverySchedule?->driver_id === $user->id;
        $isDonor = $delivery->reservation?->donation?->donor_id === $user->id;

        if (! $user->isAdmin() && ! $isBeneficiary && ! $isDriver && ! $isDonor) {
            abort(403, 'You are not authorized to view this delivery.');
        }

        return response()->json($delivery);
    }

    public function updateDeliveryStatus(Request $request, Delivery $delivery): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,picked_up,in_transit,delivered,completed,cancelled',
        ]);

        $user = Auth::user();
        $driverId = $delivery->deliverySchedule?->driver_id;
        if (! $user->isAdmin() && $driverId !== $user->id) {
            abort(403, 'Only assigned drivers or admins can update delivery status.');
        }
        if ($user->isDriver() && $delivery->deliverySchedule->scheduled_time->copy()->startOfDay()->gt(today())) {
            return response()->json([
                'message' => 'Status updates open on '.$delivery->deliverySchedule->scheduled_time->format('M j, Y').'.',
            ], 422);
        }

        $updated = $this->schedulingService->updateDeliveryStatus($delivery, $validated['status']);

        return response()->json([
            'message' => 'Delivery status updated.',
            'delivery' => $updated,
        ]);
    }

    public function driverDashboard(): JsonResponse
    {
        $dashboard = $this->schedulingService->driverDashboard(Auth::id());

        return response()->json($dashboard);
    }

    public function webServiceDeliveryStatus(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'requestID' => ['required', 'string', 'max:100'],
            'delivery_id' => ['nullable', 'integer', 'exists:deliveries,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'F',
                'requestID' => $request->input('requestID'),
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $requestID = $request->input('requestID');

        try {
            if ($request->filled('delivery_id')) {
                $delivery = Delivery::with(['reservation.foodRequest.user', 'deliverySchedule.driver', 'pickupSchedule'])
                    ->findOrFail($request->input('delivery_id'));
            } elseif ($request->filled('reservation_id')) {
                $delivery = Delivery::with(['reservation.foodRequest.user', 'deliverySchedule.driver', 'pickupSchedule'])
                    ->where('reservation_id', $request->input('reservation_id'))
                    ->firstOrFail();
            } else {
                return response()->json([
                    'status' => 'F',
                    'requestID' => $requestID,
                    'timeStamp' => now()->format('Y-m-d H:i:s'),
                    'message' => 'Either delivery_id or reservation_id is required.',
                ], 422);
            }

            return response()->json([
                'status' => 'S',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'data' => [
                    'delivery_id' => $delivery->id,
                    'reservation_id' => $delivery->reservation_id,
                    'status' => $delivery->deliverySchedule?->status ?? 'pending',
                    'scheduled_time' => $delivery->deliverySchedule?->scheduled_time?->toDateTimeString(),
                    'driver_name' => $delivery->deliverySchedule?->driver?->name,
                    'driver_phone' => $delivery->deliverySchedule?->driver?->phone,
                    'notes' => $delivery->deliverySchedule?->notes,
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to retrieve delivery status.',
            ], 500);
        }
    }

    public function webServiceFetchApprovedRequests(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);

        try {
            $payload = $this->approvedRequestsClient->getApprovedRequests($limit);

            return response()->json($payload);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to consume the food request web service.',
            ], 502);
        }
    }
}
