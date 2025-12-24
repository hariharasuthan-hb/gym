<?php

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionObserver
{
    /**
     * Handle the Subscription "updated" event.
     */
    public function updated(Subscription $subscription): void
    {
        // Check if status changed from pending/trialing to active
        if ($subscription->wasChanged('status')) {
            $oldStatus = $subscription->getOriginal('status');
            $newStatus = $subscription->status;
            
            // Dispatch notification when subscription becomes active or trialing
            if (
                in_array($oldStatus, ['pending', null]) && 
                in_array($newStatus, ['active', 'trialing'])
            ) {
                // Only dispatch if not already dispatched (check if notification exists)
                $subscription->load(['subscriptionPlan', 'user']);
                
                if ($subscription->user && $subscription->subscriptionPlan) {
                    \App\Events\UserSubscribed::dispatch($subscription);
                }
            }
        }
    }
}

