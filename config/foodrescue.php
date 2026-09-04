<?php

return [
    'food_bank_name' => env('FOOD_BANK_NAME', 'FoodBridge Community Hub'),
    'food_bank_address' => env('FOOD_BANK_ADDRESS', '100 Community Way, Central District'),
    'food_bank_hours' => env('FOOD_BANK_HOURS', 'Monday to Saturday, 9:00 AM to 6:00 PM'),
    'operating_days' => [1, 2, 3, 4, 5, 6],
    'opens_at' => env('FOOD_BANK_OPENS_AT', '09:00'),
    'closes_at' => env('FOOD_BANK_CLOSES_AT', '18:00'),
];
