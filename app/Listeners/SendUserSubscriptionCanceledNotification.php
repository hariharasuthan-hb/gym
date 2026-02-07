<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\UserSubscriptionCanceled;
use App\Services\NotificationService;

class SendUserSubscriptionCanceledNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function handle(UserSubscriptionCanceled $event): void
    {
        try {
            $subscription = $event->subscription->loadMissing(['subscriptionPlan', 'user']);
            $plan = $subscription->subscriptionPlan;
            $user = $subscription->user;

            if (!$plan || !$user) {
                \Illuminate\Support\Facades\Log::warning('Subscription cancel notification skipped: missing plan or user', [
                    'subscription_id' => $subscription->id,
                    'has_plan' => (bool) $plan,
                    'has_user' => (bool) $user,
                ]);
                return;
            }

            $userMessage = "Your subscription to {$plan->plan_name} has been cancelled. You will keep access until the end of the current billing period (if applicable).";
            $adminMessage = "Subscription cancelled: {$user->name} ({$user->email}) cancelled the {$plan->plan_name} plan.";

            $this->notificationService->sendToUserAndAdmins(
                $user,
                NotificationType::SUBSCRIPTION_CANCELED,
                $userMessage,
                $adminMessage,
                '/member/subscription',
                [
                    'plan_name' => $plan->plan_name,
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                ],
                includeTrainer: true,
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send subscription cancel notification', [
                'subscription_id' => $event->subscription->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

