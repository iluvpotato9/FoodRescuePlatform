<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $message,
        private string $entityType,
        private int $entityId,
        private string $status,
        private ?int $foodRequestId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'status' => $this->status,
            'food_request_id' => $this->foodRequestId,
        ];
    }
}
