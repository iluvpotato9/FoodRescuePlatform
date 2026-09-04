<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('donations')->update([
            'pickup_address' => config('foodrescue.food_bank_address'),
            'pickup_time' => null,
            'collection_location' => 'food_bank',
        ]);
    }

    public function down(): void
    {
        // Private donor pickup addresses are intentionally not restored.
    }
};
