<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->roundToWholeNumbers('donations', 'quantity');
        $this->roundToWholeNumbers('donation_items', 'quantity');
        $this->roundToWholeNumbers('reservations', 'quantity_reserved');
        $this->roundToWholeNumbers('food_requests', 'quantity_requested');

        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });
        Schema::table('donation_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('quantity_reserved')->change();
        });
        Schema::table('food_requests', function (Blueprint $table) {
            $table->unsignedInteger('quantity_requested')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change();
        });
        Schema::table('donation_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change();
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('quantity_reserved', 10, 2)->change();
        });
        Schema::table('food_requests', function (Blueprint $table) {
            $table->decimal('quantity_requested', 10, 2)->nullable()->change();
        });
    }

    private function roundToWholeNumbers(string $table, string $column): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        $column => max(1, (int) round($row->{$column})),
                    ]);
                }
            });
    }
};
