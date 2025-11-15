<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'gateway',
        'gateway_customer_id',
        'gateway_subscription_id',
        'status',
        'trial_end_at',
        'next_billing_at',
        'started_at',
        'canceled_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_end_at' => 'datetime',
            'next_billing_at' => 'datetime',
            'started_at' => 'datetime',
            'canceled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the payments for the subscription.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('next_billing_at')
                  ->orWhere('next_billing_at', '>=', now());
            });
    }

    /**
     * Check if subscription is in trial period.
     */
    public function isTrialing(): bool
    {
        return $this->status === 'trialing' 
            && $this->trial_end_at 
            && $this->trial_end_at->isFuture();
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && (!$this->next_billing_at || $this->next_billing_at->isFuture());
    }

    /**
     * Check if subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled' || $this->canceled_at !== null;
    }

    /**
     * Check if user still has access to subscription.
     * Returns true if subscription is active/trialing OR if canceled but period hasn't ended yet.
     */
    public function hasAccess(): bool
    {
        // If active or trialing, user has access
        if ($this->isActive() || $this->isTrialing()) {
            return true;
        }

        // If canceled but next_billing_at is in the future, user still has access
        if ($this->isCanceled() && $this->next_billing_at && $this->next_billing_at->isFuture()) {
            return true;
        }

        return false;
    }
}
