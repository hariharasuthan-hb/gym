<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\UserRegistered;
use App\Services\NotificationService;

class SendUserRegistrationNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(UserRegistered $event): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('Sending user registration notification', [
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
            ]);
            
            $userMessage = "Welcome to our gym, {$event->user->name}! Your account has been successfully created.";
            $adminMessage = "New user registered: {$event->user->name} ({$event->user->email})";
            
            // Send to user and admins
            $this->notificationService->sendToUserAndAdmins(
                $event->user,
                NotificationType::USER_REGISTRATION,
                $userMessage,
                $adminMessage,
                '/dashboard',
                ['user_id' => $event->user->id, 'user_email' => $event->user->email],
                false // Don't include trainer for registration
            );
            
            \Illuminate\Support\Facades\Log::info('User registration notification sent successfully', [
                'user_id' => $event->user->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send user registration notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

