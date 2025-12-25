<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\DietPlan;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendDailyWorkoutDietPlanNotifications extends Command
{
    protected $signature = 'notifications:daily-workout-diet-plan {--date= : Target date (Y-m-d format, defaults to today)}';

    protected $description = 'Send daily workout and diet plan notifications to active users based on start and end dates';

    public function __construct(
        private readonly NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetDate = $this->option('date') 
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $this->info("Sending daily workout and diet plan notifications for {$targetDate->format('Y-m-d')}...");

        // Get users with active plans that are within date range
        $usersWithActivePlans = User::where('status', 'active')
            ->whereHas('activeSubscription')
            ->where(function ($query) use ($targetDate) {
                // Users with workout plans within date range
                $query->whereHas('workoutPlans', function ($q) use ($targetDate) {
                    $q->where('status', 'active')
                        ->whereDate('start_date', '<=', $targetDate->toDateString())
                        ->where(function ($dateQuery) use ($targetDate) {
                            $dateQuery->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $targetDate->toDateString());
                        });
                })
                // OR users with diet plans within date range
                ->orWhereHas('dietPlans', function ($q) use ($targetDate) {
                    $q->where('status', 'active')
                        ->whereDate('start_date', '<=', $targetDate->toDateString())
                        ->where(function ($dateQuery) use ($targetDate) {
                            $dateQuery->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $targetDate->toDateString());
                        });
                });
            })
            ->get();

        if ($usersWithActivePlans->isEmpty()) {
            $this->info('No users with active plans found for this date.');
            return Command::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($usersWithActivePlans as $user) {
            try {
                // Check workout plans within date range
                $hasWorkoutPlan = WorkoutPlan::where('member_id', $user->id)
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', $targetDate->toDateString())
                    ->where(function ($query) use ($targetDate) {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $targetDate->toDateString());
                    })
                    ->exists();

                // Check diet plans within date range
                $hasDietPlan = DietPlan::where('member_id', $user->id)
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', $targetDate->toDateString())
                    ->where(function ($query) use ($targetDate) {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $targetDate->toDateString());
                    })
                    ->exists();

                if (!$hasWorkoutPlan && !$hasDietPlan) {
                    $skipped++;
                    continue;
                }

                // Check for duplicate notification within last 24 hours
                $existingNotification = $user->notifications()
                    ->where('type', \App\Notifications\DatabaseNotification::class)
                    ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [NotificationType::DAILY_WORKOUT_DIET_PLAN->value])
                    ->whereDate('created_at', $targetDate->toDateString())
                    ->first();

                if ($existingNotification) {
                    $this->warn("Duplicate notification prevented for user {$user->id} ({$user->name})");
                    $skipped++;
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
                    [
                        'has_workout_plan' => $hasWorkoutPlan,
                        'has_diet_plan' => $hasDietPlan,
                        'notification_date' => $targetDate->toDateString(),
                    ],
                    true // Include trainer for daily plans
                );

                $sent++;
            } catch (\Exception $e) {
                $this->error("Failed to send notification to user {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} daily notifications. Skipped {$skipped} duplicates or invalid plans.");
        return Command::SUCCESS;
    }
}

