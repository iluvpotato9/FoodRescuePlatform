<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_requests', function (Blueprint $table) {
            $table->foreignId('donation_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_requested', 10, 2)->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('collection_deadline')->nullable();
            $table->timestamp('fulfillment_scheduled_at')->nullable();
        });

        $fallbackDonationId = DB::table('donations')->where('is_active', true)->value('id');
        if ($fallbackDonationId) {
            DB::table('food_requests')
                ->whereNull('donation_id')
                ->update([
                    'donation_id' => $fallbackDonationId,
                    'quantity_requested' => 1,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('food_requests', function (Blueprint $table) {
            $table->dropForeign(['donation_id']);
            $table->dropColumn([
                'donation_id',
                'quantity_requested',
                'ready_at',
                'collection_deadline',
                'fulfillment_scheduled_at',
            ]);
        });
    }
};
