<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\WorkoutVideoAssigned;
use App\Services\NotificationService;

class SendWorkoutVideoNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(WorkoutVideoAssigned $event): void
    {
        $planName = $event->workoutPlan->plan_name;
        $user = $event->user;
        
        // Load workout plan with trainer relationship
        $event->workoutPlan->load('trainer');
        $trainer = $event->workoutPlan->trainer;

        $userMessage = "A new workout video has been assigned to your plan: {$planName}";
        $adminMessage = "Workout video assigned to {$user->name}'s plan: {$planName}";
        $trainerMessage = "Workout video assigned to your member {$user->name}'s plan: {$planName}";
        
        // Send to user
        $this->notificationService->send(
            $user,
            NotificationType::WORKOUT_VIDEO,
            $userMessage,
            '/member/workout-plans/' . $event->workoutPlan->id,
            ['workout_plan_id' => $event->workoutPlan->id]
        );
        
        // Send to admins
        $admins = $this->notificationService->getAdmins();
        if ($admins->isNotEmpty()) {
            $this->notificationService->sendToMany(
                $admins,
                NotificationType::WORKOUT_VIDEO,
                $adminMessage,
                '/admin/workout-plans/' . $event->workoutPlan->id,
                ['workout_plan_id' => $event->workoutPlan->id, 'member_id' => $user->id]
            );
        }
        
        // Send to trainer if exists
        if ($trainer) {
            $this->notificationService->send(
                $trainer,
                NotificationType::WORKOUT_VIDEO,
                $trainerMessage,
                '/admin/workout-plans/' . $event->workoutPlan->id,
                ['workout_plan_id' => $event->workoutPlan->id, 'member_id' => $user->id]
            );
        }
    }
}

