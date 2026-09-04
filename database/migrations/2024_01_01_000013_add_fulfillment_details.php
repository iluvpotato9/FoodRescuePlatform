<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_requests', function (Blueprint $table) {
            $table->string('fulfillment_method', 30)->default('food_bank_pickup');
            $table->text('delivery_address')->nullable();
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->string('collection_location', 30)->default('donor');
        });
    }

    public function down(): void
    {
        Schema::table('food_requests', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_method', 'delivery_address']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('collection_location');
        });
    }
};
