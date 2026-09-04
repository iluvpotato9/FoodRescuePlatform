<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyRequests = DB::table('food_requests')
            ->join('donations', 'donations.id', '=', 'food_requests.donation_id')
            ->where('food_requests.status', 'reserved')
            ->whereNull('food_requests.ready_at')
            ->select('food_requests.id', 'donations.collection_location')
            ->get();

        foreach ($legacyRequests as $request) {
            if ($request->collection_location === 'food_bank') {
                DB::table('food_requests')->where('id', $request->id)->update([
                    'ready_at' => now(),
                    'collection_deadline' => now()->addDays(3)->endOfDay(),
                ]);
            } else {
                DB::table('food_requests')->where('id', $request->id)->update([
                    'status' => 'approved',
                ]);
            }
        }
    }

    public function down(): void
    {
        // Existing request history cannot be reliably reconstructed.
    }
};
