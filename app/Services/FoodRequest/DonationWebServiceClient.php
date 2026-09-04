<?php

/**
 * Module: Food Request and Reservation Module
 * Author: Loo Zi Wei (Student ID: 2408082)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\FoodRequest;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DonationWebServiceClient
{
    public function getAvailableDonations(array $filters = []): array
    {
        $requestID = 'REQ-'.Str::uuid()->toString();

        $response = Http::acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->get(
                config('services.donation_web_service.url'),
                array_merge($filters, [
                    'requestID' => $requestID,
                ])
            );

        if (! $response->successful()) {
            throw new RuntimeException('Donation web service request failed.');
        }

        $payload = $response->json();

        if (
            ($payload['status'] ?? null) !== 'S' ||
            ($payload['requestID'] ?? null) !== $requestID ||
            ! isset($payload['timeStamp'])
        ) {
            throw new RuntimeException('Invalid response from donation web service.');
        }

        return $payload;
    }
}