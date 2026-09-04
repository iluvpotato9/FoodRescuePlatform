<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NotCommonPassword;
use App\Rules\PhoneNumber;
use App\Services\User\AuthService;
use App\Services\User\DeliveryStatusWebServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private ?DeliveryStatusWebServiceClient $deliveryStatusClient = null,
    ) {
        $this->deliveryStatusClient ??= new DeliveryStatusWebServiceClient;
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:254', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'max:128', Password::min(15), new NotCommonPassword],
            'role' => ['required', 'in:beneficiary,donor,driver'],
            'phone' => ['nullable', 'string', 'max:25', new PhoneNumber],
            'address' => 'nullable|string|max:500',
            'household_size' => ['required_if:role,beneficiary', 'nullable', 'integer', 'min:1', 'max:30'],
            'dietary_needs' => 'nullable|string|max:500',
            'income_level' => 'nullable|string|max:50',
            'emergency_contact' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['email'] = Str::lower(trim($validated['email']));
        $user = $this->authService->register($validated);
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $user = $this->authService->login($credentials);

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->load('beneficiaryProfile'),
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorizeUserAccess($user);

        return response()->json($user->load('beneficiaryProfile'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeUserAccess($user);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => ['sometimes', 'confirmed', 'max:128', Password::min(15), new NotCommonPassword],
            'phone' => ['nullable', 'string', 'max:25', new PhoneNumber],
            'address' => 'nullable|string|max:500',
            'household_size' => ['nullable', 'integer', 'min:1', 'max:30'],
            'dietary_needs' => 'nullable|string|max:500',
            'income_level' => 'nullable|string|max:50',
            'emergency_contact' => ['nullable', 'string', 'max:100'],
        ]);

        $updated = $this->authService->updateProfile($user, $validated);

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $updated,
        ]);
    }

    public function getRole(User $user): JsonResponse
    {
        $this->authorizeUserAccess($user);

        return response()->json(['role' => $user->role]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:beneficiary,donor,driver,admin',
        ]);

        $updated = $this->authService->updateRole($user, $request->role);

        return response()->json([
            'message' => 'Role updated.',
            'user' => $updated,
        ]);
    }

    private function authorizeUserAccess(User $user): void
    {
        $authUser = Auth::user();

        if (! $authUser->isAdmin() && $authUser->id !== $user->id) {
            abort(403, 'You can only access your own profile.');
        }
    }

    public function webServiceUserProfile(Request $request, int $id): JsonResponse
    {
        $validator = validator($request->all(), [
            'requestID' => ['required', 'string', 'max:100'],
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
            $user = User::with('beneficiaryProfile')->find($id);

            if (! $user) {
                return response()->json([
                    'status' => 'F',
                    'requestID' => $requestID,
                    'timeStamp' => now()->format('Y-m-d H:i:s'),
                    'message' => "User with ID {$id} not found.",
                ], 404);
            }

            return response()->json([
                'status' => 'S',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'household_size' => $user->beneficiaryProfile?->household_size,
                    'dietary_needs' => $user->beneficiaryProfile?->dietary_needs,
                    'is_verified' => true,
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'requestID' => $requestID,
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to retrieve user profile.',
            ], 500);
        }
    }

    public function webServiceCheckDelivery(Request $request): JsonResponse
    {
        $deliveryId = $request->input('delivery_id') ? (int) $request->input('delivery_id') : null;
        $reservationId = $request->input('reservation_id') ? (int) $request->input('reservation_id') : null;

        try {
            $payload = $this->deliveryStatusClient->getDeliveryStatus($deliveryId, $reservationId);

            return response()->json($payload);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'timeStamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Unable to consume the delivery web service.',
            ], 502);
        }
    }
}
