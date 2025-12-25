<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\EntityApproved;
use App\Services\NotificationService;

class SendEntityApprovalNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(EntityApproved $event): void
    {
        try {
            $userMessage = $event->message ?? "Your {$event->entityType} has been approved.";
            $adminMessage = "{$event->user->name}'s {$event->entityType} has been approved.";

            // Get entity ID for duplicate checking
            $entityId = null;
            if (is_object($event->entity) && isset($event->entity->id)) {
                $entityId = $event->entity->id;
            } elseif (is_array($event->entity) && isset($event->entity['id'])) {
                $entityId = $event->entity['id'];
            }

            // Check for duplicate notification within last 5 minutes for the same entity
            if ($entityId) {
                $existingNotification = $event->user->notifications()
                    ->where('type', \App\Notifications\DatabaseNotification::class)
                    ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [NotificationType::ADMIN_APPROVAL->value])
                    ->whereRaw("JSON_EXTRACT(data, '$.entity_type') = ?", [$event->entityType])
                    ->whereRaw("JSON_EXTRACT(data, '$.entity_id') = ?", [$entityId])
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->first();
                
                if ($existingNotification) {
                    \Illuminate\Support\Facades\Log::info('Duplicate approval notification prevented', [
                        'user_id' => $event->user->id,
                        'entity_type' => $event->entityType,
                        'entity_id' => $entityId,
                        'existing_notification_id' => $existingNotification->id,
                    ]);
                    return;
                }
            }

            // Send to user
            $this->notificationService->send(
                $event->user,
                NotificationType::ADMIN_APPROVAL,
                $userMessage,
                null,
                [
                    'entity_type' => $event->entityType,
                    'entity_id' => $entityId,
                ]
            );
            
            // Send to admins
            $admins = $this->notificationService->getAdmins();
            if ($admins->isNotEmpty()) {
                $this->notificationService->sendToMany(
                    $admins,
                    NotificationType::ADMIN_APPROVAL,
                    $adminMessage,
                    null,
                    [
                        'entity_type' => $event->entityType,
                        'entity_id' => $entityId,
                        'user_id' => $event->user->id
                    ]
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send approval notification', [
                'user_id' => $event->user->id ?? null,
                'entity_type' => $event->entityType ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

