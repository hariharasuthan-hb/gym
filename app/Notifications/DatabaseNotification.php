<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public NotificationType $type,
        public string $message,
        public ?string $actionUrl = null,
        public array $additionalData = []
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->type->getTitle(),
            'message' => $this->message,
            'type' => $this->type->value,
            'action_url' => $this->actionUrl,
            'icon' => $this->type->getIcon(),
            ...$this->additionalData,
        ];
    }
}

