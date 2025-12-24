<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\UserRegistered;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;

class SendLaravelRegisteredNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(Registered $event): void
    {
        \Illuminate\Support\Facades\Log::info('Laravel Registered event received, dispatching UserRegistered', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
        ]);
        
        event(new UserRegistered($event->user));
    }
}

