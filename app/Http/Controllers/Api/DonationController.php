<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\Donation\DonationService;
use App\Services\Donation\UserProfileWebServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    public function __construct(
        private DonationService $donationService,
        private ?UserProfileWebServiceClient $userProfileClient = null,
    ) {
        $this->userProfileClient ??= new UserProfileWebServiceClient;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:available,reserved,picked_up,delivered,expired'],
            'sort_by' => ['nullable', 'in:expiry_date,newest,category,location'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (Auth::user()?->isDonor()) {
            $filters['donor_id'] = Auth::id();
        }

        $donations = $this->donationService->list($filters);

        return response()->json($donations);
    }

    public function available(Request $request): JsonResponse

    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_by' => ['nullable', 'in:expiry_date,newest,category,location'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $donations = $this->donationService->listAvailable($filters);

        return response()->json($donations);
    }

    public function webServiceAvailable(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'requestID' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_by' => ['nullable', 'in:expiry_date,newest,category,location'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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

        $validated = $validator->validated();
        $requestID = $validated['requestID'];

        unset($validated['requestID']);

        try {
            $donations = $this->donationService->listAvailable($validated);

            return response()->json([
                'status' => 'S',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'data' => $donations,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to retrieve available donations.',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $donation = $this->donationService->find($id);

        return response()->json($donation);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:categories,id',
            'quantity' => 'required|integer|min:1|max:100000',
            'unit' => ['required', 'in:kg,g,items,boxes,trays,litres'],
            'expiry_date' => 'required|date|after:today',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:100000',
            'items.*.unit' => ['required_with:items', 'in:kg,g,items,boxes,trays,litres'],
        ]);

        $validated['donor_id'] = Auth::id();
        $validated['status'] = 'available';
        $validated['is_active'] = true;
        $validated['pickup_address'] = config('foodrescue.food_bank_address');
        $validated['pickup_time'] = null;
        $validated['collection_location'] = 'food_bank';

        $donation = $this->donationService->create(
            $validated,
            $request->file('image')
        );

        return response()->json([
            'message' => 'Donation created.',
            'donation' => $donation,
        ], 201);
    }

    public function update(Request $request, Donation $donation): JsonResponse
    {
        $this->authorizeDonation($donation);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'min:3', 'max:150'],
            'description' => 'nullable|string|max:2000',
            'category_id' => 'sometimes|exists:categories,id',
            'quantity' => 'sometimes|integer|min:1|max:100000',
            'unit' => ['sometimes', 'in:kg,g,items,boxes,trays,litres'],
            'expiry_date' => 'sometimes|date|after:today',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:100000',
            'items.*.unit' => ['required_with:items', 'in:kg,g,items,boxes,trays,litres'],
        ]);

    if (array_key_exists('quantity', $validated)) {
        $reservedQuantity = (int) $donation->reservations()->sum('quantity_reserved');

        if ((int) $validated['quantity'] < $reservedQuantity) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'quantity' => [
                        "Quantity cannot be lower than the {$reservedQuantity} already approved.",
                    ],
                ],
            ], 422);
        }
    }
        $updated = $this->donationService->update(
            $donation,
            $validated,
            $request->file('image')
        );

        return response()->json([
            'message' => 'Donation updated.',
            'donation' => $updated,
        ]);
    }

    public function destroy(Donation $donation): JsonResponse
    {
        $this->authorizeDonation($donation);

        if ($donation->reservations()->exists()) {
            return response()->json([
                'message' => 'A donation with reservations cannot be deleted.',
            ], 422);
        }

        $this->donationService->delete($donation);

        return response()->json([
            'message' => 'Donation deleted.',
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $donation = $this->donationService->find($id);
        $this->authorizeDonation($donation);

        $validated = $request->validate([
            'status' => 'required|in:available,reserved,picked_up,delivered,expired',
        ]);

        $updated = $this->donationService->updateStatus($donation, $validated['status']);

        return response()->json([
            'message' => 'Donation status updated.',
            'donation' => $updated,
        ]);
    }


    private function authorizeDonation(Donation $donation): void
    {
        $user = Auth::user();
        if (! $user->isAdmin() && $donation->donor_id !== $user->id) {
            abort(403, 'You can only manage your own donations.');
        }
    }

    public function webServiceVerifyDonor(Request $request): JsonResponse
    {
        $donorId = (int) $request->input('donor_id', Auth::id() ?? 0);

        if ($donorId <= 0) {
            return response()->json([
                'status' => 'F',
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'A valid donor_id is required.',
            ], 422);
        }

        try {
            $payload = $this->userProfileClient->getUserProfile($donorId);

            return response()->json($payload);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to consume the user profile web service.',
            ], 502);
        }
    }
}
