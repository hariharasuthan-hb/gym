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
        $userMessage = $event->message ?? "Your {$event->entityType} has been approved.";
        $adminMessage = "{$event->user->name}'s {$event->entityType} has been approved.";

        // Send to user
        $this->notificationService->send(
            $event->user,
            NotificationType::ADMIN_APPROVAL,
            $userMessage,
            null,
            ['entity_type' => $event->entityType]
        );
        
        // Send to admins
        $admins = $this->notificationService->getAdmins();
        if ($admins->isNotEmpty()) {
            $this->notificationService->sendToMany(
                $admins,
                NotificationType::ADMIN_APPROVAL,
                $adminMessage,
                null,
                ['entity_type' => $event->entityType, 'user_id' => $event->user->id]
            );
        }
    }
}

