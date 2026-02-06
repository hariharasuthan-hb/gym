<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillSubscriptionExpiration extends Command
{
    protected $signature = 'subscriptions:backfill-expiration';

    protected $description = 'Backfill expiration_at for existing subscriptions based on plan duration and lifecycle dates';

    public function handle(): int
    {
        $this->info('Backfilling expiration_at for subscriptions without it set...');

        $count = 0;

        Subscription::query()
            ->whereNull('expiration_at')
            ->with('subscriptionPlan')
            ->chunkById(100, function ($subscriptions) use (&$count) {
                /** @var Subscription $subscription */
                foreach ($subscriptions as $subscription) {
                    $plan = $subscription->subscriptionPlan;

                    if (!$plan instanceof SubscriptionPlan) {
                        continue;
                    }

                    $startedAt = $subscription->started_at instanceof Carbon
                        ? $subscription->started_at
                        : ($subscription->created_at instanceof Carbon ? $subscription->created_at : null);

                    $trialEndAt = $subscription->trial_end_at instanceof Carbon ? $subscription->trial_end_at : null;
                    $nextBillingAt = $subscription->next_billing_at instanceof Carbon ? $subscription->next_billing_at : null;

                    $expirationAt = Subscription::calculateExpiration(
                        $plan,
                        $startedAt,
                        $trialEndAt,
                        $nextBillingAt
                    );

                    if ($expirationAt instanceof Carbon) {
                        $subscription->update([
                            'expiration_at' => $expirationAt,
                        ]);
                        $count++;
                    }
                }
            });

        $this->info("Backfilled expiration_at for {$count} subscription(s).");

        return Command::SUCCESS;
    }
}

