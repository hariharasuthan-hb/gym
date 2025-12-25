<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'notifications:subscription-expiry-reminders {--days=7 : Number of days before expiry}';

    protected $description = 'Send subscription expiry reminder notifications to users';

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly NotificationRepositoryInterface $notificationRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $this->info("Checking for subscriptions expiring in {$days} days...");

        $expiringSubscriptions = $this->notificationRepository->getExpiringSubscriptions($days);

        if ($expiringSubscriptions->isEmpty()) {
            $this->info('No expiring subscriptions found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiringSubscriptions->count()} expiring subscriptions.");

        $sent = 0;
        foreach ($expiringSubscriptions as $subscription) {
            try {
                $user = \App\Models\User::find($subscription->user_id);
                if (!$user) {
                    continue;
                }

                $plan = \App\Models\SubscriptionPlan::find($subscription->subscription_plan_id);
                $expiryDate = $subscription->next_billing_at ?? $subscription->trial_end_at;
                $formattedDate = \Carbon\Carbon::parse($expiryDate)->format('M d, Y');

                $userMessage = "Your subscription to {$plan->plan_name} will expire on {$formattedDate}. Please renew to continue enjoying our services.";
                $adminMessage = "User {$user->name}'s subscription to {$plan->plan_name} will expire on {$formattedDate}";
                
                // Send to user and admins
                $this->notificationService->sendToUserAndAdmins(
                    $user,
                    NotificationType::SUBSCRIPTION_EXPIRY_REMINDER,
                    $userMessage,
                    $adminMessage,
                    '/member/subscription',
                    [
                        'plan_name' => $plan->plan_name,
                        'expiry_date' => $expiryDate,
                    ],
                    false // Don't include trainer for expiry reminders
                );

                $sent++;
            } catch (\Exception $e) {
                $this->error("Failed to send notification to user {$subscription->user_id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} expiry reminder notifications.");
        return Command::SUCCESS;
    }
}

