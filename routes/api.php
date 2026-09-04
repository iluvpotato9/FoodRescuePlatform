<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\FoodRequestController;
use App\Http\Controllers\Api\SchedulingController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('/donations/available', [DonationController::class, 'available']);
Route::get('/donations/{id}', [DonationController::class, 'show'])->whereNumber('id');

// Inter-Module Web Services (IFA-compliant)
// Module 1 (Loo Zi Wei): Exposes approved food requests, consumes available donations from Module 3
Route::get('/webservice/requests/approved', [FoodRequestController::class, 'webServiceApproved']);
Route::get('/webservice/requests/available-donations', [FoodRequestController::class, 'availableDonationsWebService']);

// Module 2 (Loo Zhi Yin): Exposes delivery status, consumes approved requests from Module 1
Route::get('/webservice/deliveries/status', [SchedulingController::class, 'webServiceDeliveryStatus']);
Route::get('/webservice/scheduling/approved-requests', [SchedulingController::class, 'webServiceFetchApprovedRequests']);

// Module 3 (Liang Yun Ci): Exposes available donations, consumes user profile verification from Module 4
Route::get('/webservice/donations/available', [DonationController::class, 'webServiceAvailable']);
Route::get('/webservice/donations/donor-profile', [DonationController::class, 'webServiceVerifyDonor']);

// Module 4 (Syed Raiz): Exposes user profiles, consumes delivery fulfillment status from Module 2
Route::get('/webservice/users/{id}/profile', [AuthController::class, 'webServiceUserProfile'])->whereNumber('id');
Route::get('/webservice/users/delivery-status', [AuthController::class, 'webServiceCheckDelivery']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // User management
    Route::get('/users/{user}', [AuthController::class, 'show']);
    Route::put('/users/{user}', [AuthController::class, 'update']);
    Route::get('/users/{user}/role', [AuthController::class, 'getRole']);
    Route::put('/users/{user}/role', [AuthController::class, 'updateRole'])->middleware('role:admin');

    // Donations
    Route::get('/donations', [DonationController::class, 'index']);
    Route::post('/donations', [DonationController::class, 'store'])->middleware('role:donor,admin');
    Route::put('/donations/{donation}', [DonationController::class, 'update'])->middleware('role:donor,admin');
    Route::delete('/donations/{donation}', [DonationController::class, 'destroy'])->middleware('role:donor,admin');
    Route::put('/donations/{id}/status', [DonationController::class, 'updateStatus'])->whereNumber('id');

    // Food requests & reservations
    Route::post('/requests', [FoodRequestController::class, 'store'])->middleware('role:beneficiary,admin');
    Route::get('/requests/history', [FoodRequestController::class, 'history'])->middleware('role:beneficiary,admin');
    Route::get('/requests/approved', [FoodRequestController::class, 'approved'])->middleware('role:admin,driver');
    Route::put('/requests/{foodRequest}/schedule', [FoodRequestController::class, 'schedule'])->middleware('role:beneficiary');
    Route::get('/requests/{id}', [FoodRequestController::class, 'show'])->whereNumber('id');
    Route::put('/requests/{foodRequest}/status', [FoodRequestController::class, 'updateStatus']);
    Route::post('/reservations', [FoodRequestController::class, 'storeReservation'])->middleware('role:admin');
    Route::delete('/reservations/{reservation}', [FoodRequestController::class, 'destroyReservation']);

    // Pickup & delivery scheduling
    Route::post('/pickups', [SchedulingController::class, 'storePickup'])->middleware('role:admin');
    Route::get('/pickups/{id}', [SchedulingController::class, 'showPickup'])->whereNumber('id');
    Route::put('/pickups/{pickup}/status', [SchedulingController::class, 'updatePickupStatus']);
    Route::post('/deliveries', [SchedulingController::class, 'storeDelivery'])->middleware('role:admin');
    Route::get('/deliveries/{id}', [SchedulingController::class, 'showDelivery'])->whereNumber('id');
    Route::put('/deliveries/{delivery}/status', [SchedulingController::class, 'updateDeliveryStatus']);
    Route::get('/driver/dashboard', [SchedulingController::class, 'driverDashboard'])->middleware('role:driver,admin');
});
