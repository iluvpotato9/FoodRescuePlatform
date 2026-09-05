<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('donations:expire', function () {
    $service = app(\App\Services\Donation\DonationService::class);
    $count = $service->expireDonations();
    $this->info("Expired {$count} donations.");
})->purpose('Auto-expire donations past their expiry date');

Schedule::command('donations:expire')->dailyAt('00:10');
