<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\User;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DeliveryStatusWebServiceClient
{
    public function getDeliveryStatus(?int $deliveryId = null, ?int $reservationId = null): array
    {
        $requestID = 'REQ-'.Str::uuid()->toString();

        $params = ['requestID' => $requestID];
        if ($deliveryId !== null) {
            $params['delivery_id'] = $deliveryId;
        }
        if ($reservationId !== null) {
            $params['reservation_id'] = $reservationId;
        }

        $response = Http::acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->get(
                config('services.delivery_web_service.url'),
                $params
            );

        if (! $response->successful()) {
            throw new RuntimeException('Delivery web service failed with status '.$response->status());
        }

        $payload = $response->json();

        if (
            ($payload['status'] ?? null) !== 'S' ||
            ($payload['requestID'] ?? null) !== $requestID ||
            ! isset($payload['timeStamp'])
        ) {
            throw new RuntimeException('Invalid response format from delivery web service.');
        }

        return $payload;
    }
}
