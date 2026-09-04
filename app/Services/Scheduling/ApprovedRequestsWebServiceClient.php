<?php

/**
 * Module: Pickup and Delivery Scheduling Module
 * Author: Loo Zhi Yin (Student ID: 2410857)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Scheduling;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ApprovedRequestsWebServiceClient
{
    public function getApprovedRequests(int $limit = 50): array
    {
        $requestID = 'REQ-'.Str::uuid()->toString();

        $response = Http::acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->get(
                config('services.food_request_web_service.url'),
                [
                    'requestID' => $requestID,
                    'limit' => $limit,
                ]
            );

        if (! $response->successful()) {
            throw new RuntimeException('Food request web service failed with status '.$response->status());
        }

        $payload = $response->json();

        if (
            ($payload['status'] ?? null) !== 'S' ||
            ($payload['requestID'] ?? null) !== $requestID ||
            ! isset($payload['timeStamp'])
        ) {
            throw new RuntimeException('Invalid response format from food request web service.');
        }

        return $payload;
    }
}
