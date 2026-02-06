<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark subscriptions as expired when their expiration_at date has passed';

    public function handle(): int
    {
        $now = Carbon::now();

        $this->info('Checking for subscriptions to expire as of ' . $now->toDateTimeString());

        $query = Subscription::query()
            ->whereIn('status', [
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_PAST_DUE,
            ])
            ->whereNotNull('expiration_at')
            ->where('expiration_at', '<=', $now);

        $count = 0;

        $query->chunkById(100, function ($subscriptions) use (&$count) {
            foreach ($subscriptions as $subscription) {
                $subscription->update([
                    'status' => Subscription::STATUS_EXPIRED,
                ]);
                $count++;
            }
        });

        $this->info("Marked {$count} subscription(s) as expired.");

        return Command::SUCCESS;
    }
}

