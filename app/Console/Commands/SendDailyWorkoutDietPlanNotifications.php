<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDailyWorkoutDietPlanNotifications extends Command
{
    protected $signature = 'notifications:daily-workout-diet-plan';

    protected $description = 'Send daily workout and diet plan notifications to active users';

    public function __construct(
        private readonly NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Sending daily workout and diet plan notifications...');

        $activeUsers = User::where('status', 'active')
            ->where(function ($query) {
                $query->whereHas('activeSubscription')
                    ->orWhereHas('workoutPlans', function ($q) {
                        $q->where('status', 'active');
                    });
            })
            ->get();

        if ($activeUsers->isEmpty()) {
            $this->info('No active users found.');
            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($activeUsers as $user) {
            try {
                $hasWorkoutPlan = $user->workoutPlans()->where('status', 'active')->exists();
                $hasDietPlan = $user->dietPlans()->where('status', 'active')->exists();

                if (!$hasWorkoutPlan && !$hasDietPlan) {
                    continue;
                }

                $message = 'Check out your daily workout and diet plan updates!';
                if ($hasWorkoutPlan && $hasDietPlan) {
                    $message = 'Your daily workout and diet plan are ready!';
                } elseif ($hasWorkoutPlan) {
                    $message = 'Your daily workout plan is ready!';
                } elseif ($hasDietPlan) {
                    $message = 'Your daily diet plan is ready!';
                }

                $userMessage = $message;
                $adminMessage = "Daily workout/diet plan notification sent to {$user->name}";
                
                // Send to user, admins, and trainer (if assigned)
                $this->notificationService->sendToUserAndAdmins(
                    $user,
                    NotificationType::DAILY_WORKOUT_DIET_PLAN,
                    $userMessage,
                    $adminMessage,
                    '/member/dashboard',
                    ['has_workout_plan' => $hasWorkoutPlan, 'has_diet_plan' => $hasDietPlan],
                    true // Include trainer for daily plans
                );

                $sent++;
            } catch (\Exception $e) {
                $this->error("Failed to send notification to user {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} daily notifications.");
        return Command::SUCCESS;
    }
}

