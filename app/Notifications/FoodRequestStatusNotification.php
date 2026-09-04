<?php

namespace App\Notifications;

use App\Models\FoodRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FoodRequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private FoodRequest $foodRequest,
        private string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $foodName = $this->foodRequest->donation?->title ?? 'your selected food';

        return [
            'title' => "Request #{$this->foodRequest->id} updated",
            'message' => "Your request for {$foodName} was {$this->status}.",
            'action' => 'view_request',
            'food_request_id' => $this->foodRequest->id,
            'status' => $this->status,
        ];
    }
}
