<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('food_requests')->cascadeOnDelete();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_reserved', 10, 2);
            $table->date('reservation_date');
            $table->timestamps();

            $table->unique(['donation_id', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
