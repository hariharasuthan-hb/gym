<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\WorkoutPlanCreated;
use App\Services\NotificationService;

class SendWorkoutPlanCreatedNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(WorkoutPlanCreated $event): void
    {
        try {
            $workoutPlan = $event->workoutPlan;
            
            // Reload workout plan with relationships
            $workoutPlan->load(['member', 'trainer']);
            
            $member = $workoutPlan->member;
            
            if (!$member) {
                \Illuminate\Support\Facades\Log::error('Workout plan notification failed: Member not found', [
                    'workout_plan_id' => $workoutPlan->id,
                ]);
                return;
            }
            
            // Check for duplicate notification within last 10 minutes
            $existingNotification = $member->notifications()
                ->where('type', \App\Notifications\DatabaseNotification::class)
                ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [NotificationType::WORKOUT_PLAN_CREATED->value])
                ->whereRaw("JSON_EXTRACT(data, '$.workout_plan_id') = ?", [$workoutPlan->id])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->first();
            
            if ($existingNotification) {
                \Illuminate\Support\Facades\Log::info('Duplicate workout plan notification prevented', [
                    'member_id' => $member->id,
                    'workout_plan_id' => $workoutPlan->id,
                    'existing_notification_id' => $existingNotification->id,
                ]);
                return;
            }
            
            \Illuminate\Support\Facades\Log::info('Sending workout plan notification', [
                'member_id' => $member->id,
                'workout_plan_id' => $workoutPlan->id,
                'plan_name' => $workoutPlan->plan_name,
            ]);
            
            $memberMessage = "A new workout plan '{$workoutPlan->plan_name}' has been created for you!";
            $adminMessage = "Workout plan '{$workoutPlan->plan_name}' created for {$member->name} ({$member->email}).";
            
            // Send to member
            $this->notificationService->send(
                $member,
                NotificationType::WORKOUT_PLAN_CREATED,
                $memberMessage,
                '/member/workout-plans/' . $workoutPlan->id,
                [
                    'workout_plan_id' => $workoutPlan->id,
                    'plan_name' => $workoutPlan->plan_name,
                ]
            );
            
            // Send to all admins
            $admins = $this->notificationService->getAdmins();
            if ($admins->isNotEmpty()) {
                $this->notificationService->sendToMany(
                    $admins,
                    NotificationType::WORKOUT_PLAN_CREATED,
                    $adminMessage,
                    '/admin/workout-plans/' . $workoutPlan->id,
                    [
                        'workout_plan_id' => $workoutPlan->id,
                        'plan_name' => $workoutPlan->plan_name,
                        'member_id' => $member->id,
                        'member_name' => $member->name,
                        'member_email' => $member->email,
                    ]
                );
            }
            
            // Send to trainer if exists
            $trainer = $workoutPlan->trainer;
            if ($trainer) {
                $trainerMessage = "Workout plan '{$workoutPlan->plan_name}' created for your member {$member->name}.";
                $this->notificationService->send(
                    $trainer,
                    NotificationType::WORKOUT_PLAN_CREATED,
                    $trainerMessage,
                    '/admin/workout-plans/' . $workoutPlan->id,
                    [
                        'workout_plan_id' => $workoutPlan->id,
                        'plan_name' => $workoutPlan->plan_name,
                        'member_id' => $member->id,
                        'member_name' => $member->name,
                    ]
                );
            }
            
            \Illuminate\Support\Facades\Log::info('Workout plan notification sent successfully', [
                'member_id' => $member->id,
                'workout_plan_id' => $workoutPlan->id,
                'admin_notified' => $admins->isNotEmpty(),
                'trainer_notified' => $trainer !== null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send workout plan notification', [
                'workout_plan_id' => $event->workoutPlan->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
