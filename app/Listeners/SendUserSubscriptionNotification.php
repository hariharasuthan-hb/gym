<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\UserSubscribed;
use App\Services\NotificationService;

class SendUserSubscriptionNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(UserSubscribed $event): void
    {
        try {
            $subscription = $event->subscription;
            
            // Reload subscription with relationships
            $subscription->load(['subscriptionPlan', 'user']);
            
            $plan = $subscription->subscriptionPlan;
            $user = $subscription->user;
            
            if (!$plan) {
                \Illuminate\Support\Facades\Log::error('Subscription notification failed: Plan not found', [
                    'subscription_id' => $subscription->id,
                ]);
                return;
            }
            
            if (!$user) {
                \Illuminate\Support\Facades\Log::error('Subscription notification failed: User not found', [
                    'subscription_id' => $subscription->id,
                ]);
                return;
            }
            
            // Check for duplicate notification within last 10 minutes
            $existingNotification = $user->notifications()
                ->where('type', \App\Notifications\DatabaseNotification::class)
                ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [\App\Enums\NotificationType::USER_SUBSCRIPTION->value])
                ->whereRaw("JSON_EXTRACT(data, '$.subscription_id') = ?", [$subscription->id])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->first();
            
            if ($existingNotification) {
                \Illuminate\Support\Facades\Log::info('Duplicate subscription notification prevented', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'existing_notification_id' => $existingNotification->id,
                ]);
                return;
            }
            
            \Illuminate\Support\Facades\Log::info('Sending subscription notification', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_name' => $plan->plan_name,
            ]);
            
            $userMessage = "Your subscription to {$plan->plan_name} has been activated successfully!";
            $adminMessage = "New subscription: {$user->name} ({$user->email}) has successfully subscribed to {$plan->plan_name} plan.";
            
            // Send to user
            $this->notificationService->send(
                $user,
                NotificationType::USER_SUBSCRIPTION,
                $userMessage,
                '/member/subscription',
                ['plan_name' => $plan->plan_name, 'subscription_id' => $subscription->id]
            );
            
            // Send to all admins
            $admins = $this->notificationService->getAdmins();
            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Log::info('Sending subscription notification to admins', [
                    'admin_count' => $admins->count(),
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                ]);
                
                $this->notificationService->sendToMany(
                    $admins,
                    NotificationType::USER_SUBSCRIPTION,
                    $adminMessage,
                    '/admin/subscriptions',
                    [
                        'plan_name' => $plan->plan_name,
                        'subscription_id' => $subscription->id,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                    ]
                );
                
                \Illuminate\Support\Facades\Log::info('Subscription notification sent to admins successfully', [
                    'admin_count' => $admins->count(),
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('No admins found to send subscription notification', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                ]);
            }
            
            // Send to all trainers
            $trainers = $this->notificationService->getTrainers();
            if ($trainers->isNotEmpty()) {
                \Illuminate\Support\Facades\Log::info('Sending subscription notification to trainers', [
                    'trainer_count' => $trainers->count(),
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                ]);
                
                $this->notificationService->sendToMany(
                    $trainers,
                    NotificationType::USER_SUBSCRIPTION,
                    $adminMessage,
                    '/admin/subscriptions',
                    [
                        'plan_name' => $plan->plan_name,
                        'subscription_id' => $subscription->id,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                    ]
                );
                
                \Illuminate\Support\Facades\Log::info('Subscription notification sent to trainers successfully', [
                    'trainer_count' => $trainers->count(),
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('No trainers found to send subscription notification', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                ]);
            }
            
            \Illuminate\Support\Facades\Log::info('Subscription notification sent successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'admin_notified' => $admins->isNotEmpty(),
                'trainer_notified' => $trainers->isNotEmpty(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send subscription notification', [
                'subscription_id' => $event->subscription->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

