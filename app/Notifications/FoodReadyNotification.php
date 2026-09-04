<?php

namespace App\Notifications;

use App\Models\FoodRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FoodReadyNotification extends Notification
{
    use Queueable;

    public function __construct(private FoodRequest $foodRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Request #{$this->foodRequest->id} is ready to schedule",
            'message' => "Your request for {$this->foodRequest->donation->title} is approved and the food is now at the food bank. Choose a collection or delivery time by {$this->foodRequest->collection_deadline->format('M j, Y')}.",
            'action' => 'choose_fulfillment_time',
            'food_request_id' => $this->foodRequest->id,
        ];
    }
}
