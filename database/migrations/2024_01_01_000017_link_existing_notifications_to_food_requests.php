<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->orderBy('created_at')->get()->each(function ($notification) {
            $data = json_decode($notification->data, true);
            if (! is_array($data)) {
                return;
            }

            $requestId = $data['food_request_id'] ?? null;

            if (! $requestId && ($data['entity_type'] ?? null) === 'delivery' && isset($data['entity_id'])) {
                $requestId = DB::table('delivery_schedules')
                    ->join('reservations', 'reservations.id', '=', 'delivery_schedules.reservation_id')
                    ->where('delivery_schedules.id', $data['entity_id'])
                    ->value('reservations.request_id');
            }

            if (! $requestId) {
                return;
            }

            $data['food_request_id'] = (int) $requestId;
            $data['title'] = str_contains(strtolower($data['title'] ?? ''), 'delivery')
                ? "Request #{$requestId} delivery updated"
                : "Request #{$requestId} is ready to schedule";

            DB::table('notifications')->where('id', $notification->id)->update([
                'data' => json_encode($data),
            ]);
        });
    }

    public function down(): void
    {
        // Existing notification wording is not restored.
    }
};
