<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\UserUploadedContent;
use App\Services\NotificationService;

class SendUserUploadNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(UserUploadedContent $event): void
    {
        try {
            $contentName = $event->contentName ?? ucfirst($event->contentType);
            
            // Check for duplicate notification within last 2 minutes for the same content path
            $existingNotification = $event->user->notifications()
                ->where('type', \App\Notifications\DatabaseNotification::class)
                ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [NotificationType::USER_UPLOAD->value])
                ->whereRaw("JSON_EXTRACT(data, '$.content_path') = ?", [$event->contentPath])
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();
            
            if ($existingNotification) {
                \Illuminate\Support\Facades\Log::info('Duplicate upload notification prevented in listener', [
                    'user_id' => $event->user->id,
                    'content_path' => $event->contentPath,
                    'existing_notification_id' => $existingNotification->id,
                ]);
                return;
            }
            
            $userMessage = "Your {$contentName} has been uploaded successfully.";
            $adminMessage = "User {$event->user->name} uploaded a {$contentName}";
            
            // Send to user, admins, and trainer (if assigned)
            $this->notificationService->sendToUserAndAdmins(
                $event->user,
                NotificationType::USER_UPLOAD,
                $userMessage,
                $adminMessage,
                null,
                ['content_type' => $event->contentType, 'content_path' => $event->contentPath],
                true // Include trainer for uploads
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send upload notification', [
                'user_id' => $event->user->id ?? null,
                'content_path' => $event->contentPath ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

