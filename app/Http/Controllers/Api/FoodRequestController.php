<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodRequest;
use App\Models\Reservation;
use App\Services\FoodRequest\FoodRequestService;
use App\Services\FoodRequest\DonationWebServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class FoodRequestController extends Controller
{
    public function __construct(
        private FoodRequestService $foodRequestService,
        private DonationWebServiceClient $donationWebServiceClient,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donation_id' => 'required|exists:donations,id',
            'quantity_requested' => 'required|integer|min:1|max:100000',
            'notes' => 'nullable|string|max:1000',
            'fulfillment_method' => ['required', 'in:food_bank_pickup,home_delivery'],
            'delivery_address' => [
                Rule::requiredIf($request->input('fulfillment_method') === 'home_delivery'),
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $validated['user_id'] = Auth::id();
        try {
            $foodRequest = $this->foodRequestService->create($validated);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Food request submitted.',
            'request' => $foodRequest,
        ], 201);
    }

    public function availableDonationsWebService(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_by' => ['nullable', 'in:expiry_date,newest,category,location'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $payload = $this->donationWebServiceClient
                ->getAvailableDonations($filters);

            return response()->json($payload);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to consume the donation web service.',
            ], 502);
        }
    }

    public function webServiceApproved(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'requestID' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            $limit = (int) $request->input('limit', 50);
            $approved = $this->foodRequestService->listApproved();

            $data = $approved->take($limit)->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name,
                    'user_phone' => $item->user?->phone,
                    'donation_id' => $item->donation_id,
                    'donation_title' => $item->donation?->title,
                    'quantity_requested' => $item->quantity_requested,
                    'fulfillment_method' => $item->fulfillment_method,
                    'delivery_address' => $item->delivery_address,
                    'status' => $item->status,
                    'ready_at' => $item->ready_at?->toDateTimeString(),
                    'fulfillment_scheduled_at' => $item->fulfillment_scheduled_at?->toDateTimeString(),
                ];
            })->values();

            return response()->json([
                'status' => 'S',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to retrieve approved food requests.',
            ], 500);
        }
    }
    public function show(int $id): JsonResponse
    {
        $foodRequest = $this->foodRequestService->find($id);
        $this->authorizeRequest($foodRequest);

        return response()->json($foodRequest);
    }

    public function updateStatus(Request $request, FoodRequest $foodRequest): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,reserve,complete,cancel',
        ]);

        if (! Auth::user()->isAdmin() && $validated['action'] === 'cancel') {
            $this->authorizeRequest($foodRequest);
        } elseif (! Auth::user()->isAdmin()) {
            abort(403, 'Only admins can change request status.');
        }

        $updated = $validated['action'] === 'approve'
            ? $this->foodRequestService->approveRequest($foodRequest)
            : $this->foodRequestService->updateStatus($foodRequest, $validated['action']);

        return response()->json([
            'message' => 'Request status updated.',
            'request' => $updated,
        ]);
    }

    public function approved(): JsonResponse
    {
        $requests = $this->foodRequestService->listApproved();

        return response()->json($requests);
    }

    public function history(): JsonResponse
    {
        $requests = $this->foodRequestService->listForUser(Auth::id());

        return response()->json($requests);
    }

    public function schedule(Request $request, FoodRequest $foodRequest): JsonResponse
    {
        $this->authorizeRequest($foodRequest);
        $validated = $request->validate([
            'fulfillment_scheduled_at' => 'required|date',
        ]);

        try {
            $updated = $this->foodRequestService->scheduleFulfillment(
                $foodRequest,
                $validated['fulfillment_scheduled_at']
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Fulfillment time selected.',
            'request' => $updated,
        ]);
    }

    public function storeReservation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:food_requests,id',
            'donation_id' => 'required|exists:donations,id',
            'quantity_reserved' => 'required|integer|min:1|max:100000',
        ]);

        $validated['user_id'] = Auth::id();

        $reservation = $this->foodRequestService->createReservation($validated);

        return response()->json([
            'message' => 'Reservation created.',
            'reservation' => $reservation,
        ], 201);
    }

    public function destroyReservation(Reservation $reservation): JsonResponse
    {
        $foodRequest = $reservation->foodRequest;
        if (! Auth::user()->isAdmin() && $foodRequest->user_id !== Auth::id()) {
            abort(403, 'You can only delete your own reservations.');
        }

        $this->foodRequestService->deleteReservation($reservation);

        return response()->json(['message' => 'Reservation deleted.']);
    }

    private function authorizeRequest(FoodRequest $foodRequest): void
    {
        if (! Auth::user()->isAdmin() && $foodRequest->user_id !== Auth::id()) {
            abort(403, 'You can only access your own requests.');
        }
    }
}
