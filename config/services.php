<?php

return [
    'donation_web_service' => [
        'url' => env(
            'DONATION_WEB_SERVICE_URL',
            'http://127.0.0.1:8000/api/webservice/donations/available'
        ),
    ],
    'food_request_web_service' => [
        'url' => env(
            'FOOD_REQUEST_WEB_SERVICE_URL',
            'http://127.0.0.1:8000/api/webservice/requests/approved'
        ),
    ],
    'delivery_web_service' => [
        'url' => env(
            'DELIVERY_WEB_SERVICE_URL',
            'http://127.0.0.1:8000/api/webservice/deliveries/status'
        ),
    ],
    'user_profile_web_service' => [
        'url' => env(
            'USER_PROFILE_WEB_SERVICE_URL',
            'http://127.0.0.1:8000/api/webservice/users'
        ),
    ],
];