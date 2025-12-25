<?php

namespace App\Listeners;

use App\Events\EntityRejected;
use App\Enums\NotificationType;
use App\Services\NotificationService;

class SendEntityRejectionNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(EntityRejected $event): void
    {
        $userMessage = "Your {$event->entityType} has been rejected.";
        if ($event->reason) {
            $userMessage .= " Reason: {$event->reason}";
        }
        
        $adminMessage = "{$event->user->name}'s {$event->entityType} has been rejected.";
        if ($event->reason) {
            $adminMessage .= " Reason: {$event->reason}";
        }

        // Extract entity ID for duplicate prevention
        $entityId = null;
        if (is_object($event->entity) && isset($event->entity->id)) {
            $entityId = $event->entity->id;
        } elseif (is_array($event->entity) && isset($event->entity['id'])) {
            $entityId = $event->entity['id'];
        }

        // Send to user with entity_id for duplicate prevention
        $this->notificationService->send(
            $event->user,
            NotificationType::ADMIN_REJECTION,
            $userMessage,
            null,
            [
                'entity_type' => $event->entityType,
                'entity_id' => $entityId,
                'reason' => $event->reason
            ]
        );
        
        // Send to admins with entity_id for duplicate prevention
        $admins = $this->notificationService->getAdmins();
        if ($admins->isNotEmpty()) {
            $this->notificationService->sendToMany(
                $admins,
                NotificationType::ADMIN_REJECTION,
                $adminMessage,
                null,
                [
                    'entity_type' => $event->entityType,
                    'entity_id' => $entityId,
                    'reason' => $event->reason,
                    'user_id' => $event->user->id
                ]
            );
        }
    }
}

