<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebController::class, 'home'])->name('home');
Route::get('/donations', [WebController::class, 'donations'])->name('donations.index');
Route::get('/donations/{id}', [WebController::class, 'donationShow'])->name('donations.show')->whereNumber('id');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [WebController::class, 'dashboard'])->name('dashboard');
    Route::post('/notifications/read', [WebController::class, 'notificationsRead'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show')->middleware('role:beneficiary');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('role:beneficiary');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy')->middleware('role:beneficiary');
    Route::get('/donations/create', [WebController::class, 'donationCreateForm'])->name('donations.create')->middleware('role:donor,admin');
    Route::post('/donations', [WebController::class, 'donationStore'])->name('donations.store')->middleware('role:donor,admin');
    Route::patch('/donations/{donation}', [WebController::class, 'donationUpdate'])->name('donations.update')->middleware('role:donor,admin');
    Route::delete('/donations/{donation}', [WebController::class, 'donationDestroy'])->name('donations.destroy')->middleware('role:donor,admin');
    Route::get('/requests', [WebController::class, 'requests'])->name('requests.index')->middleware('role:beneficiary,admin');
    Route::post('/requests', [WebController::class, 'requestStore'])->name('requests.store')->middleware('role:beneficiary');
    Route::patch('/requests/{foodRequest}/status', [WebController::class, 'requestStatus'])->name('requests.status');
    Route::patch('/requests/{foodRequest}/schedule', [WebController::class, 'requestSchedule'])->name('requests.schedule')->middleware('role:beneficiary');
    Route::delete('/reservations/{reservation}', [WebController::class, 'reservationDestroy'])->name('reservations.destroy');
    Route::post('/pickups', [WebController::class, 'pickupStore'])->name('pickups.store')->middleware('role:admin');
    Route::patch('/pickups/{pickup}/status', [WebController::class, 'pickupStatus'])->name('pickups.status')->middleware('role:driver,admin');
    Route::post('/deliveries', [WebController::class, 'deliveryStore'])->name('deliveries.store')->middleware('role:admin');
    Route::patch('/deliveries/{delivery}/status', [WebController::class, 'deliveryStatus'])->name('deliveries.status')->middleware('role:driver,admin');
});
