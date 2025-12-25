<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\TrainerStatusChanged;
use App\Services\NotificationService;

class SendTrainerStatusNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(TrainerStatusChanged $event): void
    {
        $type = $event->status === 'approved' 
            ? NotificationType::TRAINER_APPROVAL 
            : NotificationType::TRAINER_REJECTION;

        $trainerMessage = $event->status === 'approved'
            ? "Congratulations! Your trainer account has been approved."
            : "Your trainer account application has been rejected." . ($event->reason ? " Reason: {$event->reason}" : "");
        
        $adminMessage = $event->status === 'approved'
            ? "Trainer {$event->trainer->name} has been approved."
            : "Trainer {$event->trainer->name} has been rejected." . ($event->reason ? " Reason: {$event->reason}" : "");

        // Send to trainer
        $this->notificationService->send(
            $event->trainer,
            $type,
            $trainerMessage,
            '/dashboard',
            ['status' => $event->status, 'reason' => $event->reason]
        );
        
        // Send to admins (excluding the admin who made the change if needed)
        $admins = $this->notificationService->getAdmins();
        if ($admins->isNotEmpty()) {
            $this->notificationService->sendToMany(
                $admins,
                $type,
                $adminMessage,
                '/admin/trainers',
                ['status' => $event->status, 'reason' => $event->reason, 'trainer_id' => $event->trainer->id]
            );
        }
    }
}

