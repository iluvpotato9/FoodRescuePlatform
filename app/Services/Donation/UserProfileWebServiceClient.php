<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Donation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class UserProfileWebServiceClient
{
    public function getUserProfile(int $userId): array
    {
        $requestID = 'REQ-'.Str::uuid()->toString();

        $baseUrl = rtrim(config('services.user_profile_web_service.url'), '/');
        $url = $baseUrl.'/'.$userId.'/profile';

        $response = Http::acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->get($url, [
                'requestID' => $requestID,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('User profile web service failed with status '.$response->status());
        }

        $payload = $response->json();

        if (
            ($payload['status'] ?? null) !== 'S' ||
            ($payload['requestID'] ?? null) !== $requestID ||
            ! isset($payload['timeStamp'])
        ) {
            throw new RuntimeException('Invalid response format from user profile web service.');
        }

        return $payload;
    }
}
